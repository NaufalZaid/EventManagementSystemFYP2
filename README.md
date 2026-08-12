# Event Management System with Genetic Algorithm Scheduling

A web-based Event Management System (EMS) for Multimedia University (MMU), developed as Final Year Project 2. The system is intended to centralize university event discovery, registration, planning, attendance, venue management, and reporting while using a Genetic Algorithm (GA) to generate optimized, conflict-free event schedules.

This repository currently contains a working Laravel prototype with role-based workflows, student participation, secure QR attendance, date-filtered reporting, CSV evidence exports, a consent-based usability survey, and a Genetic Algorithm that generates, benchmarks, and safely applies optimized schedules.

## Project Information

- Project ID: `FYP01-SE-T2530-0794`
- Student: Muhammad Nabil Naufal bin Md Zaid
- Supervisor: Dr Mohana Muniandy
- Institution: Multimedia University (MMU)
- Development methodology: Kanban
- Requirements source: [FYP1 Project Report](docs/1221101160_Dr%20Mohana%20Muniandy_FYP1_Report.pdf)

## Problem Statement

MMU event management currently relies on fragmented communication channels and manual coordination. Students may miss events or registration updates, while organizers and administrators repeatedly check dates, venue availability, capacity, and approvals by hand. These processes are time-consuming, susceptible to human error, and can result in scheduling conflicts or inefficient venue utilization.

This project addresses those problems through a centralized EMS and an automated scheduling optimizer.

The requirements were elicited in FYP1 through a questionnaire with 30 respondents and five stakeholder interviews involving students and people with event-participation or event-management experience.

## Project Objectives

1. Design and develop an EMS with a GA-based scheduling optimization module.
2. Implement a fitness function that evaluates event, venue, and timeslot assignments against capacity, availability, blackout-date, and organizer-preference constraints.
3. Evaluate whether the system minimizes conflicts, improves venue utilization, and provides a usable experience for event organizers.

## Intended Users

The final system has three primary application roles:

- **Student:** discovers events, registers or cancels participation, receives notifications, maintains a personal calendar, records attendance, and views participation history.
- **Event organizer:** proposes and manages events, requests venues, manages registrations and tasks, publishes announcements, generates attendance QR codes, and views event analytics.
- **Administrator:** reviews proposals and venue requests, manages venue allocation and master data, monitors conflicts and venue usage, views reports, and audits administrative actions.

The FYP1 scope also refers to venue managers as operational users. In the current target design, their venue-management responsibilities can be represented by the administrator role unless a separate role is introduced later.

## Functional Requirements

| ID | Requirement | Expected behavior |
|---|---|---|
| FR-01 | Authentication and authorization | Users can register and log in, and role-based access controls expose only the functions allowed for their role. |
| FR-02 | Event discovery | Students can view a chronological event list and search or filter it by event name, type, date, or organizing committee. |
| FR-03 | Event registration | Students can register for and cancel events, receive immediate confirmation, and see registered events in a personal `My Events` list. |
| FR-04 | Notifications | The system stores in-app notifications, supports push notifications, announces event changes, and sends reminders one week, one day, and one hour before an event. |
| FR-05 | Personal scheduling | Registered events are added to an in-app calendar. Students can add personal commitments such as classes or tests to identify clashes. |
| FR-06 | Attendance | Organizers can generate an on-site QR code, attendees can record attendance, and students can view attendance history. |
| FR-07 | Venue management | Organizers and administrators can check availability. Organizers request a venue and administrators approve or reject the request without double-booking it. |
| FR-08 | Event planning | Organizers can maintain event drafts, proposals, registrations, attendance, announcements, and preparation-task checklists. |
| FR-09 | Analytics and reporting | Organizers and administrators can view registration and attendance figures, while administrators can review venue-utilization reports. |
| FR-10 | Automated scheduling | A GA assigns events to suitable venues and timeslots while respecting the defined hard constraints and optimizing soft preferences. |
| FR-11 | Scheduling comparison | The system provides evidence comparing automated and manual schedules using conflicts, venue utilization, and scheduling efficiency. |

## Scheduling and Genetic Algorithm Requirements

The intelligent scheduling module is the main distinguishing feature of this project.

### Proposed representation

- A **gene** represents an event assignment to a venue and timeslot.
- A **chromosome** represents one complete candidate event schedule.
- A **population** contains multiple candidate schedules evaluated over successive generations.

### Hard constraints

Candidate schedules should be penalized or rejected when they:

- allocate an event to a venue below its required capacity;
- allocate a venue outside its available dates or times;
- use a venue during a blackout or maintenance period;
- double-book a venue during overlapping times;
- schedule the same event more than once; or
- place an event in a timeslot shorter than its required duration.

### Soft constraints and optimization goals

- Satisfy organizer venue and time preferences where possible.
- Maximize venue utilization without inappropriate over-allocation.
- Minimize unused capacity and undesirable timetable gaps.
- Minimize the number and severity of scheduling conflicts.

### GA process

1. Generate an initial population of candidate schedules.
2. Calculate a fitness score for each candidate.
3. Select higher-quality candidates for reproduction.
4. Apply crossover to combine assignments.
5. Apply mutation to maintain diversity and explore alternatives.
6. Repeat until a stopping condition is reached.
7. Store and present the best schedule with its score and comparison metrics.

The implementation is intended for moderate-scale university scheduling. It is not expected to solve extremely large enterprise scheduling problems.

## Non-Functional Requirements

| Category | Requirement |
|---|---|
| Usability | Registration and attendance should require few steps and provide immediate feedback. |
| Performance | Browsing and search results should return within 2 seconds under normal campus load. Time-sensitive notifications should be sent within 10 seconds. |
| Reliability | Registration operations should be transaction-safe and prevent duplicate or inconsistent records. |
| Availability | Target availability is 99% during the semester. |
| Security | Enforce role-based access, minimize stored personal data, protect user records, and audit administrator actions. |
| Maintainability | Keep functionality modular so defects and changes can be isolated. |
| Scalability | Allow growth in users, events, registrations, schedules, and attendance records. |
| Compatibility | Support modern web browsers. |

## Current Implementation Status

This table distinguishes repository functionality from the target requirements in the FYP1 report.

| Module | Status | Notes |
|---|---|---|
| Laravel application setup | Implemented | Laravel 13 application with Blade and MySQL configuration. |
| Responsive UI foundation | Implemented | Shared Blade layout, Flowbite components, responsive sidebar, forms, tables, alerts, and empty states. |
| MySQL database | Implemented | Database and framework/application migrations are configured. |
| Event management | Implemented | Organizer-owned drafts include type, committee, capacity, duration, lifecycle state, review history, and optimizer preferences. |
| Event proposal workflow | Implemented | Organizers submit drafts; administrators approve them or return them with a reason. |
| Venue management | Implemented | CRUD includes capacity, location, active/inactive availability, and blackout periods. |
| Timeslot management | Implemented | Basic CRUD with date and start/end validation. |
| Manual schedule management | Implemented | Assigns an event to a venue and timeslot. |
| Venue capacity validation | Implemented | A schedule is rejected when event capacity exceeds venue capacity. |
| Venue overlap detection | Implemented | Overlapping use of the same venue on the same date is rejected. |
| One schedule per event | Implemented | Application validation prevents duplicate event assignments. |
| Timeslot duration validation | Implemented | A timeslot shorter than the event duration is rejected. |
| Venue blackout validation | Implemented | Assignments overlapping a venue blackout are rejected. |
| Authentication | Implemented | Student registration, login, logout, password recovery, secure sessions, and login throttling are available. |
| Role-based access control | Implemented | Student, organizer, and administrator roles have separate dashboards and server-enforced route permissions. |
| Event publication | Implemented | Administrators can publish scheduled events to the student catalogue or unpublish them while preserving registrations. |
| Student event discovery and registration | Implemented | Students can search and filter published events, view details, register, cancel, and review `My Events`. |
| Registration capacity protection | Implemented | Transactional registration rejects full events and preserves one registration record per student/event. |
| Student schedule-clash detection | Implemented | Registration is rejected when a published event overlaps another confirmed event in `My Events`. |
| Notifications and reminders | Implemented | Registered students receive in-app announcements and idempotent reminders one week, one day, and one hour before events. |
| Personal calendar | Implemented | Registered events and student-created classes, tests, meetings, study blocks, and personal items share a monthly timeline. |
| Personal commitment clashes | Implemented | Overlapping calendar entries are highlighted; event registration is blocked when it overlaps a recorded commitment. |
| QR attendance and history | Implemented | Organizers open encrypted, time-limited QR sessions; registered students confirm once and receive an attendance history. |
| Manual attendance fallback | Implemented | Authorized organizers can record a registered student during an active session when QR scanning is unavailable. |
| Venue request and approval workflow | Implemented | Organizers request a venue and timeslot for approved events; administrator approval creates the schedule transactionally. |
| Organizer planning workflow | Implemented for current scope | Event planning workspaces provide priority tasks, due dates, completion tracking, and participant announcements. |
| Analytics and utilization reports | Implemented for current scope | Date-filtered event-performance and venue-utilization reports calculate registrations, attendance, allocated capacity, and occupied minutes, with organizer ownership scoping and CSV export. |
| Genetic Algorithm optimizer | Implemented | Population initialization, tournament selection, crossover, mutation, elitism, fitness scoring, run persistence, and transactional application are available. |
| Automated/manual comparison | Implemented for current scope | Administrators compare assignment count, conflicts, capacity utilization, unused seats, fitness, and execution time. Seeded repeated experiments provide reproducible benchmark evidence. |
| User evaluation | Implemented for current scope | Every authenticated role can submit one consent-based 1–5 usability response and update it; administrators see aggregated averages and role summaries. |
| Automated tests | Implemented for current scope | Authentication, authorization, workflows, scoping, scheduling constraints, seeded GA reproducibility, reports, CSV exports, and evaluation consent are covered. |

## Current Data Model

The implemented prototype uses these domain tables:

- `events`: organizer ownership, requirements, venue/date/time preferences, lifecycle status, and review details;
- `venues`: name, location, capacity, description, and active state;
- `timeslots`: date, start time, and end time;
- `event_schedules`: event, venue, timeslot, and schedule source/status; and
- `venue_requests`: requested event/venue/timeslot, organizer notes, review status, administrator notes, and reviewer details;
- `venue_blackouts`: venue-specific unavailable date/time ranges and reasons; and
- `event_registrations`: student/event registration status plus registration and cancellation timestamps;
- `personal_commitments`: student-owned class, test, meeting, study, or personal date/time blocks;
- `event_tasks`: event preparation checklist items with priority, due date, and completion state;
- `event_announcements`: organizer-authored participant updates;
- `notifications`: per-user in-app announcement and reminder payloads;
- `reminder_deliveries`: unique registration/milestone records preventing duplicate reminders;
- `attendance_sessions`: encrypted, time-limited check-in tokens created by organizers;
- `attendance_records`: unique student/event attendance evidence with QR or manual method and timestamp;
- `optimization_runs`: GA parameters, best fitness, conflict count, utilization, runtime, and applied state;
- `optimization_assignments`: persisted event/venue/timeslot genes and their soft-constraint results;
- `optimization_experiments`: controlled GA parameters, dataset identifiers, sequential seeds, individual results, and aggregate benchmark metrics;
- `user_evaluations`: one consented usability response per user with four 1–5 measures, role snapshot, comments, and submission time;
- `users`: Laravel identity fields and a typed student, organizer, or administrator role.

`event_schedules` belongs to an event, venue, and timeslot. Deleting any of those parent records cascades to its related schedules.

The remaining target design includes security/performance testing, broader controlled datasets and participants, audit reporting, and any external push-notification delivery selected for deployment.

## Technology Stack

- Backend: PHP 8.3+ and Laravel 13
- Frontend: Blade, Tailwind CSS 4, and Vite 8
- Database: MySQL
- Testing: PHPUnit 12
- Development methodology: Kanban
- UI foundation: Flowbite 4
- QR generation: Endroid QR Code 6

## Local Setup with MySQL

### Prerequisites

- PHP 8.3 or later with `pdo_mysql`
- Composer
- MySQL
- Node.js and npm

### Installation

1. Clone the repository and enter its directory.
2. Install PHP dependencies:

   ```bash
   composer install
   ```

3. Create the MySQL database:

   ```sql
   CREATE DATABASE event_management_system
       CHARACTER SET utf8mb4
       COLLATE utf8mb4_unicode_ci;
   ```

4. Create the environment file:

   ```bash
   cp .env.example .env
   ```

5. Confirm the database settings in `.env`:

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=event_management_system
   DB_USERNAME=root
   DB_PASSWORD=
   ```

6. Generate the application key and migrate the database:

   ```bash
   php artisan key:generate
   php artisan migrate --seed
   ```

7. Install and build frontend dependencies:

   ```bash
   npm install
   npm run build
   ```

8. Start the development environment:

   ```bash
   composer run dev
   ```

The application is normally available at `http://127.0.0.1:8000`.

## Current Prototype Routes

| URL | Purpose |
|---|---|
| `/` | Default landing page |
| `/login` | User sign-in |
| `/register` | Student self-registration |
| `/forgot-password` | Password recovery |
| `/dashboard` | Role-specific authenticated dashboard |
| `/events` | Event CRUD |
| `/events/{event}/planning` | Organizer preparation checklist and announcements |
| `/events/{event}/attendance` | Organizer QR session and participant check-in management |
| `/analytics` | Organizer/admin registration and attendance analytics |
| `/discover` | Searchable student event catalogue |
| `/discover/{event}` | Published event details and registration action |
| `/my-events` | Student’s confirmed event registrations |
| `/calendar` | Student’s combined event and personal commitment timeline |
| `/commitments/create` | Add a class, test, meeting, study block, or personal calendar item |
| `/notifications` | Student announcement and reminder inbox |
| `/attendance-history` | Student’s confirmed attendance history |
| `/check-in/{token}` | Time-limited student attendance confirmation reached through QR scanning |
| `/proposals` | Administrator event-proposal review queue |
| `/venue-requests` | Organizer and administrator venue-request workflow |
| `/venues` | Venue CRUD |
| `/venues/{venue}/blackouts` | Administrator venue availability blocks |
| `/timeslots` | Timeslot CRUD |
| `/schedules` | Manual schedule CRUD |
| `/optimizer` | Administrator GA configuration and run history |
| `/optimizer/{run}` | Persisted candidate assignments, fitness, and safe apply action |
| `/optimizer/comparison` | Manual-versus-generated schedule metrics |
| `/experiments` | Administrator-controlled repeated GA benchmarks with reproducible seeds |
| `/reports` | Date-filtered event-performance and venue-utilization evidence |
| `/reports/events.csv` | Role-scoped event report export |
| `/reports/venues.csv` | Role-scoped venue-utilization export |
| `/reports/experiments.csv` | Administrator GA benchmark export |
| `/evaluation` | Consent-based usability questionnaire for every authenticated role |
| `/evaluation-results` | Administrator aggregate evaluation results |
| `/up` | Laravel health check |

The management routes require authentication. Organizers can manage only their own events, submit proposals, and request venues for approved events. Administrators review proposals and venue requests and manage all events, venues, blackouts, timeslots, and schedules.

### Development accounts

After running `php artisan db:seed`, the following local demonstration accounts are available. All use the password `password` and must not be used in production.

| Role | Email |
|---|---|
| Student | `student@example.com` |
| Event organizer | `organizer@example.com` |
| Administrator | `admin@example.com` |

Password-reset emails use the configured Laravel mail driver. The default local configuration writes them to the application log.

## Testing

For a complete role-by-role manual walkthrough, expected results, negative cases, and a reusable test completion sheet, see the [Manual End-to-End Testing Guide](docs/MANUAL_TESTING_GUIDE.md).

Run the current test suite with:

```bash
php artisan test
```

The current suite contains 51 tests with 205 assertions. Future work should add larger controlled GA datasets, production-like load and security testing, and user-acceptance evidence collected from representative participants.

### Scheduled reminders

`composer run dev` starts Laravel's local scheduler alongside the application. In production, configure the standard Laravel scheduler cron entry so `php artisan schedule:run` executes every minute. You can also trigger the idempotent reminder check manually:

```bash
php artisan events:send-reminders
```

## Project Boundaries

As defined in the FYP1 report, this project is a functional academic prototype rather than a complete enterprise platform.

The implemented optimizer considers:

- venue capacity;
- venue and timeslot availability;
- blackout dates; and
- organizer preferences.

The following are outside the original scope:

- event-type dependency scheduling;
- multi-day event optimization;
- payment gateways;
- external calendar synchronization;
- native mobile applications; and
- optimization for extremely large scheduling datasets.

## Recommended Implementation Roadmap

1. **Completed:** Add authentication, the three user roles, protected routes, and role-specific dashboard foundations.
2. **Completed:** Expand the event and venue domain with organizer ownership, lifecycle states, proposals, venue requests, availability, blackout dates, administrator approval, and a shared scheduling-constraint service.
3. **Completed for the current scope:** Add registrations, a personal calendar, organizer tasks, announcements, notifications, and attendance records.
4. **Completed:** Implement event publication, student discovery, filtering, capacity-safe registration, cancellation, schedule-clash detection, and `My Events`.
5. **Completed for the current scope:** Add reminders, announcements, secure QR attendance, participation histories, and initial analytics.
6. **Completed for the current workflow:** Centralize schedule validation and use a transaction when venue approval creates a schedule.
7. **Completed:** Implement and test the GA chromosome, fitness function, tournament selection, crossover, mutation, elitism, stopping conditions, and result persistence.
8. **Completed for the current evaluation scope:** Compare manual and generated schedules; run reproducible seeded GA benchmarks; export date-filtered event, venue, and experiment evidence; and collect consented usability ratings.
9. **Partially completed:** Automated feature and authorization coverage is in place. Complete production-like security/performance testing and conduct formal user acceptance with representative participants.

## License and Report Notice

The application repository is distributed under the [MIT License](LICENSE). The linked FYP1 report contains a separate Universiti Telekom Sdn. Bhd. copyright notice and should not be redistributed independently without the required permission and acknowledgement.
