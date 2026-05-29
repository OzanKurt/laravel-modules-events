<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Enums\EventStatus;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Catalog\Models\EventCategory;
use Kurt\Modules\Events\Catalog\Models\EventOrganizer;
use Kurt\Modules\Events\Catalog\Models\EventTag;
use Kurt\Modules\Events\Tests\Stubs\StubUser;

it('persists translatable title across locales', function () {
    $event = Event::factory()->create(['title' => ['en' => 'Concert', 'tr' => 'Konser']]);

    expect($event->getTranslation('title', 'en'))->toBe('Concert');
    expect($event->getTranslation('title', 'tr'))->toBe('Konser');
});

it('filters published + upcoming via scopes', function () {
    Event::factory()->create(['status' => EventStatus::Draft]);
    Event::factory()->published()->upcoming()->create();
    Event::factory()->published()->past()->create();

    expect(Event::query()->published()->upcoming()->count())->toBe(1);
});

it('scope past excludes future events', function () {
    Event::factory()->upcoming()->create();
    Event::factory()->past()->create();

    expect(Event::query()->past()->count())->toBe(1);
});

it('scopes nearLocation returns no rows when geo disabled by default', function () {
    Event::factory()->create([
        'latitude' => '52.5200000',
        'longitude' => '13.4050000',
    ]);

    expect(Event::query()->nearLocation(52.52, 13.4, 10)->count())->toBe(0);
});

it('scopeInCategory filters by category', function () {
    $cat = EventCategory::factory()->create();
    Event::factory()->create(['category_id' => $cat->id]);
    Event::factory()->create();

    expect(Event::query()->inCategory($cat)->count())->toBe(1);
    expect(Event::query()->inCategory($cat->id)->count())->toBe(1);
});

it('scopeWithTags filters by tag ids', function () {
    $event = Event::factory()->create();
    $tag = EventTag::factory()->create();
    $event->tags()->attach($tag);

    Event::factory()->create();

    expect(Event::query()->withTags([$tag->id])->count())->toBe(1);
});

it('scopeOrganizedBy filters by organizer user', function () {
    $user = StubUser::create(['email' => 'org@test.dev']);
    $event = Event::factory()->create();
    EventOrganizer::create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);

    Event::factory()->create();

    expect(Event::query()->organizedBy($user)->count())->toBe(1);
});

it('chatRoomId returns null when no bridge configured', function () {
    $event = Event::factory()->create();

    expect($event->chatRoomId())->toBeNull();
});

it('chatRoomId returns bridge value when configured', function () {
    $event = Event::factory()->create();
    config()->set('events.chat_bridge.provider', TestBridge::class);

    expect($event->chatRoomId())->toBe('chat-room-stub');
});

it('relationships are wired correctly', function () {
    $event = Event::factory()->create();

    expect($event->category())->not->toBeNull();
    expect($event->tags())->not->toBeNull();
    expect($event->organizers())->not->toBeNull();
    expect($event->sessions())->not->toBeNull();
    expect($event->ticketTypes())->not->toBeNull();
    expect($event->orders())->not->toBeNull();
    expect($event->attendees())->not->toBeNull();
    expect($event->parent())->not->toBeNull();
    expect($event->occurrences())->not->toBeNull();
});

final class TestBridge
{
    public function roomIdFor(Event $event): string
    {
        return 'chat-room-stub';
    }
}
