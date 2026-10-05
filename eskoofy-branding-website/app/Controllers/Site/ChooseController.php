<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Models\Page;
use App\Services\I18n;
use App\Services\ProductMatrix;
use App\Services\ProductRecommender;
use App\Services\VariantResolver;

class ChooseController extends Controller
{
    /**
     * The six quiz questions, in order.
     *
     * `market` is the variant axis — the quiz cannot name a product x variant
     * pairing without it, so it is required and validated first. Public so the
     * form and the rules cannot drift apart.
     */
    public const RULES = [
        'market'   => 'required|in:bd,int',
        'size'     => 'required|in:small,medium,large',
        'comfort'  => 'required|in:non_technical,some,technical',
        'hosting'  => 'required|in:shared,vps,cloud,wordpress',
        'stack'    => 'required|in:any,php,javascript',
        'priority' => 'required|in:budget,features,control',
    ];

    /** The option lists, in the order the form renders them. */
    public const OPTIONS = [
        'market'   => ['bd', 'int'],
        'size'     => ['small', 'medium', 'large'],
        'comfort'  => ['non_technical', 'some', 'technical'],
        'hosting'  => ['shared', 'vps', 'cloud', 'wordpress'],
        'stack'    => ['any', 'php', 'javascript'],
        'priority' => ['budget', 'features', 'control'],
    ];

    public function show(): void
    {
        $this->view('site.choose', [
            'cmsPage'   => $this->cmsPage('/choose'),
            'markets'   => self::OPTIONS['market'],
            'sizes'     => self::OPTIONS['size'],
            'comforts'  => self::OPTIONS['comfort'],
            'hostings'  => self::OPTIONS['hosting'],
            'stacks'    => self::OPTIONS['stack'],
            'priorities'=> self::OPTIONS['priority'],
        ]);
    }

    public function result(): void
    {
        $validator = $this->validate(self::RULES);

        $answers = array_merge($validator, [
            'market'   => $validator['market'] ?? VariantResolver::INT,
            'size'     => $validator['size'] ?? '',
            'comfort'  => $validator['comfort'] ?? '',
            'hosting'  => $validator['hosting'] ?? '',
            'stack'    => $validator['stack'] ?? '',
            'priority' => $validator['priority'] ?? '',
        ]);

        $result = ProductRecommender::recommend($answers);
        $candidates = ProductRecommender::candidates();

        // Keep the blank slate the visitor already filled in.
        $this->view('site.choose-result', [
            'cmsPage'   => $this->cmsPage('/choose'),
            'result'    => $result,
            'candidates'=> $candidates,
            'answer'    => $answers,
            // The compact 4x2 with the winning cell ringed — the quiz output is
            // one cell of the same matrix `/products` shows in full.
            'matrix'    => ProductMatrix::build(),
        ]);
    }

    private function cmsPage(string $path): ?array
    {
        $row = Page::forPath($path);

        return $row !== null ? Page::localized($row, I18n::current()) : null;
    }
}
