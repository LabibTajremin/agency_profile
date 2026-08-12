<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The integration suite gains its WordPress bootstrap in Phase 10, when there is
 * infrastructure worth booting WordPress for. Until then it proves only that the
 * second suite is wired into `composer check` and reports separately from the unit suite.
 */
final class PipelineSmokeTest extends TestCase
{
    #[Test]
    public function it_runs_the_integration_suite_as_a_separate_suite(): void
    {
        $this->assertDirectoryExists(dirname(__DIR__, 2) . '/src/Infrastructure');
    }
}
