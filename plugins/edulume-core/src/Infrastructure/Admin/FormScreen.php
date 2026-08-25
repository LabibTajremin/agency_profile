<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Admin;

use Edulume\Core\Domain\Lead\FormDefinition;
use Edulume\Core\Domain\Lead\FormField;
use Edulume\Core\Domain\Lead\FormFieldType;
use Edulume\Core\Domain\Lead\FormPlacement;
use Edulume\Core\Infrastructure\Wp\Capabilities;
use Edulume\Core\Infrastructure\Wp\Container;

/**
 * The forms the site collects enquiries with.
 *
 * A fresh install stores no forms at all, while the theme renders `enquiry` on the contact
 * section and `assessment` on every country page — so both posted to an endpoint that had no
 * definition behind them and every submission was refused. The starter button here is the fix
 * for that: it writes the two forms the theme actually asks for.
 *
 * Placement, retention and consent wording are editable because they are the three things a
 * consultancy has to change: where the form appears, how long the data is kept, and what the
 * person is agreeing to. The field list is shown rather than edited — a field builder is a
 * screen of its own, and a half-built one that can lose a field is worse than none.
 */
final class FormScreen
{
    public const ACTION = 'edulume_save_form';
    public const SEED_ACTION = 'edulume_seed_forms';
    public const NOTICE_PARAMETER = 'edulume_forms_notice';

    public function __construct(private readonly Container $container)
    {
    }

    public function register(): void
    {
        add_action('admin_post_' . self::ACTION, [$this, 'handleSave']);
        add_action('admin_post_' . self::SEED_ACTION, [$this, 'handleSeed']);
    }

    public function handleSave(): void
    {
        $this->assertCapability();
        check_admin_referer(self::ACTION);

        $id = isset($_POST['form']) ? sanitize_key(wp_unslash($_POST['form'])) : '';
        $placement = isset($_POST['placement']) ? sanitize_key(wp_unslash($_POST['placement'])) : '';
        $retention = isset($_POST['retention']) ? absint(wp_unslash($_POST['retention'])) : 0;
        $consent = isset($_POST['consent']) ? sanitize_text_field(wp_unslash($_POST['consent'])) : '';

        $repository = $this->container->formRepository();
        $form = $repository->find($id);

        if ($form === null) {
            $this->redirect(__('That form no longer exists.', 'edulume'));
        }

        $repository->save(FormDefinition::of(
            $form->id,
            $form->title,
            $form->fields,
            FormPlacement::tryFrom($placement) ?? $form->placement,
            $retention,
            $consent
        ));

        $this->redirect(__('Form saved.', 'edulume'));
    }

    public function handleSeed(): void
    {
        $this->assertCapability();
        check_admin_referer(self::SEED_ACTION);

        $repository = $this->container->formRepository();
        $created = 0;

        foreach ($this->starters() as $form) {
            if ($repository->find($form->id) === null) {
                $repository->save($form);
                $created++;
            }
        }

        $this->redirect(sprintf(
            /* translators: %d: how many starter forms were created. */
            _n('%d form created.', '%d forms created.', $created, 'edulume'),
            $created
        ));
    }

    public function render(): void
    {
        if (!current_user_can(Capabilities::MANAGE_LEADS)) {
            return;
        }

        Notice::render(self::NOTICE_PARAMETER);

        $forms = $this->container->formRepository()->all();

        $this->renderSeedOffer($forms);

        foreach ($forms as $form) {
            $this->renderForm($form);
        }
    }

    /**
     * @param list<FormDefinition> $existing
     */
    private function renderSeedOffer(array $existing): void
    {
        $missing = [];

        foreach ($this->starters() as $starter) {
            $found = false;

            foreach ($existing as $form) {
                $found = $found || $form->id === $starter->id;
            }

            if (!$found) {
                $missing[] = $starter->id;
            }
        }

        if ($missing === []) {
            return;
        }

        printf(
            '<div class="notice notice-warning"><p>%s</p>',
            esc_html(sprintf(
                /* translators: %s: a comma-separated list of form names the theme expects. */
                __('The theme asks for these forms and this site has none of them: %s.', 'edulume'),
                implode(', ', $missing)
            ))
        );

        printf('<form method="post" action="%s">', esc_url(admin_url('admin-post.php')));

        wp_nonce_field(self::SEED_ACTION);
        Field::hidden('action', self::SEED_ACTION);

        printf(
            '<p><button type="submit" class="button button-primary">%s</button></p></form></div>',
            esc_html__('Create them', 'edulume')
        );
    }

    private function renderForm(FormDefinition $form): void
    {
        printf(
            '<section class="edulume-group"><h2 class="edulume-group__title">%1$s</h2>'
            . '<p class="edulume-group__summary">%2$s</p>',
            esc_html($form->title),
            esc_html(sprintf(
                /* translators: %s: the form's id, used in templates and the submit endpoint. */
                __('Identifier: %s', 'edulume'),
                $form->id
            ))
        );

        printf('<form method="post" action="%s">', esc_url(admin_url('admin-post.php')));

        wp_nonce_field(self::ACTION);
        Field::hidden('action', self::ACTION);
        Field::hidden('form', $form->id);

        echo '<div class="edulume-group__fields">';

        Field::select(
            'placement',
            __('Where it appears', 'edulume'),
            $form->placement->value,
            [
                FormPlacement::Inline->value => __('In the page', 'edulume'),
                FormPlacement::Popup->value => __('Pop-up', 'edulume'),
                FormPlacement::SlideIn->value => __('Slide-in', 'edulume'),
                FormPlacement::StickyBar->value => __('Sticky bar', 'edulume'),
                FormPlacement::Sidebar->value => __('Sidebar', 'edulume'),
                FormPlacement::Footer->value => __('Footer', 'edulume'),
            ]
        );

        Field::integer(
            'retention',
            __('Keep submissions for', 'edulume'),
            $form->retentionDays,
            FormDefinition::MINIMUM_RETENTION_DAYS,
            FormDefinition::MAXIMUM_RETENTION_DAYS,
            __('Anything older is erased automatically, uploads included.', 'edulume'),
            __('days', 'edulume')
        );

        Field::text(
            'consent',
            __('Consent wording', 'edulume'),
            $form->consentText,
            __('Shown beside the tick box. Leave empty for no consent box.', 'edulume')
        );

        echo '</div>';

        printf(
            '<p class="edulume-settings__actions"><button type="submit" class="button button-primary">%s</button></p>'
            . '</form>',
            esc_html__('Save form', 'edulume')
        );

        $this->renderFields($form);

        echo '</section>';
    }

    private function renderFields(FormDefinition $form): void
    {
        printf('<h3 class="edulume-subheading">%s</h3>', esc_html__('Fields', 'edulume'));

        if ($form->fields === []) {
            printf('<p class="edulume-empty">%s</p>', esc_html__('This form has no fields.', 'edulume'));

            return;
        }

        echo '<table class="widefat striped"><thead><tr>';

        foreach (
            [
            __('Label', 'edulume'),
            __('Name', 'edulume'),
            __('Type', 'edulume'),
            __('Required', 'edulume'),
            __('Step', 'edulume'),
            ] as $heading
        ) {
            printf('<th scope="col">%s</th>', esc_html($heading));
        }

        echo '</tr></thead><tbody>';

        foreach ($form->fields as $field) {
            if (!$field instanceof FormField) {
                continue;
            }

            printf(
                '<tr><td>%1$s</td><td><code>%2$s</code></td><td>%3$s</td><td>%4$s</td><td>%5$s</td></tr>',
                esc_html($field->label),
                esc_html($field->id),
                esc_html($field->type->value),
                esc_html($field->isRequired ? __('Yes', 'edulume') : __('No', 'edulume')),
                esc_html((string) ($field->stepIndex + 1))
            );
        }

        echo '</tbody></table>';
    }

    /**
     * The two forms the theme renders, defined here so the site is never asking an endpoint for
     * a form that does not exist.
     *
     * @return list<FormDefinition>
     */
    private function starters(): array
    {
        $consent = __('I agree to be contacted about my enquiry.', 'edulume');

        return [
            FormDefinition::of(
                'enquiry',
                __('Ask us a question', 'edulume'),
                [
                    FormField::of('name', __('Your name', 'edulume'), FormFieldType::Text, true),
                    FormField::of('email', __('Email', 'edulume'), FormFieldType::Email, true),
                    FormField::of('phone', __('Phone', 'edulume'), FormFieldType::Telephone),
                    FormField::of('message', __('What would you like to know?', 'edulume'), FormFieldType::Textarea, true),
                    FormField::of('consent', $consent, FormFieldType::Consent, true),
                ],
                FormPlacement::Inline,
                FormDefinition::DEFAULT_RETENTION_DAYS,
                $consent
            ),
            FormDefinition::of(
                'assessment',
                __('Free assessment', 'edulume'),
                [
                    FormField::of('name', __('Your name', 'edulume'), FormFieldType::Text, true),
                    FormField::of('email', __('Email', 'edulume'), FormFieldType::Email, true),
                    FormField::of('phone', __('Phone', 'edulume'), FormFieldType::Telephone, true),
                    FormField::of(
                        'level',
                        __('What are you applying for?', 'edulume'),
                        FormFieldType::Select,
                        true,
                        0,
                        [
                            __('Undergraduate', 'edulume'),
                            __('Masters', 'edulume'),
                            __('PhD', 'edulume'),
                            __('Foundation or pathway', 'edulume'),
                        ]
                    ),
                    FormField::of('country', __('Where do you want to study?', 'edulume'), FormFieldType::Text),
                    FormField::of('consent', $consent, FormFieldType::Consent, true),
                ],
                FormPlacement::Inline,
                FormDefinition::DEFAULT_RETENTION_DAYS,
                $consent
            ),
        ];
    }

    private function redirect(string $notice): never
    {
        Notice::redirect('edulume-forms', self::NOTICE_PARAMETER, $notice);
    }

    private function assertCapability(): void
    {
        if (!current_user_can(Capabilities::MANAGE_LEADS)) {
            wp_die(esc_html__('You are not allowed to change forms.', 'edulume'), '', ['response' => 403]);
        }
    }
}
