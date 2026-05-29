<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Catalog\Enums;

enum EventVisibility: string
{
    case Public = 'public';
    case Unlisted = 'unlisted';
    case Private = 'private';
}
