<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PipelineSmokeTest extends TestCase
{
    #[Test]
    public function it_runs_the_unit_suite_without_a_wordpress_bootstrap(): void
    {
        $this->assertFalse(
            function_exists('add_action'),
            'The unit suite must run with no WordPress loaded; the domain layer may never depend on it.'
        );
    }

    #[Test]
    public function it_autoloads_the_plugin_namespace(): void
    {
        /** @var array<string, list<string>> $autoloadedPrefixes */
        $autoloadedPrefixes = require dirname(__DIR__, 4) . '/vendor/composer/autoload_psr4.php';

        $this->assertArrayHasKey('Edulume\\Core\\', $autoloadedPrefixes);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function layerProvider(): array
    {
        return [
            'domain' => ['Domain'],
            'application' => ['Application'],
            'infrastructure' => ['Infrastructure'],
            'admin' => ['Admin'],
        ];
    }

    #[Test]
    #[DataProvider('layerProvider')]
    public function it_keeps_the_four_layer_directories_in_place(string $layer): void
    {
        $this->assertDirectoryExists(dirname(__DIR__, 2) . '/src/' . $layer);
    }
}
