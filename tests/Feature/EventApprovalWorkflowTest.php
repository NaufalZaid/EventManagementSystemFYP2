<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Timeslot;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_submits_proposal_before_entering_schedule_requirements(): void
    {
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)->post(route('events.store'), [
            'title' => 'Technology Showcase',
            'event_type' => 'exhibition',
            'description' => 'Student technology projects.',
        ])->assertSessionHasNoErrors();

        $event = Event::firstOrFail();
        $this->assertSame(EventStatus::Draft, $event->status);
        $this->assertSame(0, $event->capacity);
        $this->assertNull($event->duration_minutes);

        $this->actingAs($organizer)->post(route('events.submit', $event))->assertRedirect(route('events.index'));
        $this->assertSame(EventStatus::Submitted, $event->fresh()->status);
    }

    public function test_approved_event_is_automatically_allocated_to_smallest_suitable_venue_and_published(): void
    {
        $organizer = User::factory()->organizer()->create();
        $administrator = User::factory()->administrator()->create();
        $event = $this->event($organizer, EventStatus::Submitted);
        $small = Venue::create(['name' => 'Small Room', 'capacity' => 40, 'is_active' => true]);
        $bestFit = Venue::create(['name' => 'Seminar Hall', 'capacity' => 80, 'is_active' => true]);
        Venue::create(['name' => 'Grand Hall', 'capacity' => 300, 'is_active' => true]);

        $this->actingAs($administrator)->patch(route('proposals.approve', $event))->assertSessionHasNoErrors();

        $date = today()->next(Carbon::MONDAY)->toDateString();
        $this->actingAs($organizer)->post(route('events.allocation.store', $event), [
            'slot_date' => $date,
            'start_time' => '10:00',
            'duration_minutes' => 120,
            'capacity' => 60,
        ])->assertRedirect(route('events.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('event_schedules', [
            'event_id' => $event->id,
            'venue_id' => $bestFit->id,
            'status' => 'generated',
        ]);
        $this->assertDatabaseMissing('event_schedules', ['event_id' => $event->id, 'venue_id' => $small->id]);
        $this->assertTrue(Timeslot::whereDate('slot_date', $date)
            ->where('start_time', '10:00:00')->where('end_time', '12:00:00')->exists());
        $event = $event->fresh();
        $this->assertSame(EventStatus::Published, $event->status);
        $this->assertSame(60, $event->capacity);
        $this->assertSame(120, $event->duration_minutes);
    }

    public function test_event_must_be_approved_before_automatic_allocation(): void
    {
        $organizer = User::factory()->organizer()->create();
        $event = $this->event($organizer, EventStatus::Draft);
        Venue::create(['name' => 'Available Hall', 'capacity' => 100, 'is_active' => true]);

        $this->actingAs($organizer)->post(route('events.allocation.store', $event), [
            'slot_date' => today()->next(Carbon::MONDAY)->toDateString(),
            'start_time' => '10:00',
            'duration_minutes' => 120,
            'capacity' => 60,
        ])->assertStatus(422);

        $this->assertDatabaseCount('event_schedules', 0);
    }

    public function test_organizer_cannot_allocate_another_societys_event(): void
    {
        $owner = User::factory()->organizer()->create();
        $otherOrganizer = User::factory()->organizer()->create();
        $event = $this->event($owner, EventStatus::Approved);

        $this->actingAs($otherOrganizer)->get(route('events.allocation.create', $event))->assertForbidden();
    }

    private function event(User $organizer, EventStatus $status): Event
    {
        return Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Technology Showcase',
            'event_type' => 'exhibition',
            'description' => 'Student technology projects.',
            'status' => $status,
        ]);
    }
}
