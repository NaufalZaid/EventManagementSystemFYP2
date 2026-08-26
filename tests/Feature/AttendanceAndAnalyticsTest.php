<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Models\AttendanceSession;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventSchedule;
use App\Models\Timeslot;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AttendanceAndAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_can_open_a_session_and_render_a_local_qr_code(): void
    {
        $organizer = User::factory()->organizer()->create();
        $event = $this->event($organizer);

        $this->actingAs($organizer)->post(route('events.attendance.sessions.store', $event), [
            'duration_minutes' => '30',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('attendance_sessions', 1);
        $this->actingAs($organizer)->get(route('events.attendance.show', $event))
            ->assertOk()->assertSee('data:image/svg+xml;base64', false)->assertSee('Open until');
    }

    public function test_registered_student_can_check_in_only_once(): void
    {
        $organizer = User::factory()->organizer()->create();
        $student = User::factory()->create();
        $event = $this->event($organizer);
        $registration = $this->register($student, $event);
        [$session, $token] = $this->attendanceSession($event, $organizer);

        $this->actingAs($student)->get(route('attendance.check-in.show', $token))->assertOk()->assertSee('Confirm my attendance');
        $this->actingAs($student)->post(route('attendance.check-in.store', $token))->assertRedirect(route('attendance.history'));
        $this->assertDatabaseHas('attendance_records', [
            'event_registration_id' => $registration->id, 'attendance_session_id' => $session->id,
            'user_id' => $student->id, 'method' => 'qr',
        ]);

        $this->actingAs($student)->post(route('attendance.check-in.store', $token))->assertSessionHasErrors('attendance');
        $this->assertDatabaseCount('attendance_records', 1);
        $this->actingAs($student)->from(route('discover.show', $event))
            ->delete(route('events.registration.destroy', $event))
            ->assertRedirect(route('discover.show', $event))
            ->assertSessionHas('warning', 'You cannot cancel after attendance has been recorded.');
        $this->assertSame(RegistrationStatus::Registered, $registration->fresh()->status);
    }

    public function test_unregistered_student_and_closed_session_cannot_record_attendance(): void
    {
        $organizer = User::factory()->organizer()->create();
        $student = User::factory()->create();
        $event = $this->event($organizer);
        [$session, $token] = $this->attendanceSession($event, $organizer);

        $this->actingAs($student)->post(route('attendance.check-in.store', $token))->assertSessionHasErrors('attendance');
        $session->update(['closed_at' => now()]);
        $this->actingAs($student)->get(route('attendance.check-in.show', $token))->assertStatus(410);
        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_organizer_can_manually_record_a_registered_student(): void
    {
        $organizer = User::factory()->organizer()->create();
        $student = User::factory()->create();
        $event = $this->event($organizer);
        $registration = $this->register($student, $event);
        [$session] = $this->attendanceSession($event, $organizer);

        $this->actingAs($organizer)->post(route('events.attendance.manual', [$event, $session]), [
            'registration_id' => $registration->id,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('attendance_records', [
            'event_id' => $event->id, 'user_id' => $student->id,
            'recorded_by' => $organizer->id, 'method' => 'manual',
        ]);
    }

    public function test_history_and_analytics_show_recorded_attendance(): void
    {
        $organizer = User::factory()->organizer()->create();
        $student = User::factory()->create();
        $event = $this->event($organizer);
        $registration = $this->register($student, $event);
        [$session, $token] = $this->attendanceSession($event, $organizer);
        $this->actingAs($student)->post(route('attendance.check-in.store', $token));

        $this->actingAs($student)->get(route('attendance.history'))->assertOk()->assertSee($event->title)->assertSee('Attended');
        $this->actingAs($organizer)->get(route('analytics.index'))->assertOk()->assertSee($event->title)->assertSee('100%');
        $this->assertSame($registration->id, $student->attendanceRecords()->firstOrFail()->event_registration_id);
        $this->assertSame($session->id, $student->attendanceRecords()->firstOrFail()->attendance_session_id);
    }

    private function event(User $organizer): Event
    {
        $event = Event::create([
            'organizer_id' => $organizer->id, 'title' => 'AI Research Symposium',
            'event_type' => 'symposium', 'capacity' => 100, 'duration_minutes' => 120,
            'status' => EventStatus::Published,
        ]);
        $venue = Venue::create(['name' => 'Research Auditorium', 'capacity' => 150, 'is_active' => true]);
        $timeslot = Timeslot::create(['slot_date' => now()->toDateString(), 'start_time' => '09:00', 'end_time' => '11:00']);
        EventSchedule::create(['event_id' => $event->id, 'venue_id' => $venue->id, 'timeslot_id' => $timeslot->id, 'status' => 'manual']);

        return $event;
    }

    private function register(User $student, Event $event): EventRegistration
    {
        return EventRegistration::create([
            'event_id' => $event->id, 'user_id' => $student->id,
            'status' => RegistrationStatus::Registered, 'registered_at' => now(),
        ]);
    }

    private function attendanceSession(Event $event, User $organizer): array
    {
        $token = Str::random(64);
        $session = AttendanceSession::create([
            'event_id' => $event->id, 'created_by' => $organizer->id,
            'token' => $token, 'token_hash' => hash('sha256', $token),
            'opens_at' => now()->subMinute(), 'closes_at' => now()->addMinutes(30),
        ]);

        return [$session, $token];
    }
}
