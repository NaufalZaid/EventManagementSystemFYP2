<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Timeslot;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueBlackout;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulingRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_allocation_skips_inactive_undersized_booked_and_blacked_out_venues(): void
    {
        $organizer = User::factory()->organizer()->create();
        $event = $this->event($organizer);
        $otherEvent = $this->event($organizer, ['title' => 'Existing Event', 'status' => EventStatus::Published]);
        $date = today()->next(Carbon::MONDAY);
        $startsAt = $date->copy()->setTime(10, 0);
        $endsAt = $date->copy()->setTime(12, 0);

        Venue::create(['name' => 'Inactive Hall', 'capacity' => 100, 'is_active' => false]);
        Venue::create(['name' => 'Small Hall', 'capacity' => 40, 'is_active' => true]);
        $booked = Venue::create(['name' => 'Booked Hall', 'capacity' => 70, 'is_active' => true]);
        $blackedOut = Venue::create(['name' => 'Maintenance Hall', 'capacity' => 80, 'is_active' => true]);
        $available = Venue::create(['name' => 'Available Hall', 'capacity' => 90, 'is_active' => true]);

        $slot = Timeslot::create(['slot_date' => $date, 'start_time' => '10:00', 'end_time' => '12:00']);
        EventSchedule::create(['event_id' => $otherEvent->id, 'venue_id' => $booked->id, 'timeslot_id' => $slot->id, 'status' => 'generated']);
        VenueBlackout::create(['venue_id' => $blackedOut->id, 'starts_at' => $startsAt, 'ends_at' => $endsAt, 'reason' => 'Maintenance']);

        $this->allocate($organizer, $event, $date, '10:00', 120, 60)->assertSessionHasNoErrors();

        $this->assertDatabaseHas('event_schedules', ['event_id' => $event->id, 'venue_id' => $available->id]);
    }

    public function test_allocation_fails_when_no_suitable_venue_is_available(): void
    {
        $organizer = User::factory()->organizer()->create();
        $event = $this->event($organizer);
        Venue::create(['name' => 'Small Hall', 'capacity' => 20, 'is_active' => true]);

        $this->allocate($organizer, $event, today()->next(Carbon::MONDAY), '10:00', 120, 100)
            ->assertSessionHasErrors('capacity');

        $this->assertDatabaseCount('event_schedules', 0);
        $this->assertSame(EventStatus::Approved, $event->fresh()->status);
    }

    public function test_past_and_weekend_dates_are_rejected(): void
    {
        $organizer = User::factory()->organizer()->create();
        Venue::create(['name' => 'Open Hall', 'capacity' => 100, 'is_active' => true]);

        $this->allocate($organizer, $this->event($organizer), today()->subDay(), '10:00', 120, 60)
            ->assertSessionHasErrors('slot_date');
        $this->allocate($organizer, $this->event($organizer, ['title' => 'Weekend Event']), today()->next(Carbon::SATURDAY), '10:00', 120, 60)
            ->assertSessionHasErrors('slot_date');

        $this->assertDatabaseCount('event_schedules', 0);
    }

    public function test_duration_must_use_whole_hours_and_finish_by_eleven_pm(): void
    {
        $organizer = User::factory()->organizer()->create();
        Venue::create(['name' => 'Night Hall', 'capacity' => 100, 'is_active' => true]);
        $date = today()->next(Carbon::MONDAY);

        $this->allocate($organizer, $this->event($organizer), $date, '18:00', 90, 60)
            ->assertSessionHasErrors('duration_minutes');
        $this->allocate($organizer, $this->event($organizer, ['title' => 'Too Late Event']), $date, '20:00', 240, 60)
            ->assertSessionHasErrors('end_time');
        $this->allocate($organizer, $this->event($organizer, ['title' => 'Evening Event']), $date, '19:00', 240, 60)
            ->assertSessionHasNoErrors();
    }

    public function test_administrator_cannot_allocate_an_event_for_an_organizer(): void
    {
        $organizer = User::factory()->organizer()->create();
        $administrator = User::factory()->administrator()->create();
        $event = $this->event($organizer);

        $this->actingAs($administrator)->post(route('events.allocation.store', $event), [
            'slot_date' => today()->next(Carbon::MONDAY)->toDateString(),
            'start_time' => '10:00',
            'duration_minutes' => 120,
            'capacity' => 60,
        ])->assertForbidden();
    }

    private function allocate(User $organizer, Event $event, Carbon $date, string $start, int $duration, int $capacity)
    {
        return $this->actingAs($organizer)->post(route('events.allocation.store', $event), [
            'slot_date' => $date->toDateString(),
            'start_time' => $start,
            'duration_minutes' => $duration,
            'capacity' => $capacity,
        ]);
    }

    private function event(User $organizer, array $attributes = []): Event
    {
        return Event::create(array_merge([
            'organizer_id' => $organizer->id,
            'title' => 'Approved Event',
            'event_type' => 'workshop',
            'status' => EventStatus::Approved,
        ], $attributes));
    }
}
