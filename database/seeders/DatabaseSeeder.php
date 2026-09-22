<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\EventAnnouncement;
use App\Models\EventRegistration;
use App\Models\EventSchedule;
use App\Models\EventTask;
use App\Models\PersonalCommitment;
use App\Models\Society;
use App\Models\Timeslot;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueBlackout;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $societies = $this->seedSocieties();
        $users = $this->seedUsers($societies);
        $venues = $this->seedVenues();
        $timeslots = $this->seedTimeslots();
        $events = $this->seedEvents($societies, $users, $venues, $timeslots);

        $this->seedRegistrations($events, $users);
        $this->seedPlanningData($events, $users);
        $this->seedStudentCommitments($users, $timeslots);
        $this->seedVenueBlackouts($venues, $timeslots);
    }

    /** @return array<string, Society> */
    private function seedSocieties(): array
    {
        $definitions = [
            'computing' => ['Computing Society', 'Technology workshops, competitions, and industry events.', true],
            'business' => ['Business and Entrepreneurship Club', 'Career, networking, and entrepreneurship programmes.', true],
            'sports' => ['Sports and Wellness Club', 'Inclusive sports and student wellness activities.', true],
            'arts' => ['Arts and Culture Society', 'Performances, exhibitions, and cultural celebrations.', true],
            'inactive' => ['Alumni Society (Inactive)', 'An inactive society for administration testing.', false],
        ];

        $societies = [];
        foreach ($definitions as $key => [$name, $description, $isActive]) {
            $societies[$key] = Society::updateOrCreate(
                ['name' => $name],
                ['description' => $description, 'is_active' => $isActive]
            );
        }

        return $societies;
    }

    /** @return array<string, User> */
    private function seedUsers(array $societies): array
    {
        $definitions = [
            'admin' => ['Administrator Demo', 'admin@example.com', UserRole::Administrator, null],
            'organizer' => ['Organizer Demo', 'organizer@example.com', UserRole::Organizer, 'computing'],
            'business_organizer' => ['Aisha Rahman', 'aisha.organizer@example.com', UserRole::Organizer, 'business'],
            'sports_organizer' => ['Daniel Lee', 'daniel.organizer@example.com', UserRole::Organizer, 'sports'],
            'arts_organizer' => ['Mei Lin Tan', 'meilin.organizer@example.com', UserRole::Organizer, 'arts'],
            'student' => ['Student Demo', 'student@example.com', UserRole::Student, null],
            'student_2' => ['Nur Izzah', 'izzah.student@example.com', UserRole::Student, null],
            'student_3' => ['Arjun Kumar', 'arjun.student@example.com', UserRole::Student, null],
            'student_4' => ['Siti Aminah', 'siti.student@example.com', UserRole::Student, null],
            'student_5' => ['Jason Wong', 'jason.student@example.com', UserRole::Student, null],
            'student_6' => ['Farah Aziz', 'farah.student@example.com', UserRole::Student, null],
            'student_7' => ['Harith Ismail', 'harith.student@example.com', UserRole::Student, null],
            'student_8' => ['Priya Nair', 'priya.student@example.com', UserRole::Student, null],
            'student_9' => ['Wei Jian', 'weijian.student@example.com', UserRole::Student, null],
            'student_10' => ['Amira Yusuf', 'amira.student@example.com', UserRole::Student, null],
        ];

        $users = [];
        foreach ($definitions as $key => [$name, $email, $role, $societyKey]) {
            $users[$key] = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'role' => $role,
                    'society_id' => $societyKey ? $societies[$societyKey]->id : null,
                    'email_verified_at' => now(),
                ]
            );
        }

        return $users;
    }

    /** @return array<string, Venue> */
    private function seedVenues(): array
    {
        $definitions = [
            'hall' => ['Grand Main Hall', 'Student Centre, Ground Floor', 300, 'Large hall with a stage and audiovisual equipment.', true],
            'auditorium' => ['Innovation Auditorium', 'Technology Block, Level 2', 180, 'Tiered auditorium for talks and conferences.', true],
            'lab' => ['Digital Learning Lab', 'Computing Block, Level 3', 45, 'Computer laboratory for practical workshops.', true],
            'seminar' => ['Seminar Room A', 'Academic Block, Level 1', 60, 'Flexible seminar room with movable seating.', true],
            'sports' => ['University Sports Complex', 'North Campus', 500, 'Indoor and outdoor sports facilities.', true],
            'gallery' => ['Campus Art Gallery', 'Library Annex', 100, 'Gallery space for exhibitions and receptions.', true],
            'closed' => ['Old Lecture Theatre', 'Legacy Block', 120, 'Temporarily unavailable venue.', false],
        ];

        $venues = [];
        foreach ($definitions as $key => [$name, $location, $capacity, $description, $isActive]) {
            $venues[$key] = Venue::updateOrCreate(
                ['name' => $name],
                [
                    'location' => $location,
                    'capacity' => $capacity,
                    'description' => $description,
                    'is_active' => $isActive,
                ]
            );
        }

        return $venues;
    }

    /**
     * Seed 120 reusable slots: eight time ranges on each of the next 15 weekdays.
     *
     * @return array<string, Timeslot>
     */
    private function seedTimeslots(): array
    {
        $timeRanges = [
            ['08:00:00', '10:00:00'],
            ['10:00:00', '12:00:00'],
            ['12:00:00', '14:00:00'],
            ['14:00:00', '16:00:00'],
            ['16:00:00', '18:00:00'],
            ['09:00:00', '13:00:00'],
            ['13:00:00', '17:00:00'],
            ['18:00:00', '21:00:00'],
        ];

        $dates = [];
        $candidate = Carbon::today()->addDay();
        while (count($dates) < 15) {
            if ($candidate->isWeekday()) {
                $dates[] = $candidate->copy();
            }
            $candidate->addDay();
        }

        $timeslots = [];
        foreach ($dates as $dateIndex => $date) {
            foreach ($timeRanges as $rangeIndex => [$start, $end]) {
                $timeslots["day_{$dateIndex}_range_{$rangeIndex}"] = Timeslot::updateOrCreate(
                    [
                        'slot_date' => $date->toDateString(),
                        'start_time' => $start,
                        'end_time' => $end,
                    ],
                    []
                );
            }
        }

        $pastDate = Carbon::today()->subDays(14);
        $timeslots['past_event'] = Timeslot::updateOrCreate(
            ['slot_date' => $pastDate->toDateString(), 'start_time' => '10:00:00', 'end_time' => '12:00:00'],
            []
        );

        return $timeslots;
    }

    /** @return array<string, Event> */
    private function seedEvents(array $societies, array $users, array $venues, array $timeslots): array
    {
        $definitions = [
            'completed' => ['business_organizer', 'business', 'Graduate Career Panel', 'Career', 180, 120, 'auditorium', 'past_event', EventStatus::Completed],
            'ai_workshop' => ['organizer', 'computing', 'Introduction to Artificial Intelligence', 'Workshop', 45, 120, 'lab', 'day_1_range_0', EventStatus::Published],
            'pitching' => ['business_organizer', 'business', 'Startup Pitching Masterclass', 'Seminar', 60, 120, 'seminar', 'day_2_range_3', EventStatus::Published],
            'sports_day' => ['sports_organizer', 'sports', 'Interfaculty Sports Carnival', 'Sports', 400, 240, 'sports', 'day_3_range_5', EventStatus::Published],
            'culture_night' => ['arts_organizer', 'arts', 'Campus Cultural Night', 'Performance', 280, 180, 'hall', 'day_4_range_7', EventStatus::Scheduled],
            'art_exhibition' => ['arts_organizer', 'arts', 'Student Art Exhibition', 'Exhibition', 90, 240, 'gallery', 'day_5_range_6', EventStatus::Scheduled],
            'hackathon' => ['organizer', 'computing', 'Sustainable Campus Hackathon', 'Competition', 150, 240, 'hall', 'day_7_range_5', EventStatus::Approved],
            'charity_run' => ['sports_organizer', 'sports', 'Charity Fun Run', 'Community', 350, 120, 'sports', 'day_8_range_0', EventStatus::Approved],
            'green_forum' => ['business_organizer', 'business', 'Green Entrepreneurship Forum', 'Forum', 150, 120, 'auditorium', 'day_10_range_1', EventStatus::Submitted],
            'esports' => ['organizer', 'computing', 'Campus Esports Tournament', 'Competition', 120, 240, 'auditorium', 'day_11_range_5', EventStatus::Rejected],
            'bootcamp' => ['organizer', 'computing', 'Full-Stack Development Bootcamp', 'Workshop', 40, 240, 'lab', 'day_12_range_6', EventStatus::Draft],
            'wellness' => ['sports_organizer', 'sports', 'Student Wellness Seminar', 'Seminar', 100, 120, 'auditorium', 'day_14_range_1', EventStatus::Approved],
        ];

        $events = [];
        foreach ($definitions as $key => [$organizerKey, $societyKey, $title, $type, $capacity, $duration, $venueKey, $slotKey, $status]) {
            $slot = $timeslots[$slotKey];
            $reviewed = in_array($status, [EventStatus::Approved, EventStatus::Scheduled, EventStatus::Published, EventStatus::Completed, EventStatus::Rejected], true);
            $submitted = $status !== EventStatus::Draft;

            $events[$key] = Event::updateOrCreate(
                ['organizer_id' => $users[$organizerKey]->id, 'title' => $title],
                [
                    'society_id' => $societies[$societyKey]->id,
                    'event_type' => $type,
                    'description' => "Demonstration {$type} event with realistic scheduling and workflow data.",
                    'capacity' => $capacity,
                    'duration_minutes' => $duration,
                    'is_outside_working_hours' => $key === 'culture_night',
                    'preferred_venue_id' => $venues[$venueKey]->id,
                    'preferred_date' => $slot->slot_date->toDateString(),
                    'preferred_start_time' => $slot->start_time,
                    'status' => $status,
                    'rejection_reason' => $status === EventStatus::Rejected ? 'Please add crowd-control and equipment safety details.' : null,
                    'submitted_at' => $submitted ? now()->subDays(6) : null,
                    'reviewed_at' => $reviewed ? now()->subDays(4) : null,
                    'reviewed_by' => $reviewed ? $users['admin']->id : null,
                ]
            );

            if (in_array($status, [EventStatus::Scheduled, EventStatus::Published, EventStatus::Completed], true)) {
                EventSchedule::updateOrCreate(
                    ['event_id' => $events[$key]->id],
                    ['venue_id' => $venues[$venueKey]->id, 'timeslot_id' => $slot->id, 'status' => 'manual']
                );
            }
        }

        return $events;
    }

    private function seedRegistrations(array $events, array $users): void
    {
        $studentKeys = ['student', 'student_2', 'student_3', 'student_4', 'student_5', 'student_6', 'student_7', 'student_8', 'student_9', 'student_10'];
        $eventCounts = ['completed' => 8, 'ai_workshop' => 9, 'pitching' => 6, 'sports_day' => 10];

        foreach ($eventCounts as $eventKey => $count) {
            foreach (array_slice($studentKeys, 0, $count) as $index => $studentKey) {
                EventRegistration::updateOrCreate(
                    ['event_id' => $events[$eventKey]->id, 'user_id' => $users[$studentKey]->id],
                    ['status' => RegistrationStatus::Registered, 'registered_at' => now()->subDays($index + 1), 'cancelled_at' => null]
                );
            }
        }

        EventRegistration::updateOrCreate(
            ['event_id' => $events['pitching']->id, 'user_id' => $users['student_8']->id],
            ['status' => RegistrationStatus::Cancelled, 'registered_at' => now()->subDays(5), 'cancelled_at' => now()->subDays(2)]
        );
    }

    private function seedPlanningData(array $events, array $users): void
    {
        $tasks = [
            ['ai_workshop', 'Prepare workshop exercises', 'high', 2, true],
            ['ai_workshop', 'Send participant instructions', 'medium', 1, false],
            ['sports_day', 'Arrange first-aid station', 'high', 3, false],
            ['culture_night', 'Complete technical rehearsal', 'high', 2, false],
            ['hackathon', 'Recruit judging panel', 'medium', 7, false],
        ];

        foreach ($tasks as [$eventKey, $title, $priority, $daysBefore, $completed]) {
            EventTask::updateOrCreate(
                ['event_id' => $events[$eventKey]->id, 'title' => $title],
                [
                    'description' => "Preparation task for {$events[$eventKey]->title}.",
                    'priority' => $priority,
                    'due_date' => $events[$eventKey]->preferred_date->copy()->subDays($daysBefore),
                    'completed_at' => $completed ? now()->subDay() : null,
                ]
            );
        }

        foreach (['ai_workshop', 'pitching', 'sports_day'] as $eventKey) {
            EventAnnouncement::updateOrCreate(
                ['event_id' => $events[$eventKey]->id, 'title' => 'Event information update'],
                [
                    'created_by' => $events[$eventKey]->organizer_id,
                    'message' => 'Please review the latest event details and arrive 15 minutes before the scheduled start.',
                    'published_at' => now()->subDay(),
                ]
            );
        }
    }

    private function seedStudentCommitments(array $users, array $timeslots): void
    {
        $definitions = [
            ['student', 'Software Engineering Lecture', 'class', 'day_1_range_3'],
            ['student', 'Final Year Project Meeting', 'meeting', 'day_3_range_1'],
            ['student_2', 'Database Systems Test', 'test', 'day_4_range_3'],
            ['student_3', 'Library Study Session', 'study', 'day_6_range_1'],
        ];

        foreach ($definitions as [$userKey, $title, $type, $slotKey]) {
            $slot = $timeslots[$slotKey];
            $startsAt = Carbon::parse($slot->slot_date->toDateString().' '.$slot->start_time);
            $endsAt = Carbon::parse($slot->slot_date->toDateString().' '.$slot->end_time);

            PersonalCommitment::updateOrCreate(
                ['user_id' => $users[$userKey]->id, 'title' => $title, 'starts_at' => $startsAt],
                ['commitment_type' => $type, 'location' => 'Main Campus', 'description' => 'Demonstration calendar commitment.', 'ends_at' => $endsAt]
            );
        }
    }

    private function seedVenueBlackouts(array $venues, array $timeslots): void
    {
        $slot = $timeslots['day_9_range_5'];
        $startsAt = Carbon::parse($slot->slot_date->toDateString().' '.$slot->start_time);

        VenueBlackout::updateOrCreate(
            ['venue_id' => $venues['auditorium']->id, 'starts_at' => $startsAt],
            ['ends_at' => Carbon::parse($slot->slot_date->toDateString().' '.$slot->end_time), 'reason' => 'Scheduled audiovisual maintenance']
        );
    }
}
