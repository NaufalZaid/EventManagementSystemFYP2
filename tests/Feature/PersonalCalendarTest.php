<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventSchedule;
use App\Models\PersonalCommitment;
use App\Models\Timeslot;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_combines_registered_events_and_personal_commitments(): void
    {
        $student = User::factory()->create();
        $event = $this->publishedEvent();
        EventRegistration::create(['event_id' => $event->id, 'user_id' => $student->id, 'status' => RegistrationStatus::Registered, 'registered_at' => now()]);
        PersonalCommitment::create([
            'user_id' => $student->id, 'title' => 'Database Revision', 'commitment_type' => 'study',
            'starts_at' => '2026-10-15 13:00:00', 'ends_at' => '2026-10-15 15:00:00',
        ]);

        $this->actingAs($student)->get(route('calendar.index', ['month' => '2026-10']))
            ->assertOk()->assertSee($event->title)->assertSee('Database Revision');
    }

    public function test_overlapping_commitment_is_saved_with_a_clash_warning(): void
    {
        $student = User::factory()->create();
        $event = $this->publishedEvent();
        EventRegistration::create(['event_id' => $event->id, 'user_id' => $student->id, 'status' => RegistrationStatus::Registered, 'registered_at' => now()]);

        $this->actingAs($student)->post(route('commitments.store'), [
            'title' => 'Midterm Test', 'commitment_type' => 'test',
            'starts_at' => '2026-10-15 10:00:00', 'ends_at' => '2026-10-15 12:00:00',
        ])->assertSessionHas('warning');

        $this->assertDatabaseHas('personal_commitments', ['user_id' => $student->id, 'title' => 'Midterm Test']);
        $this->actingAs($student)->get(route('calendar.index', ['month' => '2026-10']))->assertSee('Schedule clash');
    }

    public function test_student_can_update_and_delete_only_their_own_commitment(): void
    {
        $student = User::factory()->create();
        $otherStudent = User::factory()->create();
        $commitment = PersonalCommitment::create([
            'user_id' => $student->id, 'title' => 'Original title', 'commitment_type' => 'class',
            'starts_at' => '2026-10-15 08:00:00', 'ends_at' => '2026-10-15 09:00:00',
        ]);

        $this->actingAs($otherStudent)->get(route('commitments.edit', $commitment))->assertForbidden();
        $this->actingAs($student)->put(route('commitments.update', $commitment), [
            'title' => 'Updated title', 'commitment_type' => 'class',
            'starts_at' => '2026-10-15 08:00:00', 'ends_at' => '2026-10-15 09:30:00',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('personal_commitments', ['id' => $commitment->id, 'title' => 'Updated title']);

        $this->actingAs($student)->delete(route('commitments.destroy', $commitment))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('personal_commitments', ['id' => $commitment->id]);
    }

    public function test_event_registration_is_blocked_by_a_personal_commitment(): void
    {
        $student = User::factory()->create();
        $event = $this->publishedEvent();
        PersonalCommitment::create([
            'user_id' => $student->id, 'title' => 'Scheduled Class', 'commitment_type' => 'class',
            'starts_at' => '2026-10-15 09:30:00', 'ends_at' => '2026-10-15 10:30:00',
        ]);

        $this->actingAs($student)->post(route('events.register', $event))->assertSessionHasErrors('event');
        $this->assertDatabaseMissing('event_registrations', ['event_id' => $event->id, 'user_id' => $student->id]);
    }

    private function publishedEvent(): Event
    {
        $organizer = User::factory()->organizer()->create();
        $event = Event::create([
            'organizer_id' => $organizer->id, 'title' => 'Cybersecurity Workshop',
            'event_type' => 'workshop', 'capacity' => 100, 'duration_minutes' => 120,
            'status' => EventStatus::Published,
        ]);
        $venue = Venue::create(['name' => 'Lecture Theatre', 'capacity' => 200, 'is_active' => true]);
        $timeslot = Timeslot::create(['slot_date' => '2026-10-15', 'start_time' => '09:00', 'end_time' => '11:00']);
        EventSchedule::create(['event_id' => $event->id, 'venue_id' => $venue->id, 'timeslot_id' => $timeslot->id, 'status' => 'manual']);

        return $event;
    }
}
