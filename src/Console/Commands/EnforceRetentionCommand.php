<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Events\Flow\Support\GdprAnonymizer;

final class EnforceRetentionCommand extends Command
{
    /** @var string */
    protected $signature = 'events:enforce-retention';

    /** @var string */
    protected $description = 'Anonymise personal data of users whose last activity is older than events.gdpr.retention_days.';

    public function handle(GdprAnonymizer $anonymizer): int
    {
        $retentionDays = config('events.gdpr.retention_days');
        if ($retentionDays === null) {
            $this->info('GDPR retention disabled (no retention_days configured).');

            return self::SUCCESS;
        }

        $cutoff = Carbon::now()->subDays((int) $retentionDays);

        // Find users whose latest activity timestamp across attendees / orders / tickets is older than cutoff.
        $userModel = config('kurtmodules.user_model');
        if (! is_string($userModel) || ! is_subclass_of($userModel, Model::class)) {
            $this->error('No user_model configured under kurtmodules.user_model.');

            return self::FAILURE;
        }

        /** @var Model $proto */
        $proto = new $userModel;
        $userTable = $proto->getTable();

        $candidates = DB::table($userTable)
            ->select($userTable.'.id')
            ->leftJoin('events_attendees', $userTable.'.id', '=', 'events_attendees.user_id')
            ->leftJoin('events_orders', $userTable.'.id', '=', 'events_orders.user_id')
            ->leftJoin('events_tickets', $userTable.'.id', '=', 'events_tickets.holder_id')
            ->groupBy($userTable.'.id')
            ->havingRaw(
                'COALESCE(MAX(events_attendees.updated_at), MAX(events_orders.updated_at), MAX(events_tickets.updated_at)) < ?',
                [$cutoff->toDateTimeString()],
            )
            ->pluck($userTable.'.id');

        $count = 0;
        foreach ($candidates as $id) {
            /** @var Model|null $user */
            $user = $userModel::find($id);
            if ($user === null) {
                continue;
            }
            $anonymizer->anonymize($user);
            $count++;
        }

        $this->info("Anonymised {$count} user record(s) past retention.");

        return self::SUCCESS;
    }
}
