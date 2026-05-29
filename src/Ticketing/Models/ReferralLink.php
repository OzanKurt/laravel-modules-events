<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Models;

use Database\Factories\Kurt\Modules\Events\Ticketing\ReferralLinkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Events\Catalog\Models\Event;

/**
 * @property int $id
 * @property int|null $event_id
 * @property int $organizer_id
 * @property string $code
 * @property string|null $landing_path
 * @property int $commission_basis_points
 * @property int|null $max_uses
 * @property int $uses_count
 * @property Carbon|null $expires_at
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class ReferralLink extends Model
{
    /** @use HasFactory<ReferralLinkFactory> */
    use HasFactory;

    use ResolvesUser;
    use SoftDeletes;

    protected $table = 'events_referral_links';

    /** @var list<string> */
    protected $fillable = [
        'event_id', 'organizer_id', 'code', 'landing_path',
        'commission_basis_points', 'max_uses', 'uses_count',
        'expires_at', 'active',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'expires_at' => 'datetime',
        'active' => 'boolean',
        'commission_basis_points' => 'integer',
        'max_uses' => 'integer',
        'uses_count' => 'integer',
    ];

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function organizer(): BelongsTo
    {
        return $this->userBelongsTo('organizer_id');
    }

    protected static function newFactory(): ReferralLinkFactory
    {
        return ReferralLinkFactory::new();
    }
}
