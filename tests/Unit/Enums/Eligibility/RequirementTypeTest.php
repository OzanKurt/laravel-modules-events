<?php

declare(strict_types=1);

use Kurt\Modules\Events\Eligibility\Enums\RequirementType;

it('has expected cases and values', function () {
    expect(RequirementType::AgeMin->value)->toBe('age_min');
    expect(RequirementType::AgeMax->value)->toBe('age_max');
    expect(RequirementType::Document->value)->toBe('document');
    expect(RequirementType::GroupMembership->value)->toBe('group_membership');
    expect(RequirementType::Gender->value)->toBe('gender');
    expect(RequirementType::FreeFormQuestion->value)->toBe('free_form_question');
    expect(RequirementType::CustomRule->value)->toBe('custom_rule');
});
