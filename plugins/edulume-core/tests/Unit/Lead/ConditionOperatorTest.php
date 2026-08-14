<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Lead;

use Edulume\Core\Domain\Lead\ConditionOperator;
use Edulume\Core\Domain\Lead\FormFieldType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Conditional-logic operators, every arm of them.
 *
 * Three of the five arms — `Contains`, `IsEmpty`, `IsNotEmpty` — were never executed by any
 * test. Conditional logic decides which questions a person is shown, so an inverted operator
 * does not throw or look broken; it quietly asks the wrong people the wrong questions.
 */
final class ConditionOperatorTest extends TestCase
{
    /**
     * @return list<array{ConditionOperator, string, string, bool}>
     */
    public static function comparisons(): array
    {
        return [
            [ConditionOperator::Equals, 'yes', 'yes', true],
            [ConditionOperator::Equals, 'yes', 'no', false],

            [ConditionOperator::NotEquals, 'yes', 'no', true],
            [ConditionOperator::NotEquals, 'yes', 'yes', false],

            [ConditionOperator::Contains, 'undergraduate', 'grad', true],
            [ConditionOperator::Contains, 'undergraduate', 'doctoral', false],

            [ConditionOperator::IsEmpty, '', '', true],
            [ConditionOperator::IsEmpty, 'something', '', false],

            [ConditionOperator::IsNotEmpty, 'something', '', true],
            [ConditionOperator::IsNotEmpty, '', '', false],
        ];
    }

    #[Test]
    #[DataProvider('comparisons')]
    public function it_compares_an_answer_against_what_the_rule_expects(
        ConditionOperator $operator,
        string $answer,
        string $expected,
        bool $matches
    ): void {
        self::assertSame($matches, $operator->matches($answer, $expected));
    }

    /**
     * An empty needle makes `str_contains` true for every answer, which would silently turn a
     * half-configured rule into one that always fires.
     */
    #[Test]
    public function contains_refuses_an_empty_expectation_rather_than_matching_everything(): void
    {
        self::assertFalse(ConditionOperator::Contains->matches('anything at all', ''));
    }

    #[Test]
    public function emptiness_is_judged_after_trimming_so_whitespace_is_not_an_answer(): void
    {
        self::assertTrue(ConditionOperator::IsEmpty->matches("  \t ", ''));
        self::assertFalse(ConditionOperator::IsNotEmpty->matches("  \t ", ''));
    }

    #[Test]
    public function every_operator_is_exercised_by_the_comparison_table(): void
    {
        $covered = array_unique(array_map(
            static fn (array $row): string => $row[0]->value,
            self::comparisons(),
        ));

        self::assertCount(count(ConditionOperator::cases()), $covered);
    }

    #[Test]
    public function only_the_consent_field_is_required_whatever_the_form_says(): void
    {
        $required = array_values(array_filter(
            FormFieldType::cases(),
            static fn (FormFieldType $type): bool => $type->isAlwaysRequired(),
        ));

        self::assertSame([FormFieldType::Consent], $required);
    }

    #[Test]
    public function only_a_telephone_field_offers_a_country_flag(): void
    {
        $flagged = array_values(array_filter(
            FormFieldType::cases(),
            static fn (FormFieldType $type): bool => $type->showsCountryFlag(),
        ));

        self::assertSame([FormFieldType::Telephone], $flagged);
    }
}
