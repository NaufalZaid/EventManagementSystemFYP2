<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventSchedule;
use App\Models\EventTask;
use App\Models\Timeslot;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'Student Demo', 'email' => 'student@example.com', 'role' => UserRole::Student],
            ['name' => 'Organizer Demo', 'email' => 'organizer@example.com', 'role' => UserRole::Organizer],
            ['name' => 'Administrator Demo', 'email' => 'admin@example.com', 'role' => UserRole::Administrator],
        ] as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [...$account, 'password' => Hash::make('password'), 'email_verified_at' => now()]
            );
        }

        $student = User::where('email', 'student@example.com')->firstOrFail();
        $organizer = User::where('email', 'organizer@example.com')->firstOrFail();
        $administrator = User::where('email', 'admin@example.com')->firstOrFail();
        $testDate = Carbon::today()->addDays(14);

        $mainHall = Venue::updateOrCreate(
            ['name' => 'Test Main Hall'],
            ['location' => 'Block A, Ground Floor', 'capacity' => 120, 'description' => 'Primary UAT venue.', 'is_active' => true]
        );
        Venue::updateOrCreate(
            ['name' => 'Test Small Room'],
            ['location' => 'Block A, First Floor', 'capacity' => 20, 'description' => 'Small UAT venue for capacity validation.', 'is_active' => true]
        );
        Venue::updateOrCreate(
            ['name' => 'Test Closed Hall'],
            ['location' => 'Block B, Ground Floor', 'capacity' => 200, 'description' => 'Inactive UAT venue.', 'is_active' => false]
        );

        $morningSlot = Timeslot::updateOrCreate(
            ['slot_date' => $testDate->toDateString(), 'start_time' => '09:00:00', 'end_time' => '11:00:00'],
            []
        );
        Timeslot::updateOrCreate(
            ['slot_date' => $testDate->toDateString(), 'start_time' => '10:00:00', 'end_time' => '12:00:00'],
            []
        );
        Timeslot::updateOrCreate(
            ['slot_date' => $testDate->toDateString(), 'start_time' => '14:00:00', 'end_time' => '16:00:00'],
            []
        );

        $event = Event::updateOrCreate(
            ['organizer_id' => $organizer->id, 'title' => 'UAT Test Workshop'],
            [
                'event_type' => 'Workshop',
                'committee' => 'UAT Test Team',
                'description' => 'A published, scheduled event for manual UAT.',
                'capacity' => 80,
                'duration_minutes' => 60,
                'preferred_venue_id' => $mainHall->id,
                'preferred_date' => $testDate->toDateString(),
                'preferred_start_time' => '09:00',
                'status' => EventStatus::Published,
                'submitted_at' => now()->subDays(2),
                'reviewed_at' => now()->subDay(),
                'reviewed_by' => $administrator->id,
            ]
        );

        EventSchedule::updateOrCreate(
            ['event_id' => $event->id],
            ['venue_id' => $mainHall->id, 'timeslot_id' => $morningSlot->id, 'status' => 'manual']
        );
        EventRegistration::updateOrCreate(
            ['event_id' => $event->id, 'user_id' => $student->id],
            ['status' => RegistrationStatus::Registered, 'registered_at' => now(), 'cancelled_at' => null]
        );
        EventTask::updateOrCreate(
            ['event_id' => $event->id, 'title' => 'Prepare workshop materials'],
            ['description' => 'Bring slides, handouts, and attendance device.', 'priority' => 'high', 'due_date' => $testDate->copy()->subDays(2), 'completed_at' => null]
        );
    }
}
