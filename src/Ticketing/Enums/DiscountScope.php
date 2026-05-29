<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Enums;

enum DiscountScope: string
{
    case Global = 'global';
    case EventsSubset = 'events_subset';
}
