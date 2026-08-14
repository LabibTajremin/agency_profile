<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Lead;

use Edulume\Core\Domain\Lead\ConditionOperator;
use Edulume\Core\Domain\Lead\FieldCondition;
use Edulume\Core\Domain\Lead\FormDefinition;
use Edulume\Core\Domain\Lead\FormField;
use Edulume\Core\Domain\Lead\FormFieldType;
use Edulume\Core\Domain\Lead\FormPlacement;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FormSerialisationTest extends TestCase
{
    private function form(): FormDefinition
    {
        return FormDefinition::of(
            'enquiry',
            'Course enquiry',
            [
                FormField::of('name', 'Your name', FormFieldType::Text, true),
                FormField::of('email', 'Email', FormFieldType::Email, true),
                FormField::of('level', 'Study level', FormFieldType::Select, false, 1, ['Bachelors', 'Masters']),
                FormField::of(
                    'ielts',
                    'IELTS score',
                    FormFieldType::Number,
                    false,
                    1,
                    [],
                    FieldCondition::of('level', ConditionOperator::Equals, 'Masters'),
                ),
            ],
            FormPlacement::Popup,
            365,
            'I agree to be contacted about my enquiry.',
        );
    }

    #[Test]
    public function a_form_round_trips_losslessly(): void
    {
        $form = $this->form();

        self::assertEquals($form, FormDefinition::fromArray($form->toArray()));
    }

    #[Test]
    public function a_form_survives_a_json_round_trip(): void
    {
        $form = $this->form();
        $decoded = json_decode((string) json_encode($form->toArray()), true);

        self::assertIsArray($decoded);
        self::assertEquals($form, FormDefinition::fromArray($decoded));
    }

    #[Test]
    public function a_conditional_field_keeps_its_condition(): void
    {
        $rebuilt = FormDefinition::fromArray($this->form()->toArray());
        $field = $rebuilt->field('ielts');

        self::assertNotNull($field);
        self::assertNotNull($field->condition);
        self::assertSame('level', $field->condition->fieldId);
        self::assertSame(ConditionOperator::Equals, $field->condition->operator);
        self::assertSame('Masters', $field->condition->expected);
    }

    #[Test]
    public function an_unconditional_field_stores_no_condition(): void
    {
        $stored = FormField::of('name', 'Your name', FormFieldType::Text)->toArray();

        self::assertNull($stored['condition']);
        self::assertNull(FormField::fromArray($stored)->condition);
    }

    #[Test]
    public function a_condition_with_no_field_reads_as_absence_rather_than_never_satisfied(): void
    {
        // A condition that can never be met would hide the field forever, which is a far worse
        // failure than showing a field that should have been conditional.
        self::assertNull(FieldCondition::fromArray([]));
        self::assertNull(FieldCondition::fromArray(['fieldId' => '   ']));
        self::assertNull(FormField::fromArray(['id' => 'x', 'condition' => []])->condition);
    }

    #[Test]
    public function a_condition_round_trips_through_its_array_form(): void
    {
        $condition = FieldCondition::of('level', ConditionOperator::Contains, 'Mast');

        self::assertSame(
            ['fieldId' => 'level', 'operator' => 'contains', 'expected' => 'Mast'],
            $condition->toArray(),
        );
        self::assertEquals($condition, FieldCondition::fromArray($condition->toArray()));
    }

    #[Test]
    public function an_unreadable_condition_operator_falls_back_to_equality(): void
    {
        $condition = FieldCondition::fromArray(['fieldId' => 'level', 'operator' => 'nonsense']);

        self::assertNotNull($condition);
        self::assertSame(ConditionOperator::Equals, $condition->operator);
    }

    #[Test]
    public function unreadable_stored_form_data_falls_back_rather_than_fataling(): void
    {
        $form = FormDefinition::fromArray([
            'id' => 42,
            'placement' => 'nowhere',
            'fields' => ['not an array', ['id' => 'a', 'type' => 'nonsense']],
            'retentionDays' => 'ages',
        ]);

        // A non-string id is coerced rather than discarded: `42` is a usable id, just badly typed.
        self::assertSame('42', $form->id);
        self::assertSame(FormPlacement::Inline, $form->placement);
        // The malformed row is skipped, not defaulted: a field with no id collects nothing.
        self::assertCount(1, $form->fields);
        self::assertSame(FormFieldType::Text, $form->fields[0]->type);
        self::assertSame(FormDefinition::MINIMUM_RETENTION_DAYS, $form->retentionDays);
    }

    #[Test]
    public function a_fields_options_and_step_survive_storage(): void
    {
        $rebuilt = FormDefinition::fromArray($this->form()->toArray());
        $level = $rebuilt->field('level');

        self::assertNotNull($level);
        self::assertSame(['Bachelors', 'Masters'], $level->options);
        self::assertSame(1, $level->stepIndex);
        self::assertTrue($rebuilt->isMultiStep());
    }

    #[Test]
    public function a_negative_step_index_is_clamped_on_the_way_back_in(): void
    {
        self::assertSame(0, FormField::fromArray(['id' => 'a', 'stepIndex' => -4])->stepIndex);
    }
}
