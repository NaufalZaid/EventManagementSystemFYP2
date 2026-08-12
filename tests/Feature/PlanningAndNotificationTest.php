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
use App\Notifications\EventReminderNotification;
use App\Services\EventReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PlanningAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_organizer_can_manage_tasks_only_for_their_event(): void
    {
        $organizer = User::factory()->organizer()->create();
        $otherOrganizer = User::factory()->organizer()->create();
        $event = $this->publishedEvent($organizer);

        $this->actingAs($organizer)->post(route('events.tasks.store', $event), [
            'title' => 'Confirm catering', 'priority' => 'high', 'due_date' => '2026-10-05',
        ])->assertSessionHasNoErrors();
        $task = $event->tasks()->firstOrFail();
        $this->actingAs($organizer)->patch(route('events.tasks.toggle', [$event, $task]))->assertSessionHasNoErrors();
        $this->assertNotNull($task->fresh()->completed_at);

        $this->actingAs($otherOrganizer)->get(route('events.planning', $event))->assertForbidden();
        $this->actingAs($otherOrganizer)->delete(route('events.tasks.destroy', [$event, $task]))->assertForbidden();
    }

    public function test_announcement_notifies_only_registered_students(): void
    {
        $organizer = User::factory()->organizer()->create();
        $registeredStudent = User::factory()->create();
        $cancelledStudent = User::factory()->create();
        $event = $this->publishedEvent($organizer);
        EventRegistration::create(['event_id' => $event->id, 'user_id' => $registeredStudent->id, 'status' => RegistrationStatus::Registered, 'registered_at' => now()]);
        EventRegistration::create(['event_id' => $event->id, 'user_id' => $cancelledStudent->id, 'status' => RegistrationStatus::Cancelled, 'registered_at' => now(), 'cancelled_at' => now()]);

        $this->actingAs($organizer)->post(route('events.announcements.store', $event), [
            'title' => 'Room entrance changed', 'message' => 'Please enter through the north lobby.',
        ])->assertSessionHasNoErrors();

        $this->assertCount(1, $registeredStudent->fresh()->notifications);
        $this->assertCount(0, $cancelledStudent->fresh()->notifications);
        $this->assertSame('Room entrance changed', $registeredStudent->fresh()->notifications->first()->data['title']);
    }

    public function test_reminders_are_sent_once_per_milestone(): void
    {
        Carbon::setTestNow('2026-10-01 09:00:00');
        $student = User::factory()->create();
        $event = $this->publishedEvent(date: '2026-10-07', start: '09:00');
        EventRegistration::create(['event_id' => $event->id, 'user_id' => $student->id, 'status' => RegistrationStatus::Registered, 'registered_at' => now()]);
        $service = app(EventReminderService::class);

        $this->assertSame(1, $service->sendDue());
        $this->assertSame(0, $service->sendDue());
        $this->assertDatabaseCount('reminder_deliveries', 1);
        $this->assertCount(1, $student->fresh()->notifications);
        $this->assertSame(10080, $student->fresh()->notifications->first()->data['lead_minutes']);
    }

    public function test_student_can_read_notifications_and_mark_all_as_read(): void
    {
        $organizer = User::factory()->organizer()->create();
        $student = User::factory()->create();
        $event = $this->publishedEvent($organizer);
        EventRegistration::create(['event_id' => $event->id, 'user_id' => $student->id, 'status' => RegistrationStatus::Registered, 'registered_at' => now()]);
        $this->actingAs($organizer)->post(route('events.announcements.store', $event), ['title' => 'Update', 'message' => 'Bring your student card.']);
        $notification = $student->fresh()->notifications->firstOrFail();

        $this->actingAs($student)->get(route('notifications.index'))->assertOk()->assertSee('Bring your student card.');
        $this->actingAs($student)->get(route('notifications.read', $notification->id))->assertRedirect(route('discover.show', $event));
        $this->assertNotNull($notification->fresh()->read_at);

        $student->notify(new EventReminderNotification($event, 60));
        $this->actingAs($student)->patch(route('notifications.read-all'))->assertSessionHasNoErrors();
        $this->assertSame(0, $student->fresh()->unreadNotifications()->count());
    }

    private function publishedEvent(
        ?User $organizer = null,
        string $date = '2026-10-07',
        string $start = '09:00',
    ): Event {
        $organizer ??= User::factory()->organizer()->create();
        $event = Event::create([
            'organizer_id' => $organizer->id, 'title' => 'Innovation Conference',
            'event_type' => 'conference', 'capacity' => 100, 'duration_minutes' => 120,
            'status' => EventStatus::Published,
        ]);
        $venue = Venue::create(['name' => 'Grand Hall '.$event->id, 'capacity' => 200, 'is_active' => true]);
        $timeslot = Timeslot::create(['slot_date' => $date, 'start_time' => $start, 'end_time' => '11:00']);
        EventSchedule::create(['event_id' => $event->id, 'venue_id' => $venue->id, 'timeslot_id' => $timeslot->id, 'status' => 'manual']);

        return $event;
    }
}
