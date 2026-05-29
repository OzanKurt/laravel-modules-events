<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Eligibility\Enums;

enum RequirementType: string
{
    case AgeMin = 'age_min';
    case AgeMax = 'age_max';
    case Document = 'document';
    case GroupMembership = 'group_membership';
    case Gender = 'gender';
    case FreeFormQuestion = 'free_form_question';
    case CustomRule = 'custom_rule';
}
