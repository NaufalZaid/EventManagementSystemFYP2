<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Timeslot;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchedulingRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_selects_exact_hourly_times_and_only_available_venues_are_shown(): void
    {
        $organizer = User::factory()->organizer()->create();
        $event = $this->event($organizer);
        $available = Venue::create(['name' => 'Available Hall', 'capacity' => 150, 'is_active' => true]);
        Venue::create(['name' => 'Too Small Room', 'capacity' => 20, 'is_active' => true]);
        $busy = Venue::create(['name' => 'Busy Hall', 'capacity' => 150, 'is_active' => true]);
        $otherEvent = $this->event($organizer, ['title' => 'Other Event']);
        $slot = Timeslot::create(['slot_date' => '2026-10-13', 'start_time' => '09:00', 'end_time' => '12:00']);
        EventSchedule::create(['event_id' => $otherEvent->id, 'venue_id' => $busy->id, 'timeslot_id' => $slot->id, 'status' => 'manual']);

        $this->actingAs($organizer)->get(route('venue-requests.create', [
            'event_id' => $event->id, 'slot_date' => '2026-10-13', 'start_time' => '09:00', 'end_time' => '12:00',
        ]))->assertOk()->assertSee('Available Hall')->assertDontSee('Too Small Room')->assertDontSee('Busy Hall');

        $this->actingAs($organizer)->post(route('venue-requests.store'), [
            'event_id' => $event->id, 'venue_id' => $available->id, 'slot_date' => '2026-10-13',
            'start_time' => '09:00', 'end_time' => '12:00',
        ])->assertRedirect(route('venue-requests.index'));

        $this->assertDatabaseHas('timeslots', ['start_time' => '09:00:00', 'end_time' => '12:00:00']);
        $this->assertDatabaseCount('timeslots', 1);
    }

    public function test_normal_events_end_by_six_and_flagged_events_may_end_at_eleven(): void
    {
        $organizer = User::factory()->organizer()->create();
        $venue = Venue::create(['name' => 'Night Hall', 'capacity' => 150, 'is_active' => true]);
        $normal = $this->event($organizer);

        $this->actingAs($organizer)->post(route('venue-requests.store'), [
            'event_id' => $normal->id, 'venue_id' => $venue->id, 'slot_date' => '2026-10-14',
            'start_time' => '19:00', 'end_time' => '21:00',
        ])->assertSessionHasErrors(['start_time', 'end_time']);

        $special = $this->event($organizer, ['title' => 'Night Test', 'is_outside_working_hours' => true]);
        $this->actingAs($organizer)->post(route('venue-requests.store'), [
            'event_id' => $special->id, 'venue_id' => $venue->id, 'slot_date' => '2026-10-14',
            'start_time' => '19:00', 'end_time' => '23:00',
        ])->assertSessionHasNoErrors();
    }

    public function test_timeslots_reject_minute_level_boundaries(): void
    {
        $administrator = User::factory()->administrator()->create();
        $this->actingAs($administrator)->post(route('timeslots.store'), [
            'slot_date' => '2026-10-12', 'start_time' => '10:32', 'end_time' => '12:26',
        ])->assertSessionHasErrors(['start_time', 'end_time']);
    }

    public function test_weekends_are_default_blackouts_for_organizers_and_admins(): void
    {
        $organizer = User::factory()->organizer()->create();
        $administrator = User::factory()->administrator()->create();
        $organizerEvent = $this->event($organizer);
        $adminEvent = $this->event($administrator, ['title' => 'Admin Weekend Event']);
        $venue = Venue::create(['name' => 'Weekday Hall', 'capacity' => 150, 'is_active' => true]);

        $this->actingAs($organizer)->post(route('venue-requests.store'), [
            'event_id' => $organizerEvent->id,
            'venue_id' => $venue->id,
            'slot_date' => '2026-10-10',
            'start_time' => '09:00',
            'end_time' => '11:00',
        ])->assertSessionHasErrors('slot_date');

        $this->actingAs($administrator)->post(route('schedules.store'), [
            'event_id' => $adminEvent->id,
            'venue_id' => $venue->id,
            'slot_date' => '2026-10-11',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'manual',
        ])->assertSessionHasErrors('slot_date');

        $this->assertDatabaseCount('venue_requests', 0);
        $this->assertDatabaseCount('event_schedules', 0);
    }

    public function test_admin_event_duration_must_be_a_whole_number_of_hours(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)->post(route('events.store'), [
            'title' => 'Minute Duration Event', 'event_type' => 'workshop', 'capacity' => 50,
            'duration_minutes' => 90,
        ])->assertSessionHasErrors('duration_minutes');
    }

    public function test_admin_cannot_manually_schedule_a_legacy_minute_timeslot(): void
    {
        $administrator = User::factory()->administrator()->create();
        $event = $this->event($administrator);
        $venue = Venue::create(['name' => 'Admin Hall', 'capacity' => 150, 'is_active' => true]);
        $timeslotId = DB::table('timeslots')->insertGetId([
            'slot_date' => '2026-10-15', 'start_time' => '10:15', 'end_time' => '12:45',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($administrator)->post(route('schedules.store'), [
            'event_id' => $event->id, 'venue_id' => $venue->id, 'timeslot_id' => $timeslotId, 'status' => 'manual',
        ])->assertSessionHasErrors('timeslot_id');

        $this->assertDatabaseCount('event_schedules', 0);
    }

    public function test_admin_sets_exact_hours_instead_of_selecting_a_timeslot(): void
    {
        $administrator = User::factory()->administrator()->create();
        $event = $this->event($administrator);
        $venue = Venue::create(['name' => 'Manual Hall', 'capacity' => 150, 'is_active' => true]);

        $this->actingAs($administrator)->get(route('schedules.create'))
            ->assertOk()
            ->assertSee('name="start_time"', false)
            ->assertSee('name="end_time"', false)
            ->assertDontSee('name="timeslot_id"', false);

        $this->actingAs($administrator)->post(route('schedules.store'), [
            'event_id' => $event->id,
            'venue_id' => $venue->id,
            'slot_date' => '2026-10-20',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'status' => 'manual',
        ])->assertRedirect(route('schedules.index'))->assertSessionHasNoErrors();

        $timeslot = Timeslot::whereDate('slot_date', '2026-10-20')->firstOrFail();
        $this->assertSame('09:00:00', $timeslot->start_time);
        $this->assertSame('12:00:00', $timeslot->end_time);
        $this->assertDatabaseHas('event_schedules', [
            'event_id' => $event->id,
            'venue_id' => $venue->id,
            'timeslot_id' => $timeslot->id,
            'status' => 'manual',
        ]);
    }

    public function test_admin_can_black_out_an_entire_day_for_all_venues(): void
    {
        $administrator = User::factory()->administrator()->create();
        $first = Venue::create(['name' => 'Hall A', 'capacity' => 100, 'is_active' => true]);
        $second = Venue::create(['name' => 'Hall B', 'capacity' => 100, 'is_active' => false]);

        $this->actingAs($administrator)->post(route('venues.blackouts.store', $first), [
            'all_day' => '1', 'all_venues' => '1', 'blackout_date' => '2026-12-25', 'reason' => 'Public holiday',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('venue_blackouts', ['venue_id' => $first->id, 'starts_at' => '2026-12-25 00:00:00', 'ends_at' => '2026-12-26 00:00:00']);
        $this->assertDatabaseHas('venue_blackouts', ['venue_id' => $second->id, 'starts_at' => '2026-12-25 00:00:00', 'ends_at' => '2026-12-26 00:00:00']);
    }

    public function test_admin_blackout_periods_use_hour_only_controls_and_validation(): void
    {
        $administrator = User::factory()->administrator()->create();
        $venue = Venue::create(['name' => 'Maintenance Hall', 'capacity' => 100, 'is_active' => true]);

        $this->actingAs($administrator)->get(route('venues.blackouts.index', $venue))
            ->assertOk()
            ->assertSee('name="start_time"', false)
            ->assertSee('name="end_time"', false)
            ->assertDontSee('datetime-local', false);

        $this->actingAs($administrator)->post(route('venues.blackouts.store', $venue), [
            'starts_on' => '2026-10-13', 'start_time' => '10:30',
            'ends_on' => '2026-10-13', 'end_time' => '12:00', 'reason' => 'Maintenance',
        ])->assertSessionHasErrors('start_time');

        $this->actingAs($administrator)->post(route('venues.blackouts.store', $venue), [
            'starts_on' => '2026-10-13', 'start_time' => '07:00',
            'ends_on' => '2026-10-13', 'end_time' => '12:00', 'reason' => 'Maintenance',
        ])->assertSessionHasErrors('start_time');

        $this->actingAs($administrator)->post(route('venues.blackouts.store', $venue), [
            'starts_on' => '2026-10-13', 'start_time' => '10:00',
            'ends_on' => '2026-10-13', 'end_time' => '12:00', 'reason' => 'Maintenance',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('venue_blackouts', [
            'venue_id' => $venue->id,
            'starts_at' => '2026-10-13 10:00:00',
            'ends_at' => '2026-10-13 12:00:00',
        ]);
    }

    private function event(User $organizer, array $attributes = []): Event
    {
        return Event::create(array_merge([
            'organizer_id' => $organizer->id, 'title' => 'Workshop', 'event_type' => 'workshop',
            'capacity' => 100, 'duration_minutes' => 120, 'status' => EventStatus::Approved,
        ], $attributes));
    }
}
