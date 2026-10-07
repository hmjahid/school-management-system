<?php
declare(strict_types=1);

namespace App\Services\Analytics;

use App\Services\Catalog;

/**
 * Builds the JSON chart configs that `public/js/charts.js` mounts.
 *
 * Chart configuration is built here, in PHP, and handed to the view as a JSON
 * attribute — never hand-written in a template. That keeps the two vendored
 * chart libraries (Chart.js + ApexCharts) behind one surface: swapping either
 * one touches this file and `charts.js`, nothing else.
 *
 * Colours are read from the CSS custom properties on the client, so a chart
 * re-themes on a `data-theme` flip without repainting. Where a series needs a
 * concrete colour (product/variant series) it comes from {@see Catalog} or
 * {@see VariantResolver}, never a local map — that is the fix for a missing
 * `node` colour rendering in the app's blue.
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §5.3
 */
final class ChartPayload
{
    /**
     * Chart.js area/line series from `[{label, value}]` points.
     *
     * @param list<array{label: string, value: float|int}> $points
     * @param array{color?: string, label?: string, valueFormat?: string, cumulative?: bool, tension?: float} $opts
     * @return array<string, mixed>
     */
    public static function area(array $points, array $opts = []): array
    {
        $labels = array_map(static fn (array $p): string => (string) ($p['label'] ?? ''), $points);
        $data = array_map(static fn (array $p): float => (float) ($p['value'] ?? 0), $points);

        $datasets = [[
            'label'   => (string) ($opts['label'] ?? ''),
            'data'    => $data,
            'color'   => $opts['color'] ?? null,
            'fill'    => true,
            'tension' => (float) ($opts['tension'] ?? 0.35),
        ]];

        if (!empty($opts['cumulative'])) {
            $running = 0.0;
            $cum = [];
            foreach ($data as $v) {
                $running += $v;
                $cum[] = round($running, 2);
            }
            $datasets[] = [
                'label'      => (string) ($opts['cumulativeLabel'] ?? 'Cumulative'),
                'data'       => $cum,
                'color'      => $opts['cumulativeColor'] ?? null,
                'fill'       => false,
                'borderWidth' => 1.5,
                'tension'    => 0.35,
                'order'      => 0,
                'yAxisID'    => 'y1',
            ];
        }

        return [
            'type'        => 'line',
            'labels'      => $labels,
            'datasets'    => $datasets,
            'valueFormat' => (string) ($opts['valueFormat'] ?? 'number'),
            'scales'      => $opts['scales'] ?? self::defaultScales(!empty($opts['cumulative'])),
        ];
    }

    /**
     * Chart.js bar chart. `$datasets` = `[{label, data:number[], color?, colors?}]`.
     *
     * @param list<string> $labels
     * @param list<array{label?: string, data: list<float|int>, color?: string, colors?: list<string>}> $datasets
     * @return array<string, mixed>
     */
    public static function bars(array $labels, array $datasets, array $opts = []): array
    {
        $out = [];
        foreach ($datasets as $ds) {
            $out[] = [
                'label'           => (string) ($ds['label'] ?? ''),
                'data'            => array_map(static fn ($v): float => (float) $v, $ds['data'] ?? []),
                'color'           => $ds['color'] ?? null,
                'colors'          => $ds['colors'] ?? null,
                'backgroundColor' => $ds['color'] ?? null,
                'borderWidth'     => 0,
            ];
        }

        return [
            'type'        => 'bar',
            'labels'      => array_values($labels),
            'datasets'    => $out,
            'valueFormat' => (string) ($opts['valueFormat'] ?? 'number'),
            'scales'      => $opts['scales'] ?? self::defaultScales(false, !empty($opts['stacked'])),
        ];
    }

    /**
     * Chart.js stacked bar — the shape the "new vs renewal revenue" widget needs.
     *
     * @param list<string> $labels
     * @param list<array{label: string, data: list<float|int>, color?: string}> $datasets
     * @return array<string, mixed>
     */
    public static function stacked(array $labels, array $datasets, array $opts = []): array
    {
        $opts['stacked'] = true;

        return self::bars($labels, $datasets, $opts);
    }

    /**
     * Dual-line flow chart (activations vs deactivations).
     *
     * @param list<string> $labels
     * @param list<array{label: string, data: list<float|int>, color?: string}> $series
     * @return array<string, mixed>
     */
    public static function lines(array $labels, array $series, array $opts = []): array
    {
        $datasets = [];
        foreach ($series as $s) {
            $datasets[] = [
                'label'   => (string) ($s['label'] ?? ''),
                'data'    => array_map(static fn ($v): float => (float) $v, $s['data'] ?? []),
                'color'   => $s['color'] ?? null,
                'fill'    => false,
                'tension' => 0.35,
            ];
        }

        return [
            'type'        => 'line',
            'labels'      => array_values($labels),
            'datasets'    => $datasets,
            'valueFormat' => (string) ($opts['valueFormat'] ?? 'compact'),
            'scales'      => $opts['scales'] ?? self::defaultScales(false),
        ];
    }

    /**
     * ApexCharts sparkline for a KPI tile.
     *
     * @param list<float|int> $values
     * @return array<string, mixed>
     */
    public static function spark(array $values, string $color = 'var(--brand)', string $type = 'area'): array
    {
        return [
            'colors'  => [$color],
            'options' => [
                'chart'     => ['type' => $type, 'height' => 40, 'sparkline' => ['enabled' => true]],
                'series'    => [['name' => '', 'data' => array_map(static fn ($v): float => (float) $v, $values)]],
                'stroke'    => ['curve' => 'smooth', 'width' => 2],
                'fill'      => ['type' => 'gradient', 'gradient' => ['shade' => 'light', 'opacityFrom' => 0.45, 'opacityTo' => 0.02]],
                'tooltip'   => ['enabled' => true],
                'markers'   => ['size' => 0],
                'dataLabels' => ['enabled' => false],
                'xaxis'     => ['labels' => ['show' => false]],
                'yaxis'     => ['labels' => ['show' => false]],
            ],
        ];
    }

    /**
     * ApexCharts donut.
     *
     * @param list<string> $labels
     * @param list<float|int> $values
     * @param list<string> $colors explicit colours, aligned with `$labels`
     * @return array<string, mixed>
     */
    public static function donut(array $labels, array $values, array $colors, array $opts = []): array
    {
        return [
            'colors'  => array_values($colors),
            'options' => [
                'chart'   => ['type' => 'donut', 'height' => (int) ($opts['height'] ?? 240)],
                'labels'  => array_values($labels),
                'series'  => array_map(static fn ($v): float => (float) $v, $values),
                'legend'  => ['show' => true, 'position' => (string) ($opts['legend'] ?? 'bottom')],
                'stroke'  => ['width' => 0],
                'dataLabels' => ['enabled' => false],
                'plotOptions' => [
                    'pie' => [
                        'donut' => [
                            'size'      => (string) ($opts['size'] ?? '62%'),
                            'labels'    => [
                                'show'  => true,
                                'name'  => ['show' => false],
                                'value' => ['show' => true, 'fontSize' => '18px', 'fontWeight' => 800],
                                'total' => ['show' => true, 'showAlways' => true, 'label' => (string) ($opts['totalLabel'] ?? 'Total')],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * ApexCharts horizontal bar.
     *
     * @param list<string> $labels
     * @param list<float|int> $values
     * @return array<string, mixed>
     */
    public static function hbar(array $labels, array $values, array $colors, array $opts = []): array
    {
        return [
            'colors'  => array_values($colors),
            'options' => [
                'chart'       => ['type' => 'bar', 'height' => (int) ($opts['height'] ?? 240)],
                'series'      => [['name' => (string) ($opts['name'] ?? 'Value'), 'data' => array_map(static fn ($v): float => (float) $v, $values)]],
                'xaxis'       => ['categories' => array_values($labels)],
                'plotOptions' => ['bar' => ['horizontal' => true, 'borderRadius' => 4, 'barHeight' => '62%', 'distributed' => true]],
                'legend'      => ['show' => false],
                'stroke'      => ['width' => 0],
            ],
        ];
    }

    /**
     * ApexCharts radialBar (single-ratio gauges, e.g. product share).
     *
     * @param list<string> $labels
     * @param list<float|int> $values
     * @return array<string, mixed>
     */
    public static function radial(array $labels, array $values, array $colors, array $opts = []): array
    {
        return [
            'colors'  => array_values($colors),
            'options' => [
                'chart'       => ['type' => 'radialBar', 'height' => (int) ($opts['height'] ?? 240)],
                'labels'      => array_values($labels),
                'series'      => array_map(static fn ($v): float => (float) $v, $values),
                'legend'      => ['show' => true, 'position' => (string) ($opts['legend'] ?? 'bottom')],
                'plotOptions' => [
                    'radialBar' => [
                        'hollow'    => ['size' => '42%'],
                        'track'     => ['background' => 'var(--surface-3)'],
                        'dataLabels' => ['name' => ['show' => true], 'value' => ['show' => true]],
                    ],
                ],
                'stroke'      => ['lineCap' => 'round'],
            ],
        ];
    }

    /**
     * ApexCharts funnel — a real funnel shape for the traffic-to-sale path.
     *
     * @param list<string> $labels
     * @param list<float|int> $values
     * @return array<string, mixed>
     */
    public static function funnel(array $labels, array $values, array $opts = []): array
    {
        $data = [];
        foreach (array_values($labels) as $i => $label) {
            $data[] = ['x' => (string) $label, 'y' => (float) ($values[$i] ?? 0)];
        }

        return [
            'colors'  => array_values($opts['colors'] ?? ['var(--brand)']),
            'options' => [
                'chart'   => ['type' => 'funnel', 'height' => (int) ($opts['height'] ?? 260)],
                'series'  => [['name' => (string) ($opts['name'] ?? 'Stage'), 'data' => $data]],
                'labels'  => array_values($labels),
                'legend'  => ['show' => true, 'position' => 'bottom'],
                'plotOptions' => [
                    'funnel' => [
                        'isFunnel3d' => false,
                        'dynamicRectHeight' => false,
                    ],
                ],
                'dataLabels' => ['enabled' => true, 'formatter' => 'RAW'],
            ],
        ];
    }

    /**
     * ApexCharts heatmap.
     *
     * @param list<array{name: string, data: list<float|int>}> $series
     * @param list<string> $categories
     * @return array<string, mixed>
     */
    public static function heatmap(array $series, array $categories, array $opts = []): array
    {
        $out = [];
        foreach ($series as $s) {
            $out[] = [
                'name' => (string) ($s['name'] ?? ''),
                'data' => array_map(static fn ($v): float => (float) $v, $s['data'] ?? []),
            ];
        }

        return [
            'colors'  => array_values($opts['colors'] ?? ['var(--brand)']),
            'options' => [
                'chart'  => ['type' => 'heatmap', 'height' => (int) ($opts['height'] ?? 260)],
                'series' => $out,
                'xaxis'  => ['categories' => array_values($categories)],
                'legend' => ['show' => false],
                'stroke' => ['width' => 2, 'colors' => ['var(--surface)']],
                'plotOptions' => [
                    'heatmap' => [
                        'radius'           => 4,
                        'enableShades'     => true,
                        'shadeIntensity'   => 0.6,
                        'colorScale'       => ['ranges' => []],
                    ],
                ],
                'dataLabels' => ['enabled' => false],
            ],
        ];
    }

    /**
     * Chart.js scales with a soft grid and no chart junk.
     *
     * @return array<string, mixed>
     */
    private static function defaultScales(bool $dualAxis = false, bool $stacked = false): array
    {
        $scales = [
            'x' => [
                'grid'  => ['display' => false],
                'ticks' => ['maxRotation' => 0, 'autoSkipPadding' => 12],
            ],
            'y' => [
                'beginAtZero' => true,
                'grid'        => ['drawBorder' => false],
                'ticks'       => ['maxTicksLimit' => 5],
                'stacked'     => $stacked,
            ],
        ];

        if ($stacked) {
            $scales['x']['stacked'] = true;
        }

        if ($dualAxis) {
            $scales['y1'] = [
                'beginAtZero' => true,
                'position'    => 'right',
                'grid'        => ['display' => false],
                'ticks'       => ['maxTicksLimit' => 5],
            ];
        }

        return $scales;
    }

    /**
     * The colour palette for a product series, in Catalog order.
     *
     * @return list<string>
     */
    public static function productColors(): array
    {
        return array_values(Catalog::colors());
    }

    /** Safe JSON for a `data-chart-spec` attribute. */
    public static function json(array $spec): string
    {
        $json = json_encode($spec, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return htmlspecialchars($json === false ? '{}' : $json, ENT_QUOTES, 'UTF-8');
    }
}
