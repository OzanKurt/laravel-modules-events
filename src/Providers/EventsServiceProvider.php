<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Auth\Access\Gate;
use Kurt\Modules\Core\Providers\PackageServiceProvider;
use Kurt\Modules\Events\Attendance\Models\Application;
use Kurt\Modules\Events\Attendance\Support\AnnouncementDispatcher;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Catalog\Support\EventCloner;
use Kurt\Modules\Events\Catalog\Support\IcsExporter;
use Kurt\Modules\Events\Catalog\Support\RecurrenceExpander;
use Kurt\Modules\Events\Catalog\Support\TemplateManager;
use Kurt\Modules\Events\Console\Commands\DemoCommand;
use Kurt\Modules\Events\Console\Commands\DispatchAnnouncementsCommand;
use Kurt\Modules\Events\Console\Commands\DispatchRemindersCommand;
use Kurt\Modules\Events\Console\Commands\EnforceRetentionCommand;
use Kurt\Modules\Events\Console\Commands\ExpirePendingOrdersCommand;
use Kurt\Modules\Events\Console\Commands\ExpireWaitlistClaimsCommand;
use Kurt\Modules\Events\Console\Commands\GenerateOccurrencesCommand;
use Kurt\Modules\Events\Console\Commands\PruneQueueCommand;
use Kurt\Modules\Events\Console\Commands\ReleaseQueueCommand;
use Kurt\Modules\Events\Eligibility\Engine\RequirementEngine;
use Kurt\Modules\Events\Flow\Models\Refund;
use Kurt\Modules\Events\Flow\Support\AuditLogWriter;
use Kurt\Modules\Events\Flow\Support\GdprAnonymizer;
use Kurt\Modules\Events\Flow\Support\GdprExporter;
use Kurt\Modules\Events\Flow\Support\PayoutAccruer;
use Kurt\Modules\Events\Flow\Support\QueuePruner;
use Kurt\Modules\Events\Flow\Support\QueueReleaser;
use Kurt\Modules\Events\Flow\Support\RefundCoordinator;
use Kurt\Modules\Events\Flow\Support\SponsorCoordinator;
use Kurt\Modules\Events\Flow\Support\WaitlistPromoter;
use Kurt\Modules\Events\Policies\ApplicationPolicy;
use Kurt\Modules\Events\Policies\EventPolicy;
use Kurt\Modules\Events\Policies\OrderPolicy;
use Kurt\Modules\Events\Policies\RefundPolicy;
use Kurt\Modules\Events\Policies\TicketTypePolicy;
use Kurt\Modules\Events\Support\Events as EventsService;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Models\Ticket;
use Kurt\Modules\Events\Ticketing\Models\TicketType;
use Kurt\Modules\Events\Ticketing\Observers\OrderObserver;
use Kurt\Modules\Events\Ticketing\Observers\TicketObserver;
use Kurt\Modules\Events\Ticketing\Support\QrTokenSigner;
use Spatie\LaravelPackageTools\Package;

final class EventsServiceProvider extends PackageServiceProvider
{
    protected function module(): string
    {
        return 'events';
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-modules-events')
            ->hasConfigFile('events')
            ->hasTranslations()
            ->hasViews('events')
            ->discoversMigrations()
            ->hasCommands([
                ReleaseQueueCommand::class,
                PruneQueueCommand::class,
                ExpireWaitlistClaimsCommand::class,
                GenerateOccurrencesCommand::class,
                DispatchRemindersCommand::class,
                ExpirePendingOrdersCommand::class,
                DispatchAnnouncementsCommand::class,
                EnforceRetentionCommand::class,
                DemoCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(QrTokenSigner::class, fn () => new QrTokenSigner((string) config('app.key')));
        $this->app->singleton(RequirementEngine::class);
        $this->app->scoped(QueueReleaser::class);
        $this->app->scoped(QueuePruner::class);
        $this->app->scoped(WaitlistPromoter::class);
        $this->app->scoped(RefundCoordinator::class);
        $this->app->scoped(PayoutAccruer::class);
        $this->app->scoped(AuditLogWriter::class);
        $this->app->scoped(SponsorCoordinator::class);
        $this->app->scoped(EventCloner::class);
        $this->app->scoped(TemplateManager::class);
        $this->app->scoped(IcsExporter::class);
        $this->app->scoped(RecurrenceExpander::class);
        $this->app->scoped(GdprExporter::class);
        $this->app->scoped(GdprAnonymizer::class);
        $this->app->scoped(AnnouncementDispatcher::class);

        $this->app->singleton(EventsService::class);
    }

    public function packageBooted(): void
    {
        // Observers: only register what exists in v1. EventObserver is not implemented;
        // event domain events fire from the Support\Events facade explicitly.
        Order::observe(OrderObserver::class);
        Ticket::observe(TicketObserver::class);

        /** @var Gate $gate */
        $gate = $this->app->make(Gate::class);
        $gate->policy(Event::class, EventPolicy::class);
        $gate->policy(TicketType::class, TicketTypePolicy::class);
        $gate->policy(Order::class, OrderPolicy::class);
        $gate->policy(Application::class, ApplicationPolicy::class);
        $gate->policy(Refund::class, RefundPolicy::class);

        if ($this->app->runningInConsole() && (bool) config('events.scheduler.enabled', true)) {
            $this->app->booted(function () {
                /** @var Schedule $schedule */
                $schedule = $this->app->make(Schedule::class);
                $schedule->command(ReleaseQueueCommand::class)->everyTenSeconds();
                $schedule->command(PruneQueueCommand::class)->everyMinute();
                $schedule->command(ExpireWaitlistClaimsCommand::class)->everyMinute();
                $schedule->command(ExpirePendingOrdersCommand::class)->everyMinute();
                $schedule->command(DispatchAnnouncementsCommand::class)->everyMinute();
                $schedule->command(DispatchRemindersCommand::class)->everyFiveMinutes();
                $schedule->command(GenerateOccurrencesCommand::class)->daily();
                $schedule->command(EnforceRetentionCommand::class)->daily();
            });
        }
    }
}
