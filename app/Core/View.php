<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * View Renderer.
 * 
 * Lightweight template engine with:
 *  - Layout / partial composition
 *  - {{ $var }} and {{ $var|filter }} syntax
 *  - @if, @foreach, @include directives
 *  - Auto-escaping by default
 */
final class View
{
    public static function render(string $template, array $data = []): string
    {
        $app = Application::getInstance();
        $data = array_merge([
            'app'    => $app,
            'config' => $app->getConfig('app', []),
        ], $data);

        $path = self::resolve($template);
        if (!is_file($path)) {
            throw new \RuntimeException("View not found: $template ($path)");
        }
        return self::renderFile($path, $data);
    }

    public static function exists(string $template): bool
    {
        return is_file(self::resolve($template));
    }

    public static function resolve(string $template): string
    {
        $app = Application::getInstance();
        $relative = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $template);
        $base = $app->rootPath() . '/app/Views';
        $path = $base . DIRECTORY_SEPARATOR . $relative . '.php';
        return $path;
    }

    private static function renderFile(string $path, array $data): string
    {
        $code = file_get_contents($path);
        $code = self::compile($code);
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            eval('?>' . $code);
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return ob_get_clean() ?: '';
    }

    /**
     * Compile the view syntax into PHP.
     */
    private static function compile(string $code): string
    {
        // {{ $var|filter }} -> e($var, FILTER)
        $code = preg_replace_callback(
            '/\{\{\s*(.+?)\s*\}\}/s',
            function ($m) {
                $expr = trim($m[1]);
                if (str_contains($expr, '|')) {
                    [$var, $filter] = array_map('trim', explode('|', $expr, 2));
                    return '<?php echo e((string) (' . self::filterExp($filter, $var) . ')); ?>';
                }
                return '<?php echo e((string) (' . $expr . ')); ?>';
            },
            $code
        );

        // @{ ... } (raw, no escape)
        $code = preg_replace('/@\{\s*(.+?)\s*\}@/s', '<?php echo $1; ?>', $code);

        // @if / @elseif / @else / @endif
        $code = preg_replace('/@if\s*\((.*?)\)/', '<?php if ($1): ?>', $code);
        $code = preg_replace('/@elseif\s*\((.*?)\)/', '<?php elseif ($1): ?>', $code);
        $code = preg_replace('/@else\b/', '<?php else: ?>', $code);
        $code = preg_replace('/@endif\b/', '<?php endif; ?>', $code);

        // @foreach / @endforeach
        $code = preg_replace('/@foreach\s*\((.*?)\)/', '<?php foreach ($1): ?>', $code);
        $code = preg_replace('/@endforeach\b/', '<?php endforeach; ?>', $code);

        // @for / @endfor
        $code = preg_replace('/@for\s*\((.*?)\)/', '<?php for ($1): ?>', $code);
        $code = preg_replace('/@endfor\b/', '<?php endfor; ?>', $code);

        // @while / @endwhile
        $code = preg_replace('/@while\s*\((.*?)\)/', '<?php while ($1): ?>', $code);
        $code = preg_replace('/@endwhile\b/', '<?php endwhile; ?>', $code);

        // @include('template', [...])
        $code = preg_replace_callback(
            '/@include\s*\(\s*([\'"])(.+?)\1(.*?)\)/s',
            function ($m) {
                $template = $m[2];
                $args = trim($m[3]);
                $code = "<?php echo \\App\\Core\\View::render('$template', array_merge(get_defined_vars(), $args ? [$args] : [])); ?>";
                return $code;
            },
            $code
        );

        // @yield('name')
        $code = preg_replace('/@yield\s*\(\s*([\'"])(.+?)\1\s*\)/', '<?php echo $__sections[\'$2\'] ?? ""; ?>', $code);
        $code = preg_replace('/@yield\s*\(\s*([\'"])(.+?)\1\s*,\s*([\'"])(.+?)\3\s*\)/', '<?php echo $__sections[\'$2\'] ?? \'$4\'; ?>', $code);

        // @section / @endsection
        $code = preg_replace_callback(
            '/@section\s*\(\s*([\'"])(.+?)\1\s*\)(.*?)@endsection/s',
            function ($m) {
                $name = $m[2];
                $body = $m[3];
                return "<?php \$__sections['$name'] = \$__sections['$name'] ?? ''; ob_start(); ?>$body<?php \$__sections['$name'] = ob_get_clean(); ?>";
            },
            $code
        );

        // {{-- comment --}}
        $code = preg_replace('/\{\{--.*?--\}\}/s', '', $code);

        // PHP tags
        $code = preg_replace('/@php\b(.*?)@endphp/s', '<?php$1?>', $code);
        $code = preg_replace('/@csrf\b/', '<?php echo csrf_field(); ?>', $code);
        $code = preg_replace('/@method\s*\(\s*([\'"])(PUT|PATCH|DELETE)\1\s*\)/', '<?php echo method_field(\'$2\'); ?>', $code);

        return $code;
    }

    private static function filterExp(string $filter, string $var): string
    {
        $parts = explode('|', $filter);
        $expr = $var;
        foreach ($parts as $f) {
            $f = trim($f);
            if (str_contains($f, ':')) {
                [$name, $args] = explode(':', $f, 2);
                $args = "'" . str_replace(',', "','", $args) . "'";
                $expr = "$name($expr, $args)";
            } else {
                $expr = "$f($expr)";
            }
        }
        return $expr;
    }
}
