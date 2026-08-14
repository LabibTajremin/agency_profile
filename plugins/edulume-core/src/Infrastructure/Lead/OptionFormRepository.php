<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Lead;

use Edulume\Core\Application\Port\FormRepository;
use Edulume\Core\Domain\Lead\FormDefinition;

/**
 * Forms stored in one option.
 *
 * A site has a handful of forms, not thousands, and they are read on nearly every page render —
 * so one autoloaded option is a single cached fetch, where a custom post type would be a query
 * per form per page.
 */
final class OptionFormRepository implements FormRepository
{
    public const OPTION = 'edulume_forms';

    public function find(string $slug): ?FormDefinition
    {
        foreach ($this->all() as $form) {
            if ($form->id === $slug) {
                return $form;
            }
        }

        return null;
    }

    public function all(): array
    {
        $stored = get_option(self::OPTION, []);

        if (!is_array($stored)) {
            return [];
        }

        $forms = [];

        foreach ($stored as $row) {
            if (is_array($row)) {
                $forms[] = FormDefinition::fromArray($row);
            }
        }

        return $forms;
    }

    public function save(FormDefinition $form): void
    {
        $rows = [];
        $replaced = false;

        foreach ($this->all() as $existing) {
            if ($existing->id === $form->id) {
                $rows[] = $form->toArray();
                $replaced = true;

                continue;
            }

            $rows[] = $existing->toArray();
        }

        if (!$replaced) {
            $rows[] = $form->toArray();
        }

        update_option(self::OPTION, $rows);
    }
}
