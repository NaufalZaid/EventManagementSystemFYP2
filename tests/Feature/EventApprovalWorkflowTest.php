<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\VenueRequestStatus;
use App\Models\Event;
use App\Models\Timeslot;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueBlackout;
use App\Models\VenueRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_can_only_manage_their_own_event(): void
    {
        $owner = User::factory()->organizer()->create();
        $otherOrganizer = User::factory()->organizer()->create();
        $event = $this->event($owner);

        $this->actingAs($otherOrganizer)->get(route('events.edit', $event))->assertForbidden();
        $this->actingAs($otherOrganizer)->post(route('events.submit', $event))->assertForbidden();
    }

    public function test_event_can_move_from_draft_to_an_approved_venue_schedule(): void
    {
        $organizer = User::factory()->organizer()->create();
        $administrator = User::factory()->administrator()->create();
        $event = $this->event($organizer);
        $venue = Venue::create(['name' => 'Main Hall', 'capacity' => 250, 'is_active' => true]);
        $timeslot = Timeslot::create(['slot_date' => '2026-09-10', 'start_time' => '09:00', 'end_time' => '11:00']);

        $this->actingAs($organizer)->post(route('events.submit', $event))->assertRedirect(route('events.index'));
        $this->assertSame(EventStatus::Submitted, $event->fresh()->status);

        $this->actingAs($administrator)->patch(route('proposals.approve', $event))->assertSessionHasNoErrors();
        $this->assertSame(EventStatus::Approved, $event->fresh()->status);

        $this->actingAs($organizer)->post(route('venue-requests.store'), [
            'event_id' => $event->id,
            'venue_id' => $venue->id,
            'timeslot_id' => $timeslot->id,
            'organizer_notes' => 'Projector required',
        ])->assertRedirect(route('venue-requests.index'));

        $venueRequest = VenueRequest::firstOrFail();
        $this->actingAs($administrator)->patch(route('venue-requests.approve', $venueRequest), [
            'admin_notes' => 'Projector confirmed',
        ])->assertSessionHasNoErrors();

        $this->assertSame(VenueRequestStatus::Approved, $venueRequest->fresh()->status);
        $this->assertSame(EventStatus::Scheduled, $event->fresh()->status);
        $this->assertDatabaseHas('event_schedules', [
            'event_id' => $event->id,
            'venue_id' => $venue->id,
            'timeslot_id' => $timeslot->id,
        ]);
    }

    public function test_venue_request_rejects_a_blackout_conflict(): void
    {
        $organizer = User::factory()->organizer()->create();
        $event = $this->event($organizer, EventStatus::Approved);
        $venue = Venue::create(['name' => 'Lab A', 'capacity' => 100, 'is_active' => true]);
        $timeslot = Timeslot::create(['slot_date' => '2026-09-10', 'start_time' => '09:00', 'end_time' => '11:00']);
        VenueBlackout::create([
            'venue_id' => $venue->id,
            'starts_at' => '2026-09-10 10:00:00',
            'ends_at' => '2026-09-10 12:00:00',
            'reason' => 'Maintenance',
        ]);

        $this->actingAs($organizer)->from(route('venue-requests.create'))->post(route('venue-requests.store'), [
            'event_id' => $event->id,
            'venue_id' => $venue->id,
            'timeslot_id' => $timeslot->id,
        ])->assertRedirect(route('venue-requests.create'))->assertSessionHasErrors('venue_id');

        $this->assertDatabaseCount('venue_requests', 0);
    }

    public function test_venue_request_rejects_insufficient_capacity_and_duration(): void
    {
        $organizer = User::factory()->organizer()->create();
        $event = $this->event($organizer, EventStatus::Approved);
        $venue = Venue::create(['name' => 'Small Room', 'capacity' => 50, 'is_active' => true]);
        $timeslot = Timeslot::create(['slot_date' => '2026-09-10', 'start_time' => '09:00', 'end_time' => '09:30']);

        $this->actingAs($organizer)->post(route('venue-requests.store'), [
            'event_id' => $event->id,
            'venue_id' => $venue->id,
            'timeslot_id' => $timeslot->id,
        ])->assertSessionHasErrors(['venue_id', 'timeslot_id']);
    }

    private function event(User $organizer, EventStatus $status = EventStatus::Draft): Event
    {
        return Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Technology Showcase',
            'event_type' => 'exhibition',
            'committee' => 'Tech Society',
            'description' => 'Student technology projects.',
            'capacity' => 100,
            'duration_minutes' => 90,
            'status' => $status,
        ]);
    }
}
