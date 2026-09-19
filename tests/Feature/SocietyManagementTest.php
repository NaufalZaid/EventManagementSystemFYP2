<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Society;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocietyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_a_society_without_an_organizer(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)->post(route('societies.store'), [
            'name' => 'Robotics Society',
            'description' => 'Robotics events and workshops.',
            'is_active' => '1',
        ])->assertRedirect(route('societies.index'))->assertSessionHasNoErrors();

        $society = Society::where('name', 'Robotics Society')->firstOrFail();
        $this->assertFalse($society->organizers()->exists());
        $this->actingAs($administrator)->get(route('societies.index'))->assertOk()->assertSee('Robotics Society');
        $this->actingAs($administrator)->get(route('societies.create'))->assertOk();
        $this->actingAs($administrator)->get(route('societies.edit', $society))->assertOk();
    }

    public function test_organizers_in_the_same_society_can_collaborate_on_events(): void
    {
        $society = Society::factory()->create();
        $creator = User::factory()->organizer()->create(['society_id' => $society->id]);
        $colleague = User::factory()->organizer()->create(['society_id' => $society->id]);
        $outsider = User::factory()->organizer()->create();
        $event = $this->event($creator, $society);

        $this->actingAs($colleague)->get(route('events.edit', $event))->assertOk();
        $this->actingAs($colleague)->post(route('events.submit', $event))->assertRedirect(route('events.index'));
        $this->assertSame(EventStatus::Submitted, $event->fresh()->status);

        $this->actingAs($outsider)->get(route('events.edit', $event))->assertForbidden();
    }

    public function test_organizer_created_event_is_owned_by_their_society(): void
    {
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)->post(route('events.store'), [
            'title' => 'Society Workshop',
            'event_type' => 'workshop',
            'committee' => 'User-provided committee should be ignored',
            'capacity' => 50,
            'duration_minutes' => 60,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $event = Event::where('title', 'Society Workshop')->firstOrFail();
        $this->assertSame($organizer->society_id, $event->society_id);
        $this->assertSame($organizer->id, $event->organizer_id);
        $this->assertNull($event->committee);
    }

    public function test_organizer_cannot_choose_another_society_for_an_event(): void
    {
        $organizer = User::factory()->organizer()->create();
        $otherSociety = Society::factory()->create();

        $this->actingAs($organizer)->post(route('events.store'), [
            'title' => 'Protected Ownership',
            'event_type' => 'workshop',
            'capacity' => 50,
            'duration_minutes' => 60,
            'society_id' => $otherSociety->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame($organizer->society_id, Event::where('title', 'Protected Ownership')->value('society_id'));
    }

    public function test_unassigned_organizer_cannot_create_an_event_by_posting_directly(): void
    {
        $organizer = User::factory()->organizer()->create(['society_id' => null]);

        $this->actingAs($organizer)->post(route('events.store'), [
            'title' => 'Unowned Event',
            'event_type' => 'workshop',
            'capacity' => 50,
            'duration_minutes' => 60,
        ])->assertStatus(422);

        $this->assertDatabaseMissing('events', ['title' => 'Unowned Event']);
    }

    private function event(User $organizer, Society $society): Event
    {
        return Event::create([
            'organizer_id' => $organizer->id,
            'society_id' => $society->id,
            'title' => 'Shared Society Event',
            'event_type' => 'workshop',
            'capacity' => 100,
            'duration_minutes' => 60,
            'status' => EventStatus::Draft,
        ]);
    }
}
