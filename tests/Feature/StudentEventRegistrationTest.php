<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventSchedule;
use App\Models\Timeslot;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentEventRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_publish_a_scheduled_event(): void
    {
        $administrator = User::factory()->administrator()->create();
        $event = $this->scheduledEvent(status: EventStatus::Scheduled);

        $this->actingAs($administrator)->patch(route('events.publish', $event))->assertSessionHasNoErrors();

        $this->assertSame(EventStatus::Published, $event->fresh()->status);
    }

    public function test_student_can_search_published_events_but_cannot_see_unpublished_events(): void
    {
        $student = User::factory()->create();
        $this->scheduledEvent(title: 'Robotics Workshop');
        $this->scheduledEvent(title: 'Private Committee Meeting', status: EventStatus::Scheduled, date: '2026-10-02');

        $this->actingAs($student)->get(route('discover.index', ['q' => 'Robotics']))
            ->assertOk()
            ->assertSee('Robotics Workshop')
            ->assertDontSee('Private Committee Meeting');
    }

    public function test_student_can_register_cancel_and_register_again(): void
    {
        $student = User::factory()->create();
        $event = $this->scheduledEvent();

        $this->actingAs($student)->post(route('events.register', $event))
            ->assertRedirect(route('my-events.index'));
        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id, 'user_id' => $student->id, 'status' => RegistrationStatus::Registered->value,
        ]);

        $this->actingAs($student)->delete(route('events.registration.destroy', $event))->assertSessionHasNoErrors();
        $this->assertSame(RegistrationStatus::Cancelled, EventRegistration::firstOrFail()->fresh()->status);

        $this->actingAs($student)->post(route('events.register', $event))->assertSessionHasNoErrors();
        $this->assertSame(RegistrationStatus::Registered, EventRegistration::firstOrFail()->fresh()->status);
        $this->assertDatabaseCount('event_registrations', 1);
    }

    public function test_registration_stops_when_event_capacity_is_full(): void
    {
        $student = User::factory()->create();
        $otherStudent = User::factory()->create();
        $event = $this->scheduledEvent(capacity: 1);
        EventRegistration::create([
            'event_id' => $event->id,
            'user_id' => $otherStudent->id,
            'status' => RegistrationStatus::Registered,
            'registered_at' => now(),
        ]);

        $this->actingAs($student)->post(route('events.register', $event))->assertSessionHasErrors('event');
        $this->assertDatabaseMissing('event_registrations', ['event_id' => $event->id, 'user_id' => $student->id]);
    }

    public function test_registration_rejects_an_overlapping_event_in_my_events(): void
    {
        $student = User::factory()->create();
        $first = $this->scheduledEvent(title: 'First Event', start: '09:00', end: '11:00');
        $second = $this->scheduledEvent(title: 'Clashing Event', start: '10:30', end: '12:00');

        $this->actingAs($student)->post(route('events.register', $first))->assertSessionHasNoErrors();
        $this->actingAs($student)->post(route('events.register', $second))->assertSessionHasErrors('event');

        $this->assertDatabaseMissing('event_registrations', ['event_id' => $second->id, 'user_id' => $student->id]);
    }

    private function scheduledEvent(
        string $title = 'Published Technology Event',
        EventStatus $status = EventStatus::Published,
        string $date = '2026-10-01',
        string $start = '09:00',
        string $end = '11:00',
        int $capacity = 100,
    ): Event {
        $organizer = User::factory()->organizer()->create();
        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => $title,
            'event_type' => 'workshop',
            'committee' => 'Technology Society',
            'description' => 'A practical technology event.',
            'capacity' => $capacity,
            'duration_minutes' => 60,
            'status' => $status,
        ]);
        $venue = Venue::create(['name' => $title.' Venue', 'capacity' => 200, 'is_active' => true]);
        $timeslot = Timeslot::create(['slot_date' => $date, 'start_time' => $start, 'end_time' => $end]);
        EventSchedule::create(['event_id' => $event->id, 'venue_id' => $venue->id, 'timeslot_id' => $timeslot->id, 'status' => 'manual']);

        return $event;
    }
}
