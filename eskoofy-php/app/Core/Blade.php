<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Minimal Blade-compatible template engine (compiler + runtime) so eskoofy-php
 * can reuse the exact Blade views from eskoofy-app without a framework.
 *
 * Supported surface (subset of Laravel Blade):
 *   @extends / @section / @endsection / @show / @yield / @parent
 *   @include / @includeIf / @includeWhen / @each
 *   @push / @prepend / @stack / @once
 *   @if @elseif @else @endif @unless @isset @empty
 *   @foreach @forelse @for @while @switch/@case/@break/@default
 *   @php / @endphp  @csrf @method @error @json @class @props
 *   @auth @guest @can @cannot @elsecan @canany @env
 *   {{ }} escaped echo, {!! !!} raw echo, {{-- --}} comments, @{{ }} literal
 *   <x-... /> anonymous components + <x-slot> + :attr binding + @props + {{ $attributes }}
 */
class Blade
{
    /** @var string Directory holding the Blade (.blade.php) templates. */
    public static string $viewRoot = '';

    /** @var string Compiled PHP cache directory. */
    public static string $compilePath = '';

    /** @var array<string,mixed> */
    public array $data = [];

    /** @var array<string,string> sections that have been set */
    protected array $sections = [];

    /** @var string[] */
    protected array $sectionStack = [];

    /** @var array<string,string> */
    protected array $pushes = [];

    /** @var array<string,array<int,string>> */
    protected array $prepends = [];

    /** @var string[] */
    protected array $pushStack = [];

    /** @var array<string,mixed> parent view to render after child */
    protected ?string $parent = null;

    /** @var array<string,mixed> */
    protected array $parentData = [];

    /** @var list<array{view:string,attributes:array<string,mixed>,data:array<string,mixed>,slots:array<string,string>}> */
    protected array $componentStack = [];

    /** @var array<string,mixed>|null component currently rendering */
    protected ?array $currentComponent = null;

    /** @var string[]|null */
    protected ?array $slotStack = null;

    /** @var array<string,array<string,mixed>> */
    protected array $loops = [];

    /** @var int[] */
    protected array $loopStack = [];

    /** @var array<string,string> parent-marker keyed by section */
    protected array $parentMarkers = [];

    /** @var array<string,true> used by @once */
    protected static array $onceSeen = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
        if (self::$viewRoot === '') {
            self::$viewRoot = dirname(__DIR__, 2) . '/resources/views';
        }
        if (self::$compilePath === '') {
            self::$compilePath = dirname(__DIR__, 2) . '/storage/framework/views';
        }
    }

    // ------------------------------------------------------------------
    // Public rendering API
    // ------------------------------------------------------------------

    public static function render(string $view, array $data = [], ?Blade $parentEnv = null, bool $forInclude = false): string
    {
        $env = $parentEnv ?? new self($data);
        $path = self::resolvePath($view);
        if ($path === null) {
            // Fall back to legacy PHP views handled by the View class.
            ob_start();
            \App\Core\View::partial($view, $data);
            return (string) ob_get_clean();
        }
        $compiled = self::compile($path);
        $env->data = array_merge($env->data, $data);

        ob_start();
        self::evaluate($compiled, $env, $env->data);
        $childOutput = ob_get_clean();

        if (!$forInclude && $env->parent !== null) {
            $parent = $env->parent;
            $env->parent = null;
            return self::render($parent, $env->data, $env);
        }

        return $childOutput;
    }

    /** Render an included sub-view reusing the current environment + scope vars. */
    public function make(string $view, array $data = [], array $mergeData = []): string
    {
        $scope = array_filter(
            array_merge($mergeData, $data),
            static fn ($k) => !str_starts_with((string) $k, '__') && $k !== 'env',
            ARRAY_FILTER_USE_KEY
        );
        return self::render($view, array_merge($this->data, $scope), $this, true);
    }

    public function include(string $view, array $data = [], array $mergeData = []): string
    {
        return $this->make($view, $data, $mergeData);
    }

    // ------------------------------------------------------------------
    // Sections (@extends / @section / @yield / @parent)
    // ------------------------------------------------------------------

    public function extend(string $view, array $data = []): void
    {
        $this->parent = $view;
        $this->parentData = $data;
    }

    public function startSection(string $name, ?string $content = null): void
    {
        if ($content === null) {
            if (ob_start()) {
                $this->sectionStack[] = $name;
            }
        } else {
            $this->sections[$name] = $content;
        }
    }

    public function stopSection(bool $overwrite = false): void
    {
        $last = array_pop($this->sectionStack);
        if ($last === null) {
            return;
        }
        $content = (string) ob_get_clean();
        $marker = $this->parentMarkers[$last] ?? null;
        if ($overwrite && array_key_exists($last, $this->sections)) {
            $this->sections[$last] = $content;
        } elseif ($marker !== null && isset($this->sections[$last])) {
            $this->sections[$last] = str_replace($marker, $this->sections[$last], $content);
        } else {
            $this->sections[$last] = $content;
        }
    }

    public function yieldSection(): string
    {
        $last = array_pop($this->sectionStack);
        if ($last === null) {
            return '';
        }
        $content = (string) ob_get_clean();
        $marker = $this->parentMarkers[$last] ?? null;
        if ($marker !== null && isset($this->sections[$last])) {
            $content = str_replace($marker, $this->sections[$last], $content);
        }
        $this->sections[$last] = $content;
        return $content;
    }

    public function yieldContent(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    public function parentPlaceholder(): string
    {
        $name = end($this->sectionStack) ?: 'content';
        $marker = '__PARENT__' . md5($name . mt_rand()) . '__';
        $this->parentMarkers[$name] = $marker;
        return $marker;
    }

    // ------------------------------------------------------------------
    // Stacks (@push / @prepend / @stack)
    // ------------------------------------------------------------------

    public function startPush(string $section): void
    {
        ob_start();
        $this->pushStack[] = $section;
    }

    public function stopPush(): void
    {
        $last = array_pop($this->pushStack);
        if ($last === null) {
            return;
        }
        $content = (string) ob_get_clean();
        $this->pushes[$last] = ($this->pushes[$last] ?? '') . $content;
    }

    public function startPrepend(string $section): void
    {
        ob_start();
        $this->pushStack[] = '@prepend:' . $section;
    }

    public function stopPrepend(): void
    {
        $last = array_pop($this->pushStack);
        if ($last === null || !str_starts_with($last, '@prepend:')) {
            return;
        }
        $content = (string) ob_get_clean();
        $name = substr($last, strlen('@prepend:'));
        $this->prepends[$name][] = $content;
    }

    public function yieldPushContent(string $section): string
    {
        return implode('', $this->prepends[$section] ?? []) . ($this->pushes[$section] ?? '');
    }

    // ------------------------------------------------------------------
    // Loops ($loop variable for @foreach / @forelse)
    // ------------------------------------------------------------------

    public function addLoop(mixed $data): void
    {
        $length = is_countable($data) ? count($data) : (is_iterable($data) ? iterator_count(new \ArrayIterator((array) $data)) : 0);
        $this->loopStack[] = $length;
    }

    public function incrementLoopIndices(): void
    {
        $index = count($this->loopStack) - 1;
        $this->loops[$index] = [
            'iteration' => ($this->loops[$index]['iteration'] ?? 0) + 1,
        ];
    }

    public function popLoop(): void
    {
        array_pop($this->loopStack);
        if (count($this->loopStack) === 0) {
            $this->loops = [];
        }
    }

    public function getLastLoop(): object
    {
        $index = count($this->loopStack) - 1;
        if (!isset($this->loopStack[$index])) {
            return (object) [
                'iteration' => 0, 'index' => 0, 'remaining' => 0, 'count' => 0,
                'first' => false, 'last' => false, 'even' => false, 'odd' => false,
            ];
        }
        $length = $this->loopStack[$index];
        $iteration = $this->loops[$index]['iteration'] ?? 0;
        return (object) [
            'iteration' => $iteration,
            'index'     => $iteration - 1,
            'remaining' => max(0, $length - $iteration),
            'count'     => $length,
            'first'     => $iteration === 1,
            'last'      => $length > 0 && $iteration === $length,
            'even'      => $iteration % 2 === 0,
            'odd'       => $iteration % 2 === 1,
            'depth'     => $index + 1,
        ];
    }

    // ------------------------------------------------------------------
    // Anonymous components (<x-...>)
    // ------------------------------------------------------------------

    public function startComponent(string $view, array $attributes = [], array $data = []): void
    {
        $this->componentStack[] = [
            'view'       => $view,
            'attributes' => $attributes,
            'data'       => $data,
            'slots'      => [],
        ];
        ob_start();
    }

    public function startSlot(string $name): void
    {
        ob_start();
        $this->slotStack[] = $name;
    }

    public function endSlot(): void
    {
        $name = array_pop($this->slotStack);
        if ($name === null) {
            return;
        }
        $content = (string) ob_get_clean();
        $index = count($this->componentStack) - 1;
        if ($index >= 0) {
            $this->componentStack[$index]['slots'][$name] = $content;
        }
    }

    public function renderComponent(): string
    {
        $slot = (string) ob_get_clean();
        if (count($this->componentStack) === 0) {
            return $slot;
        }
        $frame = array_pop($this->componentStack);
        $this->currentComponent = $frame;

        $data = $frame['data'];
        $data['slot'] = $slot;
        $data['attributes'] = new ComponentAttributeBag($frame['attributes']);
        $data['__componentAttributes'] = $frame['attributes'];
        foreach ($frame['slots'] as $name => $content) {
            $data[$name] = $content;
        }

        $result = self::render($frame['view'], $data, $this, true);
        $this->currentComponent = null;
        return $result;
    }

    /**
     * Merge @props([...]) defaults with provided attributes; the remaining
     * attributes stay available as $attributes.
     *
     * @return array<string,mixed>
     */
    public function componentProps(array $defaults): array
    {
        $frame = &$this->currentComponent;
        if ($frame === null) {
            return $defaults;
        }
        $attrs = $frame['attributes'];
        $merged = [];
        foreach ($defaults as $key => $value) {
            if (is_int($key)) {
                $key = $value;
                $value = null;
            }
            if (array_key_exists($key, $attrs)) {
                $merged[$key] = $attrs[$key];
                unset($attrs[$key]);
            } else {
                $merged[$key] = $value;
            }
        }
        $frame['attributes'] = $attrs;
        return $merged;
    }

    public function currentComponentAttributes(): array
    {
        return $this->currentComponent['attributes'] ?? [];
    }

    // ------------------------------------------------------------------
    // Small render helpers used by compiled templates
    // ------------------------------------------------------------------

    public function class(array $classes): string
    {
        $parts = [];
        foreach ($classes as $class => $condition) {
            if (is_int($class)) {
                $parts[] = $condition;
            } elseif ($condition) {
                $parts[] = $class;
            }
        }
        return implode(' ', array_unique(array_filter($parts)));
    }

    public function once(): bool
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $key = $trace[1]['file'] ?? '' . $trace[1]['line'] ?? '';
        if (isset(self::$onceSeen[$key])) {
            return false;
        }
        self::$onceSeen[$key] = true;
        return true;
    }

    public function vite(array $deps): string
    {
        return '';
    }

    public function getData(): array
    {
        return $this->data;
    }

    // ------------------------------------------------------------------
    // Compilation
    // ------------------------------------------------------------------

    public static function compile(string $path): string
    {
        if (self::$compilePath === '') {
            self::$compilePath = dirname(__DIR__, 2) . '/storage/framework/views';
        }
        if (!is_dir(self::$compilePath)) {
            @mkdir(self::$compilePath, 0775, true);
        }
        $key = md5($path);
        $compiled = self::$compilePath . '/' . $key . '.php';
        $mtime = (int) @filemtime($path);
        $stamp = (int) @filemtime($compiled);
        if (is_file($compiled) && $stamp >= $mtime) {
            return $compiled;
        }
        $source = (string) file_get_contents($path);
        $php = self::compileString($source);
        if (str_ends_with($php, "\n")) {
            $php .= "\n";
        }
        file_put_contents($compiled, "<?php /* {$path} */ ?>\n" . $php . "\n<?php /* eof */ ?>\n");
        @touch($compiled, $mtime);
        return $compiled;
    }

    public static function compileString(string $value): string
    {
        $value = preg_replace('/\{\{--(.*?)--\}\}/s', '', $value) ?? $value;

        // Components must be expanded before echos/directives so that attribute
        // expressions (:attr="$x") are not mangled by echo compilation.
        $value = self::compileComponents($value);

        $value = self::compileStatements($value);
        $value = self::compileEchos($value);
        return $value;
    }

    /** @var bool */
    protected static bool $firstCaseInSwitch = true;

    protected static function compileEchos(string $value): string
    {
        $result = '';
        $offset = 0;
        $len = strlen($value);

        while ($offset < $len) {
            $literalPos = strpos($value, '@{{', $offset);
            $rawPos = strpos($value, '{!!', $offset);
            $echoPos = strpos($value, '{{', $offset);

            $candidates = array_filter(
                ['literal' => $literalPos, 'raw' => $rawPos, 'echo' => $echoPos],
                static fn ($p) => $p !== false
            );
            if ($candidates === []) {
                $result .= substr($value, $offset);
                break;
            }
            asort($candidates);
            $type = array_key_first($candidates);
            $pos = $candidates[$type];

            $result .= substr($value, $offset, $pos - $offset);

            if ($type === 'literal') {
                $result .= '{{';
                $offset = $pos + 3;
                continue;
            }

            if ($type === 'raw') {
                $end = strpos($value, '!!}', $pos);
                if ($end === false) {
                    $result .= substr($value, $pos);
                    break;
                }
                $expr = trim(substr($value, $pos + 3, $end - $pos - 3));
                $result .= '<?php echo ' . $expr . '; ?>';
                $offset = $end + 3;
                continue;
            }

            // Regular echo: match balanced {{ ... }} so nested literals survive.
            $i = $pos + 2;
            $depth = 1;
            $quote = null;
            while ($i < $len) {
                $ch = $value[$i];
                if ($quote !== null) {
                    if ($ch === $quote && ($i === 0 || $value[$i - 1] !== '\\')) {
                        $quote = null;
                    }
                    $i++;
                    continue;
                }
                if ($ch === '"' || $ch === "'") {
                    $quote = $ch;
                    $i++;
                    continue;
                }
                if (substr($value, $i, 2) === '{{') {
                    $depth++;
                    $i += 2;
                    continue;
                }
                if (substr($value, $i, 2) === '}}') {
                    $depth--;
                    if ($depth === 0) {
                        break;
                    }
                    $i += 2;
                    continue;
                }
                $i++;
            }
            $expr = trim(substr($value, $pos + 2, $i - $pos - 2));
            $prev = $pos > 0 ? $value[$pos - 1] : '';
            $next = ($i + 2) < $len ? $value[$i + 2] : '';
            $isBareWord = preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $expr) === 1;
            if ($isBareWord && (($prev === "'" || $prev === '"') || ($next === "'" || $next === '"'))) {
                // Literal {{word}} inside a JS/HTML string, e.g. {{amount}}.
                $result .= substr($value, $pos, $i - $pos + 2);
                $offset = $i + 2;
                continue;
            }
            $result .= '<?php echo e(' . $expr . '); ?>';
            $offset = $i + 2;
        }

        return $result;
    }

    protected static function compileStatements(string $value): string
    {
        $pattern = '/\B@(@?\w+(?:::\w+)?)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x';
        return (string) preg_replace_callback($pattern, static function (array $m): string {
            $name = $m[1] ?? '';
            $expression = null;
            if (isset($m[3]) && $m[3] !== '') {
                // Strip exactly the outer parentheses (not nested ones).
                $expression = trim(substr($m[3], 1, -1));
            }
            return self::compileStatement($name, $expression);
        }, $value);
    }

    protected static function compileStatement(string $name, ?string $expression): string
    {
        $method = 'compile' . ucfirst($name);
        if (method_exists(self::class, $method)) {
            return self::$method($expression);
        }

        // Simple closing tokens / plain control flow without parentheses.
        return match ($name) {
            'else', 'endif', 'endunless', 'endisset', 'endempty', 'endauth',
            'endguest', 'endcan', 'endcannot', 'endcanany', 'enderror',
            'endswitch', 'endforeach', 'endforelse', 'endfor', 'endwhile'
                => self::compileClose($name),
            default => '@' . $name . ($expression !== null ? '(' . $expression . ')' : ''),
        };
    }

    protected static function compileClose(string $name): string
    {
        return match ($name) {
            'else'        => '<?php else: ?>',
            'endif'       => '<?php endif; ?>',
            'endunless'   => '<?php endif; ?>',
            'endisset'    => '<?php endif; ?>',
            'endempty'    => '<?php endif; ?>',
            'endauth'     => '<?php endif; ?>',
            'endguest'    => '<?php endif; ?>',
            'endcan'      => '<?php endif; ?>',
            'endcannot'   => '<?php endif; ?>',
            'endcanany'   => '<?php endif; ?>',
            'enderror'    => '<?php endif; ?>',
            'endswitch'   => '<?php endswitch; ?>',
            'endforeach'  => '<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>',
            'endforelse'  => '<?php endif; ?>',
            'endfor'      => '<?php endfor; ?>',
            'endwhile'    => '<?php endwhile; ?>',
            default       => '',
        };
    }

    protected static function compileIf(?string $e): string
    {
        return "<?php if({$e}): ?>";
    }

    protected static function compileElseif(?string $e): string
    {
        return "<?php elseif({$e}): ?>";
    }

    protected static function compileUnless(?string $e): string
    {
        return "<?php if(!({$e})): ?>";
    }

    protected static function compileIsset(?string $e): string
    {
        return "<?php if(isset({$e})): ?>";
    }

    protected static function compileEmpty(?string $e): string
    {
        if ($e !== null) {
            return "<?php if(empty({$e})): ?>";
        }
        return '<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>';
    }

    protected static function compileForeach(?string $e): string
    {
        [$iterable, $key, $value] = self::parseLoop($e);
        $as = $key !== null ? "{$key} => {$value}" : $value;
        return "<?php \$__currentLoopData = {$iterable}; \$__env->addLoop(\$__currentLoopData); foreach((is_iterable(\$__currentLoopData) ? \$__currentLoopData : []) as {$as}): \$__env->incrementLoopIndices(); \$loop = \$__env->getLastLoop(); ?>";
    }

    protected static function compileForelse(?string $e): string
    {
        [$iterable, $key, $value] = self::parseLoop($e);
        $as = $key !== null ? "{$key} => {$value}" : $value;
        return "<?php \$__empty_1 = true; \$__currentLoopData = {$iterable}; \$__env->addLoop(\$__currentLoopData); foreach((is_iterable(\$__currentLoopData) ? \$__currentLoopData : []) as {$as}): \$__env->incrementLoopIndices(); \$loop = \$__env->getLastLoop(); \$__empty_1 = false; ?>";
    }

    /**
     * Parse a @foreach/@forelse expression into [iterable, keyVar?, valueVar].
     *
     * @return array{0:string,1:?string,2:string}
     */
    protected static function parseLoop(?string $e): array
    {
        $e = (string) $e;
        $parts = preg_split('/\s+as\s+/i', $e, 2);
        $iterable = trim($parts[0] ?? '[]');
        $spec = trim($parts[1] ?? '');
        $key = null;
        $value = $spec;
        if (preg_match('/^(.+?)\s*=>\s*(.+)$/s', $spec, $m)) {
            $key = trim($m[1]);
            $value = trim($m[2]);
        }
        return [$iterable, $key, $value];
    }

    protected static function compileFor(?string $e): string
    {
        return "<?php for({$e}): ?>";
    }

    protected static function compileWhile(?string $e): string
    {
        return "<?php while({$e}): ?>";
    }

    protected static function compileSwitch(?string $e): string
    {
        self::$firstCaseInSwitch = true;
        return "<?php switch({$e}):";
    }

    protected static function compileCase(?string $e): string
    {
        if (self::$firstCaseInSwitch) {
            self::$firstCaseInSwitch = false;
            return "case {$e}: ?>";
        }
        return "<?php case {$e}: ?>";
    }

    protected static function compileBreak(?string $e = null): string
    {
        return $e ? "<?php if({$e}) break; ?>" : '<?php break; ?>';
    }

    protected static function compileDefault(?string $e = null): string
    {
        return '<?php default: ?>';
    }

    protected static function compilePhp(?string $e): string
    {
        if ($e !== null) {
            return "<?php {$e}; ?>";
        }
        return '<?php ';
    }

    protected static function compileEndphp(?string $e = null): string
    {
        return ' ?>';
    }

    protected static function compileCsrf(?string $e = null): string
    {
        return '<?php echo csrf_field(); ?>';
    }

    protected static function compileMethod(?string $e): string
    {
        return "<?php echo method_field({$e}); ?>";
    }

    protected static function compileError(?string $e): string
    {
        return "<?php \$__errorArgs = [{$e}]; \$__bag = \$errors ?? new \\App\\Core\\ViewErrorBag([]); if (\$__bag->has(\$__errorArgs[0])): ?>";
    }

    protected static function compileEnderror(?string $e = null): string
    {
        return '<?php endif; ?>';
    }

    protected static function compileAuth(?string $e = null): string
    {
        return "<?php if(auth()->check()): ?>";
    }

    protected static function compileGuest(?string $e = null): string
    {
        return "<?php if(!auth()->check()): ?>";
    }

    protected static function compileCan(?string $e): string
    {
        return "<?php if(\\App\\Core\\Gate::allows({$e})): ?>";
    }

    protected static function compileCannot(?string $e): string
    {
        return "<?php if(!\\App\\Core\\Gate::allows({$e})): ?>";
    }

    protected static function compileElsecan(?string $e): string
    {
        return "<?php elseif(\\App\\Core\\Gate::allows({$e})): ?>";
    }

    protected static function compileElsecannot(?string $e): string
    {
        return "<?php elseif(!\\App\\Core\\Gate::allows({$e})): ?>";
    }

    protected static function compileCanany(?string $e): string
    {
        return "<?php if(\\App\\Core\\Gate::any({$e})): ?>";
    }

    protected static function compileJson(?string $e): string
    {
        return "<?php echo json_encode({$e}); ?>";
    }

    protected static function compileClass(?string $e): string
    {
        return "<?php echo \$__env->class({$e}); ?>";
    }

    protected static function compileProps(?string $e): string
    {
        return "<?php \$__props = \$__env->componentProps({$e}); extract(\$__props, EXTR_SKIP); \$attributes = new \\App\\Core\\ComponentAttributeBag(\$__env->currentComponentAttributes()); ?>";
    }

    protected static function compileInclude(?string $e): string
    {
        return "<?php echo \$__env->include({$e}, get_defined_vars()); ?>";
    }

    protected static function compileIncludeIf(?string $e): string
    {
        $parts = self::splitArguments($e);
        $view = $parts[0] ?? "''";
        $extra = count($parts) > 1 ? implode(', ', array_slice($parts, 1)) : '';
        $call = $view . ($extra !== '' ? ', ' . $extra : '');
        return "<?php if (\$__env->includeExists({$view})) echo \$__env->include({$call}); ?>";
    }

    protected static function compileIncludeWhen(?string $e): string
    {
        // @includeWhen($cond, 'view', [...]) — split first arg off the expression.
        $parts = self::splitArguments($e);
        $cond = array_shift($parts);
        $call = implode(', ', $parts);
        return "<?php if({$cond}) echo \$__env->include({$call}); ?>";
    }

    protected static function compileIncludeUnless(?string $e): string
    {
        $parts = self::splitArguments($e);
        $cond = array_shift($parts);
        $call = implode(', ', $parts);
        return "<?php if(!({$cond})) echo \$__env->include({$call}); ?>";
    }

    protected static function compileEach(?string $e): string
    {
        return "<?php echo \$__env->renderEach({$e}); ?>";
    }

    protected static function compileExtends(?string $e): string
    {
        return "<?php \$__env->extend({$e}); ?>";
    }

    protected static function compileSection(?string $e): string
    {
        $args = self::splitArguments($e);
        $name = $args[0] ?? "''";
        if (count($args) > 1) {
            return "<?php \$__env->startSection({$name}, {$args[1]}); ?>";
        }
        return "<?php \$__env->startSection({$name}); ?>";
    }

    protected static function compileEndsection(?string $e = null): string
    {
        return '<?php $__env->stopSection(); ?>';
    }

    protected static function compileStop(?string $e = null): string
    {
        return '<?php $__env->stopSection(); ?>';
    }

    protected static function compileShow(?string $e = null): string
    {
        return '<?php echo $__env->yieldSection(); ?>';
    }

    protected static function compileParent(?string $e = null): string
    {
        return '<?php echo $__env->parentPlaceholder(); ?>';
    }

    protected static function compileYield(?string $e): string
    {
        $args = self::splitArguments($e);
        $name = $args[0] ?? "''";
        $default = $args[1] ?? "''";
        return "<?php echo \$__env->yieldContent({$name}, {$default}); ?>";
    }

    protected static function compilePush(?string $e): string
    {
        return "<?php \$__env->startPush({$e}); ?>";
    }

    protected static function compileEndpush(?string $e = null): string
    {
        return '<?php $__env->stopPush(); ?>';
    }

    protected static function compilePrepend(?string $e): string
    {
        return "<?php \$__env->startPrepend({$e}); ?>";
    }

    protected static function compileEndprepend(?string $e = null): string
    {
        return '<?php $__env->stopPrepend(); ?>';
    }

    protected static function compileStack(?string $e): string
    {
        return "<?php echo \$__env->yieldPushContent({$e}); ?>";
    }

    protected static function compileOnce(?string $e = null): string
    {
        return '<?php if($__env->once()): ?>';
    }

    protected static function compileEndonce(?string $e = null): string
    {
        return '<?php endif; ?>';
    }

    protected static function compileVite(?string $e): string
    {
        return "<?php echo \$__env->vite({$e}); ?>";
    }

    protected static function compileEnv(?string $e): string
    {
        return "<?php if(env({$e})): ?>";
    }

    protected static function compileEndenv(?string $e = null): string
    {
        return '<?php endif; ?>';
    }

    protected static function compileProduction(?string $e = null): string
    {
        return "<?php if(env('APP_ENV') === 'production'): ?>";
    }

    protected static function compileEndproduction(?string $e = null): string
    {
        return '<?php endif; ?>';
    }

    // ------------------------------------------------------------------
    // Components
    // ------------------------------------------------------------------

    protected static function compileComponents(string $value): string
    {
        $result = '';
        $offset = 0;
        $len = strlen($value);

        while ($offset < $len) {
            $openPos = strpos($value, '<x-', $offset);
            $closePos = strpos($value, '</x-', $offset);
            $next = null;
            $isClose = false;
            if ($openPos !== false && ($closePos === false || $openPos < $closePos)) {
                $next = $openPos;
            } elseif ($closePos !== false) {
                $next = $closePos;
                $isClose = true;
            }
            if ($next === null) {
                $result .= substr($value, $offset);
                break;
            }

            $result .= substr($value, $offset, $next - $offset);
            $end = self::findTagEnd($value, $next);
            if ($end === false) {
                $result .= substr($value, $next);
                break;
            }
            $tag = substr($value, $next, $end - $next + 1);
            $result .= self::compileTag($tag, $isClose);
            $offset = $end + 1;
        }

        return $result;
    }

    /**
     * Find the closing ">" of a tag, ignoring ">" inside quoted attribute values.
     */
    protected static function findTagEnd(string $value, int $start): int|false
    {
        $len = strlen($value);
        $quote = null;
        for ($i = $start + 1; $i < $len; $i++) {
            $ch = $value[$i];
            if ($quote !== null) {
                if ($ch === $quote && $value[$i - 1] !== '\\') {
                    $quote = null;
                }
                continue;
            }
            if ($ch === '"' || $ch === "'") {
                $quote = $ch;
                continue;
            }
            if ($ch === '>') {
                return $i;
            }
        }
        return false;
    }

    protected static function compileTag(string $tag, bool $isClose): string
    {
        if ($isClose) {
            $inner = trim(substr($tag, 2, -1));
            if ($inner === 'slot') {
                return '<?php $__env->endSlot(); ?>';
            }
            return '<?php echo $__env->renderComponent(); ?>';
        }

        $inner = trim(substr($tag, 1, -1));
        $selfClosing = false;
        if (str_ends_with($inner, '/')) {
            $selfClosing = true;
            $inner = rtrim(substr($inner, 0, -1));
        }
        if ($inner === '') {
            return '';
        }

        $spacePos = strcspn($inner, " \t\n\r");
        $name = substr($inner, 0, $spacePos);
        $attributes = trim(substr($inner, $spacePos));

        // <x-slot:name> shorthand
        if (str_starts_with($name, 'x-slot:')) {
            $slotName = substr($name, strlen('x-slot:'));
            $php = '<?php $__env->startSlot(' . var_export($slotName, true) . '); ?>';
            if ($selfClosing) {
                $php .= '<?php echo $__env->endSlot(); ?>';
            }
            return $php;
        }

        if ($name === 'x-slot') {
            $attrs = self::parseAttributes($attributes);
            $slotName = $attrs['name'] ?? 'default';
            unset($attrs['name']);
            $php = '<?php $__env->startSlot(' . var_export((string) $slotName, true) . '); ?>';
            if ($selfClosing) {
                $php .= '<?php echo $__env->endSlot(); ?>';
            }
            return $php;
        }

        if (!str_starts_with($name, 'x-')) {
            return $tag;
        }

        $component = substr($name, 2);
        $attrs = self::parseAttributes($attributes);
        $php = '<?php $__env->startComponent(' . var_export('components.' . $component, true) . ', [' . self::attributesToPhp($attrs) . ']); ?>';
        if ($selfClosing) {
            $php .= '<?php echo $__env->renderComponent(); ?>';
        }
        return $php;
    }

    /**
     * @return array<string,mixed>
     */
    protected static function parseAttributes(string $string): array
    {
        $string = trim($string);
        $attrs = [];
        if ($string === '') {
            return $attrs;
        }
        $regex = '/(?<name>[:@]?[a-zA-Z0-9_@.\-]+)(?:=(?<value>"[^"]*"|\'[^\']*\'|[^\s>]+))?/s';
        preg_match_all($regex, $string, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $name = $m['name'];
            if (!isset($m['value']) || $m['value'] === '') {
                $attrs[$name] = true;
                continue;
            }
            $val = $m['value'];
            $attrs[$name] = $val;
        }
        return $attrs;
    }

    /**
     * Convert parsed attributes into a PHP array literal (with `:name` bound to
     * the PHP expression and `::name` escaped to a literal attribute).
     */
    protected static function attributesToPhp(array $attrs): string
    {
        $parts = [];
        foreach ($attrs as $name => $value) {
            if ($name === 'name' && $value !== true && str_starts_with((string) $value, "'")) {
                // already a PHP string literal from parseAttributes quoting
            }
            if ($value === true) {
                $parts[] = var_export((string) $name, true) . ' => true';
                continue;
            }
            $val = (string) $value;
            if (str_starts_with($name, '::')) {
                // Escaped literal attribute: "::class" -> class="..."
                $parts[] = var_export(substr($name, 1), true) . ' => ' . $val;
            } elseif (str_starts_with($name, ':')) {
                // Bound attribute: ":title='$x'" -> 'title' => $x
                $parts[] = var_export(substr($name, 1), true) . ' => ' . self::unquoteBinding($val);
            } else {
                $parts[] = var_export((string) $name, true) . ' => ' . $val;
            }
        }
        return implode(', ', $parts);
    }

    protected static function unquoteBinding(string $value): string
    {
        // Value comes from parseAttributes as a quoted string ("..." or '...').
        if ((str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            return substr($value, 1, -1);
        }
        return $value;
    }

    /**
     * Split a PHP expression argument list at top-level commas (handles nested
     * parens / brackets / strings).
     *
     * @return list<string>
     */
    protected static function splitArguments(string $expr): array
    {
        $args = [];
        $depth = 0;
        $current = '';
        $len = strlen($expr);
        $quote = null;
        for ($i = 0; $i < $len; $i++) {
            $ch = $expr[$i];
            if ($quote !== null) {
                $current .= $ch;
                if ($ch === $quote && ($i === 0 || $expr[$i - 1] !== '\\')) {
                    $quote = null;
                }
                continue;
            }
            if ($ch === '"' || $ch === "'") {
                $quote = $ch;
                $current .= $ch;
                continue;
            }
            if ($ch === '(' || $ch === '[' || $ch === '{') {
                $depth++;
            } elseif ($ch === ')' || $ch === ']' || $ch === '}') {
                $depth--;
            }
            if ($ch === ',' && $depth === 0) {
                $args[] = trim($current);
                $current = '';
                continue;
            }
            $current .= $ch;
        }
        if (trim($current) !== '') {
            $args[] = trim($current);
        }
        return $args;
    }

    // ------------------------------------------------------------------
    // Path / evaluation helpers
    // ------------------------------------------------------------------

    public static function resolvePath(string $view): ?string
    {
        if (self::$viewRoot === '') {
            self::$viewRoot = dirname(__DIR__, 2) . '/resources/views';
        }
        $name = str_replace(['.', '::'], '/', $view);
        $candidates = [
            self::$viewRoot . '/' . $name . '.blade.php',
            self::$viewRoot . '/' . $name . '.php',
        ];
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }
        // Hyphen/underscore tolerant lookup (legacy port naming).
        $tolerant = str_replace('_', '-', $name);
        $alt = self::$viewRoot . '/' . $tolerant . '.blade.php';
        if (is_file($alt)) {
            return $alt;
        }
        $alt2 = self::$viewRoot . '/' . str_replace('-', '_', $name) . '.blade.php';
        if (is_file($alt2)) {
            return $alt2;
        }
        return null;
    }

    public function viewPath(string $view): string
    {
        return self::resolvePath($view) ?? '';
    }

    public function includeExists(string $view): bool
    {
        return self::resolvePath($view) !== null;
    }

    public function renderEach(string $view, array $data, string $iterator, string $empty = 'raw|'): string
    {
        $result = '';
        foreach ($data as $key => $item) {
            $result .= self::render($view, array_merge($this->data, [$iterator => $item]), $this, true);
        }
        if (trim($result) === '' && $empty !== 'raw|') {
            $parts = explode('|', $empty, 2);
            if (count($parts) === 2) {
                $result = $parts[1];
            }
        }
        return $result;
    }

    protected static function evaluate(string $compiled, Blade $env, array $data): void
    {
        $__env = $env;
        $__data = $data;
        extract($data, EXTR_SKIP);
        require $compiled;
    }
}