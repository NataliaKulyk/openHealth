<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BladeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->addNonemptyDirective();
        $this->addIconDirective();
    }

    protected function addNonemptyDirective(): void
    {
        Blade::directive('nonempty', static fn ($expression) => "<?php if(!empty($expression)): ?>");

        Blade::directive('elsenonempty', static fn () => "<?php else: ?>");

        Blade::directive('endnonempty', static fn () => "<?php endif; ?>");
    }

    /**
     * Expression will be something like: @icon('bell', 'w-6 h-6')
     *
     * @return void
     */
    protected function addIconDirective(): void
    {
        Blade::directive('icon', static function (string $expression) {
            return "<?php
                // Parse arguments from the directive
                \$iconArgs = [$expression];
                \$iconName = trim(\$iconArgs[0], \"'\\\"\");
                \$iconClass = \$iconArgs[1] ?? '';
                \$iconFile = resource_path('icons/' . \$iconName . '.svg');
                \$svgContent = file_exists(\$iconFile) ? file_get_contents(\$iconFile) : '';
                if (\$svgContent) {
                    \$svgContent = str_replace(['fill=\"white\"', 'fill=\'white\'', 'stroke=\"white\"', 'stroke=\'white\'', 'fill=\"#ffffff\"', 'fill=\'#ffffff\'', 'stroke=\"#ffffff\"', 'stroke=\'#ffffff\''], ['fill=\"currentColor\"', 'fill=\"currentColor\"', 'stroke=\"currentColor\"', 'stroke=\"currentColor\"', 'fill=\"currentColor\"', 'fill=\"currentColor\"', 'stroke=\"currentColor\"', 'stroke=\"currentColor\"'], \$svgContent);
                    if (\$iconClass) {
                        \$svgContent = preg_replace('/\\s*class=[\"\\'][^\"\\']*[\"\\']/i', '', \$svgContent);
                        \$svgContent = preg_replace(
                            '/<svg([^>]*)>/i',
                            '<svg$1 class=\"' . e(\$iconClass) . '\">',
                            \$svgContent,
                            1
                        );
                    }
                }
                echo \$svgContent;
            ?>";
        });
    }
}
