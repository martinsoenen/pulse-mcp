<?php

namespace MartinSoenen\PulseMcp\Tests;

use PHPUnit\Framework\TestCase;

class StylesheetTest extends TestCase
{
    /**
     * Pulse ships a precompiled Tailwind stylesheet, so a utility class its own
     * views never use simply has no effect. That is how text-red-500 once
     * rendered the error counts in plain white.
     */
    public function test_every_class_used_by_the_cards_exists_in_the_pulse_stylesheet(): void
    {
        $stylesheet = file_get_contents(__DIR__.'/../vendor/laravel/pulse/dist/pulse.css');

        foreach (glob(__DIR__.'/../resources/views/livewire/*.blade.php') as $view) {
            foreach ($this->classesUsedIn(file_get_contents($view)) as $class) {
                $selector = '.'.preg_replace('/([:@\[\]\/.%])/', '\\\\$1', $class);

                $this->assertStringContainsString(
                    $selector,
                    $stylesheet,
                    "The class [{$class}] used in ".basename($view)." does not exist in Pulse's stylesheet.",
                );
            }
        }
    }

    /**
     * Collect the static classes of a view, including the keys of its @class directives.
     *
     * @return list<string>
     */
    private function classesUsedIn(string $view): array
    {
        preg_match_all('/(?<![:\w-])class="([^"{]*)"/', $view, $attributes);
        preg_match_all('/@class\(\[(.*?)\]\)/s', $view, $directives);
        preg_match_all("/'([^']+)'\s*=>/", implode(' ', $directives[1]), $conditionalClasses);

        $classes = implode(' ', [...$attributes[1], ...$conditionalClasses[1]]);

        return array_values(array_unique(preg_split('/\s+/', $classes, -1, PREG_SPLIT_NO_EMPTY)));
    }
}
