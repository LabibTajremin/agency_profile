<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * The home page's section order, reconciled with the sections that exist.
 *
 * A stored order is a snapshot of the sections the product had on the day the owner dragged
 * them. Upgrades add sections and, occasionally, retire one — so replaying the stored list
 * verbatim either drops the new section off the page entirely or renders a slug with no
 * template behind it. Both look like the upgrade broke the site.
 *
 * So: stored slugs first, in the owner's order, unknown ones discarded; then anything the
 * product has gained since, put back in its default position relative to what survived.
 */
final class SectionOrder
{
    /**
     * @param list<string> $stored
     * @return list<string>
     */
    public static function reconcile(array $stored): array
    {
        $known = array_map(
            static fn (SectionId $section): string => $section->value,
            SectionId::homePageOrder()
        );

        $kept = array_values(array_filter(
            array_unique($stored),
            static fn (string $slug): bool => in_array($slug, $known, true)
        ));

        return self::reinstate($known, $kept);
    }

    /**
     * Puts back the sections the stored order has never heard of.
     *
     * Each one goes immediately after whichever of its default predecessors survives, so a
     * section added between two existing ones arrives between them rather than at the foot of
     * the page. A section with no surviving predecessor goes first, which is the only position
     * that keeps the default relative order true.
     *
     * @param list<string> $known
     * @param list<string> $kept
     * @return list<string>
     */
    private static function reinstate(array $known, array $kept): array
    {
        $order = $kept;

        foreach ($known as $index => $slug) {
            if (in_array($slug, $order, true)) {
                continue;
            }

            $position = 0;

            foreach (array_slice($known, 0, $index) as $predecessor) {
                $found = array_search($predecessor, $order, true);

                if ($found !== false) {
                    $position = (int) $found + 1;
                }
            }

            array_splice($order, $position, 0, [$slug]);
        }

        return array_values($order);
    }

    /**
     * The default order, as slugs.
     *
     * @return list<string>
     */
    public static function default(): array
    {
        return self::reconcile([]);
    }
}
