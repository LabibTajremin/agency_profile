<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Color;

use InvalidArgumentException;

final class InvalidColorException extends InvalidArgumentException
{
    public static function forHex(string $hex): self
    {
        return new self(sprintf('"%s" is not a valid hex colour; expected #rgb or #rrggbb.', $hex));
    }

    public static function forChannel(string $channel, float $value): self
    {
        return new self(sprintf('The %s channel must lie between 0 and 1; got %.6F.', $channel, $value));
    }

    public static function forEmptyCandidateList(): self
    {
        return new self('At least one foreground candidate is required.');
    }

    public static function forStepIndex(int $index, int $stepCount): self
    {
        return new self(sprintf('Step %d does not exist; the scale has %d steps.', $index, $stepCount));
    }
}
