<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Security;

use Edulume\Core\Domain\Security\AttemptLog;
use Edulume\Core\Domain\Security\ClientIp;
use Edulume\Core\Domain\Security\LockoutPolicy;
use Edulume\Core\Domain\Security\LoginSlug;
use Edulume\Core\Domain\Security\ShieldSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The login shield's rules, tested where they are decidable.
 *
 * Every one of these is a way a real site gets broken: a slug that collides and makes the login
 * unreachable, a spoofed header that turns the lockout into either a no-op or a weapon, a log
 * that grows without bound, an integer overflow that reads as "not locked out".
 */
#[CoversClass(ShieldSettings::class)]
#[CoversClass(LoginSlug::class)]
#[CoversClass(LockoutPolicy::class)]
#[CoversClass(AttemptLog::class)]
#[CoversClass(ClientIp::class)]
final class LoginShieldTest extends TestCase
{
    public function testTheShieldIsOffUntilSomebodyTurnsItOn(): void
    {
        self::assertFalse(ShieldSettings::defaults()->enabled);
    }

    public function testStoredSettingsRoundTrip(): void
    {
        $settings = ShieldSettings::defaults()
            ->with('enabled', true)
            ->with('slug', 'quiet-door')
            ->with('maxAttempts', 3);

        self::assertSame($settings->toArray(), ShieldSettings::fromArray($settings->toArray())->toArray());
    }

    public function testNonsenseInTheSettingsRowFallsBackRatherThanBreakingTheLockout(): void
    {
        $settings = ShieldSettings::fromArray([
            'maxAttempts' => 'unlimited',
            'lockoutMinutes' => -12,
            'slug' => '',
            'allowlistIps' => 'not-a-list',
        ]);

        self::assertSame(5, $settings->maxAttempts);
        self::assertSame(1, $settings->lockoutMinutes);
        self::assertSame(ShieldSettings::DEFAULT_SLUG, $settings->slug);
        self::assertSame([], $settings->allowlistIps);
    }

    public function testTheLockoutWindowCannotBeSetBeyondADay(): void
    {
        self::assertSame(
            ShieldSettings::MAXIMUM_LOCKOUT_MINUTES,
            ShieldSettings::fromArray(['lockoutMinutes' => 99999])->lockoutMinutes
        );
    }

    public function testOnlyRealAddressesReachTheAllowlist(): void
    {
        $settings = ShieldSettings::fromArray([
            'allowlistIps' => ['203.0.113.7', 'nonsense', '2001:db8::1', '203.0.113.7'],
        ]);

        self::assertSame(['203.0.113.7', '2001:db8::1'], $settings->allowlistIps);
        self::assertTrue($settings->allows('203.0.113.7'));
        self::assertFalse($settings->allows('198.51.100.1'));
    }

    #[DataProvider('reservedWords')]
    public function testAReservedPathIsRejected(string $candidate): void
    {
        $proposed = LoginSlug::propose($candidate);

        self::assertFalse($proposed->isUsable(), $candidate . ' should be rejected');
        self::assertSame('reserved', $proposed->problem);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function reservedWords(): array
    {
        $cases = [];

        foreach (LoginSlug::RESERVED as $word) {
            $cases[$word] = [$word];
        }

        return $cases;
    }

    public function testCasingAndSlashesDoNotSmuggleAReservedPathPast(): void
    {
        foreach (['/wp-admin', 'WP-Admin/', 'wp-admin?redirect=1', '/wp-admin/users.php'] as $candidate) {
            self::assertSame('reserved', LoginSlug::propose($candidate)->problem, $candidate);
        }
    }

    public function testAPathTheSiteAlreadyUsesIsRejected(): void
    {
        $proposed = LoginSlug::propose('destinations', ['courses', 'destinations']);

        self::assertSame('taken', $proposed->problem);
    }

    public function testLengthIsBounded(): void
    {
        self::assertSame('empty', LoginSlug::propose('   ')->problem);
        self::assertSame('too-short', LoginSlug::propose('ab')->problem);
        self::assertSame('too-long', LoginSlug::propose(str_repeat('a', 51))->problem);
    }

    public function testAUsableSlugIsNormalisedAndAccepted(): void
    {
        $proposed = LoginSlug::propose('  Quiet Door!  ');

        self::assertTrue($proposed->isUsable());
        self::assertSame('quietdoor', $proposed->value);
        self::assertNull($proposed->problem);
    }

    public function testTheDefaultSlugIsItselfUsable(): void
    {
        self::assertTrue(LoginSlug::propose(ShieldSettings::DEFAULT_SLUG)->isUsable());
    }

    public function testTheCounterLocksOutAtTheConfiguredCap(): void
    {
        $policy = new LockoutPolicy(ShieldSettings::defaults());

        self::assertFalse($policy->isLockedOut(4));
        self::assertTrue($policy->isLockedOut(5));
        self::assertSame(1, $policy->remainingAttempts(4));
        self::assertSame(0, $policy->remainingAttempts(9));
    }

    public function testLimitingCanBeSwitchedOffEntirely(): void
    {
        $policy = new LockoutPolicy(ShieldSettings::defaults()->with('limitAttempts', false));

        self::assertFalse($policy->isLockedOut(500));
        self::assertSame(5, $policy->remainingAttempts(500));
    }

    public function testEachRepeatOffenceDoublesTheWindow(): void
    {
        $policy = new LockoutPolicy(ShieldSettings::defaults());

        self::assertSame(15, $policy->durationMinutes(0));
        self::assertSame(30, $policy->durationMinutes(1));
        self::assertSame(60, $policy->durationMinutes(2));
    }

    public function testTheWindowIsCappedAtADayHoweverManyOffences(): void
    {
        $policy = new LockoutPolicy(ShieldSettings::defaults());

        self::assertSame(ShieldSettings::MAXIMUM_LOCKOUT_MINUTES, $policy->durationMinutes(200));
        self::assertGreaterThan(0, $policy->durationMinutes(1000), 'an overflowed window reads as no lockout');
    }

    public function testProgressiveLockoutCanBeSwitchedOff(): void
    {
        $policy = new LockoutPolicy(ShieldSettings::defaults()->with('progressiveLockout', false));

        self::assertSame(15, $policy->durationMinutes(9));
    }

    public function testTheLogTrimsAtExactlyTwoHundred(): void
    {
        $log = AttemptLog::empty();

        for ($i = 0; $i < 250; $i++) {
            $log = $log->with('203.0.113.' . ($i % 250), 'user' . $i, '2026-08-16T00:00:00+00:00');
        }

        self::assertSame(AttemptLog::MAXIMUM_ENTRIES, $log->count());
        self::assertSame('user249', $log->entries[AttemptLog::MAXIMUM_ENTRIES - 1]['username']);
    }

    public function testALogRestoredFromNonsenseDoesNotCarryNonsenseForward(): void
    {
        $log = AttemptLog::fromArray(['not an entry', ['ip' => '203.0.113.7'], ['ip' => 1, 'at' => 2]]);

        self::assertSame(2, $log->count());
        self::assertSame('203.0.113.7', $log->entries[0]['ip']);
        self::assertSame('', $log->entries[0]['username']);
        self::assertSame('', $log->entries[1]['ip']);
    }

    public function testTheLogCountsFailuresPerAddress(): void
    {
        $log = AttemptLog::empty()
            ->with('203.0.113.7', 'a', 'now')
            ->with('198.51.100.1', 'b', 'now')
            ->with('203.0.113.7', 'c', 'now');

        self::assertSame(2, $log->failuresFrom('203.0.113.7'));
        self::assertSame(0, $log->failuresFrom('192.0.2.9'));
    }

    public function testALogTrimmedToTwoHundredSurvivesARoundTrip(): void
    {
        $log = AttemptLog::empty();

        for ($i = 0; $i < 205; $i++) {
            $log = $log->with('203.0.113.7', 'user', 'now');
        }

        self::assertSame(200, AttemptLog::fromArray($log->toArray())->count());
    }

    public function testTheClientAddressIsRemoteAddrByDefault(): void
    {
        $server = [
            'REMOTE_ADDR' => '203.0.113.7',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.1',
        ];

        self::assertSame('203.0.113.7', ClientIp::resolve($server));
    }

    public function testASpoofedForwardedHeaderIsIgnoredUntilAProxyIsDeclared(): void
    {
        $server = [
            'REMOTE_ADDR' => '203.0.113.7',
            'HTTP_X_FORWARDED_FOR' => 'not-an-address',
        ];

        self::assertSame('203.0.113.7', ClientIp::resolve($server, false));
        self::assertSame('203.0.113.7', ClientIp::resolve($server, true), 'a junk header must fall back');
    }

    public function testWithAProxyDeclaredTheLeftMostHopWins(): void
    {
        $server = [
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.7, 70.41.3.18, 10.0.0.1',
        ];

        self::assertSame('203.0.113.7', ClientIp::resolve($server, true));
    }

    public function testCloudflaresHeaderIsPreferredWhenPresent(): void
    {
        $server = [
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_CF_CONNECTING_IP' => '203.0.113.9',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.1',
        ];

        self::assertSame('203.0.113.9', ClientIp::resolve($server, true));
    }

    public function testARequestWithNoUsableAddressResolvesToNothing(): void
    {
        self::assertSame('', ClientIp::resolve([]));
        self::assertSame('', ClientIp::resolve(['REMOTE_ADDR' => ['array']]));
        self::assertSame('', ClientIp::resolve(['HTTP_X_REAL_IP' => 5], true));
    }

    public function testTheRealIpHeaderIsAlsoRead(): void
    {
        self::assertSame(
            '203.0.113.4',
            ClientIp::resolve(['REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_REAL_IP' => '203.0.113.4'], true)
        );
    }
}
