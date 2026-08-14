<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Port;

use Edulume\Core\Domain\Lead\FormDefinition;

/**
 * The forms a site has built.
 *
 * A port because the submission endpoint has to load a form by slug before it can validate
 * anything, and that lookup is the one thing standing between a public endpoint and a
 * request-shaped form definition.
 */
interface FormRepository
{
    public function find(string $slug): ?FormDefinition;

    /**
     * @return list<FormDefinition>
     */
    public function all(): array;

    public function save(FormDefinition $form): void;
}
