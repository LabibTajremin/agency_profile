<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Admin;

/**
 * What each admin screen is for, in plain language.
 *
 * Written for somebody who runs a consultancy, not somebody who builds websites. That rules out
 * the vocabulary this codebase uses everywhere else: no "tokens", no "overrides", no "schema".
 * A person who has to look up a word in your interface has already stopped reading it.
 *
 * Kept in the domain, and as data, so the same text can appear in the on-screen panel, in
 * WordPress's contextual Help tab and in the settings search without three copies drifting
 * apart — which is how the third one ends up describing a screen that no longer exists.
 */
final class ScreenHelp
{
    /**
     * @param list<string> $steps what to actually do here, in order
     */
    private function __construct(
        public readonly string $slug,
        public readonly string $title,
        public readonly string $summary,
        public readonly array $steps,
    ) {
    }

    /**
     * @param list<string> $steps
     */
    public static function of(string $slug, string $title, string $summary, array $steps): self
    {
        return new self($slug, $title, $summary, $steps);
    }

    /**
     * @return list<self>
     */
    public static function all(): array
    {
        return [
            self::of(
                'edulume',
                'Dashboard',
                'Where you land. It shows what needs your attention — new enquiries, anything '
                . 'half-finished, and how the site is performing.',
                [
                    'New here? Run the setup wizard. It walks through the essentials in about ten minutes.',
                    'Check the enquiry count. Anything unread is a person waiting for a reply.',
                    'Use the search box at the top to jump straight to any setting by name.',
                ],
            ),
            self::of(
                'edulume-design',
                'Design',
                'How the site looks: colours, fonts, spacing and which sections appear on the '
                . 'home page. Change something and the preview beside it updates as you go, so '
                . 'nothing is a surprise once you save.',
                [
                    'Pick a colour. Everything else — buttons, links, headings — follows automatically.',
                    'Choose a font pairing rather than individual fonts. The pairings are designed to work together.',
                    'Use Sections to switch parts of the home page on and off. Off means the section is gone, not hidden.',
                    'Nothing is live until you press Save. Discard puts everything back.',
                ],
            ),
            self::of(
                'edulume-sections',
                'Home sections',
                'The blocks that make up your front page, in the order visitors see them. Every '
                . 'one is on to begin with and already filled in, so the page looks finished '
                . 'from the first day. Turn off the ones you do not need and drag the others '
                . 'into the order you want.',
                [
                    'Untick a section to remove it from the front page. Nothing is deleted — tick it again and it comes back.',
                    'Drag a row by its handle to move it, or type a number in the box beside it if you prefer.',
                    'A section with nothing of your own in it shows our sample content until you replace it.',
                    'Press Save home page. Reload the front page to see the result.',
                ],
            ),
            self::of(
                'edulume-leads',
                'Enquiries',
                'Every enquiry submitted through the site, in one list. Filter it, assign it to '
                . 'a counsellor, and export it to a spreadsheet.',
                [
                    'Click any row to read the full enquiry and add a note.',
                    'Assign an enquiry so a counsellor sees it in their own list.',
                    'Export gives you a CSV that opens in Excel or Google Sheets.',
                    'Deleting an enquiry is permanent. Export first if you might want it back.',
                ],
            ),
            self::of(
                'edulume-forms',
                'Forms',
                'The enquiry forms that appear on the site. Add fields, mark which are required, '
                . 'and decide who is emailed when somebody submits one.',
                [
                    'Drag fields to reorder them. Shorter forms get more submissions.',
                    'Mark a field required only if you genuinely cannot proceed without it.',
                    'Set the notification address, or nobody finds out an enquiry arrived.',
                    'Send yourself a test submission before you publish a new form.',
                ],
            ),
            self::of(
                'edulume-content',
                'Content tools',
                'Bulk jobs on your courses, universities and destinations: import a spreadsheet, '
                . 'export what you have, or fix many records at once.',
                [
                    'Import expects a CSV. Download the sample first and match its columns.',
                    'Every import previews what will change before anything is written.',
                    'Large imports continue in the background — you can close the tab.',
                ],
            ),
            self::of(
                'edulume-demos',
                'Starter content',
                'Fills the site with realistic example content so you can see how it looks '
                . 'before writing your own. Useful on a new site, and safe to remove later.',
                [
                    'Import adds content alongside anything you have already written — it never overwrites.',
                    'Everything it creates is tagged, so Remove takes out exactly what it added.',
                    'Replace the example text with your own before you launch.',
                ],
            ),
            self::of(
                'edulume-safety',
                'Safety',
                'Two things: undo, and your sign-in page. Snapshots of your settings are taken '
                . 'automatically before anything big, so a change you regret is one click away '
                . 'from being undone. Below them you can move your sign-in page somewhere only '
                . 'you know about, and slow down anyone guessing at passwords.',
                [
                    'A snapshot is taken before every import and every reset.',
                    'Take one yourself before a large change, and name it something you will recognise.',
                    'Restoring affects settings only. It never touches your pages or enquiries.',
                    'Before moving your sign-in page: save the new address, and check it works in a private window before you sign out.',
                    'If you are ever locked out, the line shown on this page goes into wp-config.php and opens the door again.',
                ],
            ),
            self::of(
                'edulume-licence',
                'Licence',
                'Your licence key and which site it is registered to. This is what keeps updates '
                . 'and support coming.',
                [
                    'Paste the key you were sent and press Activate.',
                    'Moving to a new domain? Deactivate here first, then activate on the new one.',
                ],
            ),
            self::of(
                'edulume-system',
                'System',
                'Technical details worth having to hand if you ever contact support: versions, '
                . 'server settings, and anything that looks wrong.',
                [
                    'Copy report puts everything on the clipboard in one go.',
                    'Warnings here are worth reading, but rarely urgent.',
                    'Nothing on this screen changes anything. It is safe to look.',
                ],
            ),
        ];
    }

    public static function find(string $slug): ?self
    {
        foreach (self::all() as $help) {
            if ($help->slug === $slug) {
                return $help;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_map(static fn (self $help): string => $help->slug, self::all());
    }
}
