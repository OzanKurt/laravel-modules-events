<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Catalog\Enums;

enum OrganizerRole: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Scanner = 'scanner';
}
