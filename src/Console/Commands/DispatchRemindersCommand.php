<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Kurt\Modules\Events\Attendance\Enums\AttendeeStatus;
use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Catalog\Enums\EventStatus;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Notifications\EventReminderDue;

final class DispatchRemindersCommand extends Command
{
    /** @var string */
    protected $signature = 'events:dispatch-reminders';

    /** @var string */
    protected $description = 'Send reminder notifications to attendees of upcoming events.';

    public function handle(): int
    {
        if (! (bool) config('events.reminders.enabled', true)) {
            $this->info('Reminders disabled via config.');

            return self::SUCCESS;
        }

        /** @var array<int, int|string> $defaultThresholds */
        $defaultThresholds = (array) config('events.reminders.before_hours', [24, 1]);

        $now = now();
        $events = Event::query()
            ->where('status', EventStatus::Published->value)
            ->where('starts_at', '>=', $now)
            ->get();

        $totalSent = 0;
        foreach ($events as $event) {
            [$thresholds, $sentMap] = $this->bucket($event, $defaultThresholds);
            if ($thresholds === []) {
                continue;
            }

            $changed = false;
            foreach ($thresholds as $hours) {
                $key = (string) $hours;
                if (isset($sentMap[$key])) {
                    continue;
                }
                $reminderTime = $event->starts_at->copy()->subHours($hours);
                if ($now->lt($reminderTime)) {
                    continue;
                }

                $attendees = Attendee::query()
                    ->where('event_id', $event->id)
                    ->whereIn('status', [
                        AttendeeStatus::Registered->value,
                        AttendeeStatus::CheckedIn->value,
                    ])
                    ->get();

                foreach ($attendees as $attendee) {
                    $user = $attendee->user()->first();
                    if ($user === null) {
                        continue;
                    }
                    Notification::send($user, new EventReminderDue($event));
                    $totalSent++;
                }

                $sentMap[$key] = $now->toIso8601String();
                $changed = true;
            }

            if ($changed) {
                DB::table($event->getTable())->where('id', $event->id)->update([
                    'reminder_intervals' => json_encode([
                        'thresholds' => $thresholds,
                        'reminders_sent' => $sentMap,
                    ], JSON_THROW_ON_ERROR),
                ]);
            }
        }

        $this->info("Dispatched {$totalSent} reminder notification(s).");

        return self::SUCCESS;
    }

    /**
     * @param  array<int, int|string>  $defaults
     * @return array{0: array<int, int>, 1: array<string, mixed>}
     */
    private function bucket(Event $event, array $defaults): array
    {
        /** @var array<int|string, mixed>|null $raw */
        $raw = $event->reminder_intervals;
        if (is_array($raw) && array_key_exists('thresholds', $raw)) {
            /** @var array<int, int|string> $thresholdsRaw */
            $thresholdsRaw = (array) $raw['thresholds'];
            $thresholds = array_map('intval', $thresholdsRaw);
            /** @var array<string, mixed> $sentMap */
            $sentMap = (array) ($raw['reminders_sent'] ?? []);

            return [$thresholds, $sentMap];
        }

        /** @var array<int, int|string> $source */
        $source = is_array($raw) && $raw !== [] ? $raw : $defaults;
        $thresholds = array_map('intval', $source);

        return [$thresholds, []];
    }
}
