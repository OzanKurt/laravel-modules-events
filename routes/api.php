<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Kurt\Modules\Events\Http\Controllers\EventController;
use Kurt\Modules\Events\Http\Controllers\RegistrationController;
use Kurt\Modules\Events\Http\Controllers\TicketTypeController;

/*
|--------------------------------------------------------------------------
| Events module API routes
|--------------------------------------------------------------------------
|
| Loaded by PackageServiceProvider::registerModuleApi(), already wrapped in the
| module's route group (prefix `events.http.prefix` = `api/events`, base
| middleware `events.http.middleware`, the `events-api` throttle and the
| `events.api.` name prefix). The events resource is mounted at the group root,
| so the collection is `/api/events` rather than `/api/events/events`.
|
| This file only distinguishes read (public) from write (authenticated) routes;
| writes append `events.http.auth_middleware` and every write enforces a Policy
| via $this->authorize() in the controller. `{event}` is constrained to numeric
| ids so it never shadows literal sub-paths such as `registrations`.
|
*/

// Read (public) endpoints — public for published/public events.
Route::get('/', [EventController::class, 'index'])->name('events.index');
Route::get('{event}', [EventController::class, 'show'])->whereNumber('event')->name('events.show');
Route::get('{event}/ticket-types', [TicketTypeController::class, 'index'])->whereNumber('event')->name('ticket-types.index');

// Write endpoints — add the module auth middleware; Policies enforced in-controller.
Route::middleware(config('events.http.auth_middleware', ['auth']))->group(function (): void {
    // Events
    Route::post('/', [EventController::class, 'store'])->name('events.store');
    Route::patch('{event}', [EventController::class, 'update'])->whereNumber('event')->name('events.update');
    Route::delete('{event}', [EventController::class, 'destroy'])->whereNumber('event')->name('events.destroy');
    Route::post('{event}/publish', [EventController::class, 'publish'])->whereNumber('event')->name('events.publish');
    Route::post('{event}/cancel', [EventController::class, 'cancel'])->whereNumber('event')->name('events.cancel');
    Route::get('{event}/attendees', [EventController::class, 'attendees'])->whereNumber('event')->name('events.attendees');

    // Ticket types
    Route::post('{event}/ticket-types', [TicketTypeController::class, 'store'])->whereNumber('event')->name('ticket-types.store');
    Route::patch('ticket-types/{ticketType}', [TicketTypeController::class, 'update'])->name('ticket-types.update');
    Route::delete('ticket-types/{ticketType}', [TicketTypeController::class, 'destroy'])->name('ticket-types.destroy');

    // Registrations / tickets
    Route::get('registrations', [RegistrationController::class, 'index'])->name('registrations.index');
    Route::post('ticket-types/{ticketType}/register', [RegistrationController::class, 'register'])->name('registrations.register');
    Route::post('orders/{order}/cancel', [RegistrationController::class, 'cancel'])->name('registrations.cancel');
    Route::post('tickets/{ticket}/check-in', [RegistrationController::class, 'checkIn'])->name('tickets.check-in');
});
