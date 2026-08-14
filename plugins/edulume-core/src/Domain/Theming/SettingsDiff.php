<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * What an import would change, key by key, before it changes anything.
 *
 * Importing a settings file blind is how a site owner discovers at 6pm that the export they
 * were handed also carried someone else's fonts. The diff is shown first, in full.
 */
final class SettingsDiff
{
    private const PATH_SEPARATOR = '.';

    /**
     * @param array<string, array{before:mixed, after:mixed}> $changes
     */
    private function __construct(private readonly array $changes)
    {
    }

    /**
     * @param array<array-key, mixed> $current
     * @param array<array-key, mixed> $incoming
     */
    public static function between(array $current, array $incoming): self
    {
        return new self(self::compare($current, $incoming, ''));
    }

    /**
     * @return array<string, array{before:mixed, after:mixed}>
     */
    public function changes(): array
    {
        return $this->changes;
    }

    /**
     * @return list<string>
     */
    public function changedKeys(): array
    {
        return array_keys($this->changes);
    }

    public function isEmpty(): bool
    {
        return $this->changes === [];
    }

    public function count(): int
    {
        return count($this->changes);
    }

    public function touches(string $path): bool
    {
        foreach ($this->changedKeys() as $key) {
            if ($key === $path || str_starts_with($key, $path . self::PATH_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<array-key, mixed> $current
     * @param array<array-key, mixed> $incoming
     *
     * @return array<string, array{before:mixed, after:mixed}>
     */
    private static function compare(array $current, array $incoming, string $prefix): array
    {
        $changes = [];

        foreach (self::allKeys($current, $incoming) as $key) {
            $path = $prefix === '' ? (string) $key : $prefix . self::PATH_SEPARATOR . $key;
            $before = $current[$key] ?? null;
            $after = $incoming[$key] ?? null;

            if (is_array($before) && is_array($after)) {
                $changes = array_merge($changes, self::compare($before, $after, $path));

                continue;
            }

            if ($before !== $after) {
                $changes[$path] = ['before' => $before, 'after' => $after];
            }
        }

        return $changes;
    }

    /**
     * @param array<array-key, mixed> $current
     * @param array<array-key, mixed> $incoming
     *
     * @return list<array-key>
     */
    private static function allKeys(array $current, array $incoming): array
    {
        return array_values(array_unique(array_merge(array_keys($current), array_keys($incoming))));
    }
}
