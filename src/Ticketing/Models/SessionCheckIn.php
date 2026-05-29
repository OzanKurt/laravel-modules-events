<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Models;

use Database\Factories\Kurt\Modules\Events\Ticketing\SessionCheckInFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Events\Catalog\Models\Session;

/**
 * @property int $id
 * @property int $session_id
 * @property int $ticket_id
 * @property Carbon $checked_in_at
 * @property int|null $checked_in_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SessionCheckIn extends Model
{
    /** @use HasFactory<SessionCheckInFactory> */
    use HasFactory;

    use ResolvesUser;

    protected $table = 'events_session_check_ins';

    /** @var list<string> */
    protected $fillable = ['session_id', 'ticket_id', 'checked_in_at', 'checked_in_by'];

    /** @var array<string, string> */
    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Session, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function checkedInBy(): BelongsTo
    {
        return $this->userBelongsTo('checked_in_by');
    }

    protected static function newFactory(): SessionCheckInFactory
    {
        return SessionCheckInFactory::new();
    }
}
