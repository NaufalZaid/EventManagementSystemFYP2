# Event Management System Code Flow Guide

This guide explains how this Laravel project moves from a user action in the frontend to backend processing, database access, and the final browser response. All referenced paths are relative to the project root.

## 1. Standard Laravel request flow

```text
Blade link or form
    -> HTTP request from browser
    -> public/index.php
    -> bootstrap/app.php
    -> web middleware (session, CSRF, auth, role)
    -> routes/web.php
    -> controller method
    -> service and Eloquent model
    -> MySQL
    -> redirect or Blade response
    -> browser displays the next page
```

This project is server-rendered. Most buttons submit a normal HTML form and cause a complete page request. It is not currently a separate JavaScript SPA calling a JSON API.

## 2. Fully traced example: student clicks Register

### Step 1: The frontend creates the button

File: `resources/views/discovery/index.blade.php`

The event card contains a form similar to:

```blade
<form action="{{ route('events.register', $event) }}" method="POST">
    @csrf
    <button>Register</button>
</form>
```

`route('events.register', $event)` generates a URL such as:

```text
/events/12/register
```

`@csrf` creates a hidden security token. Laravel compares this token with the token in the user's session when the form is submitted.

### Step 2: The browser sends the request

When the student clicks **Register**, the browser sends approximately:

```http
POST /events/12/register
Cookie: laravel_session=...
Content-Type: application/x-www-form-urlencoded

_token=the-csrf-token
```

### Step 3: Laravel receives the request

File: `public/index.php`

```php
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->handleRequest(Request::capture());
```

Laravel captures the URL, request method, form data, cookies, session, and current authenticated user.

### Step 4: Middleware checks the request

File: `routes/web.php`

The registration route is inside both the `auth` and `role:student` groups:

```php
Route::middleware('auth')->group(function (): void {
    Route::middleware('role:student')->group(function (): void {
        Route::post('events/{event}/register', [EventRegistrationController::class, 'store'])
            ->name('events.register');
    });
});
```

The checks happen before the controller:

1. The web middleware loads the session.
2. CSRF middleware validates `_token`.
3. `auth` confirms that a user is logged in.
4. `role:student` runs `app/Http/Middleware/EnsureUserHasRole.php`.
5. A non-student receives HTTP 403.

### Step 5: Route model binding loads the event

The URL contains `{event}` and the controller accepts `Event $event`:

```php
public function store(Request $request, Event $event)
```

For `/events/12/register`, Laravel effectively performs:

```php
$event = Event::findOrFail(12);
```

If event 12 does not exist, Laravel returns HTTP 404.

### Step 6: The controller starts a transaction

File: `app/Http/Controllers/EventRegistrationController.php`

```php
DB::transaction(function () use ($request, $event): void {
    // All registration checks and writes happen here.
});
```

If an exception occurs inside the callback, Laravel rolls back all database changes made by the callback.

### Step 7: It locks and verifies the event

```php
$event = Event::with('schedules.timeslot')
    ->lockForUpdate()
    ->findOrFail($event->id);

abort_unless($event->status === EventStatus::Published, 422);
```

The lock helps protect capacity when multiple students register concurrently. Registration is permitted only for a published event with a schedule.

### Step 8: It checks duplicate registration and capacity

The controller looks for an existing `event_registrations` row belonging to the student. It rejects an already-active registration.

It then counts registrations whose status is `registered`:

```php
$registeredCount = EventRegistration::where('event_id', $event->id)
    ->where('status', RegistrationStatus::Registered)
    ->count();
```

If this count is equal to or greater than `events.capacity`, registration stops with a validation error.

### Step 9: It checks the student's calendar

File: `app/Services/CalendarConflictService.php`

The service checks the proposed event time against:

- Other confirmed event registrations.
- Personal commitments such as classes, tests, meetings, and study blocks.

The overlap formula is:

```text
new start < existing end
AND
new end > existing start
```

If the event overlaps anything, the controller throws a validation error and nothing is saved.

### Step 10: Eloquent writes the registration

```php
EventRegistration::updateOrCreate(
    ['event_id' => $event->id, 'user_id' => $request->user()->id],
    [
        'status' => RegistrationStatus::Registered,
        'registered_at' => now(),
        'cancelled_at' => null,
    ]
);
```

For a new registration, Eloquent generates an SQL `INSERT`. For a previously cancelled registration, it generates an SQL `UPDATE` that reactivates the existing row.

The transaction then commits.

### Step 11: Laravel redirects the browser

```php
return redirect()
    ->route('my-events.index')
    ->with('success', 'Registration confirmed. The event is now in My Events.');
```

Laravel returns an HTTP redirect to `/my-events`. The success message is temporarily stored in the session.

### Step 12: The destination page is rendered

The redirected `GET /my-events` request runs `EventRegistrationController::index()`.

It loads the student's active registrations together with event, schedule, venue, and timeslot relationships. It then renders:

```text
resources/views/registrations/index.blade.php
```

The shared layout renders the HTML and the flash-message component displays the success message.

## 3. Event creation example

### Frontend file

`resources/views/events/_form.blade.php`

The form selects its endpoint based on whether an event is being created or edited:

```blade
<form action="{{ $editing ? route('events.update', $event) : route('events.store') }}"
      method="POST">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif
</form>
```

HTML only directly supports GET and POST. `@method('PUT')` creates a hidden `_method=PUT` field, and Laravel treats the request as PUT.

### Route file

`routes/web.php`

```php
Route::resource('events', EventController::class)
    ->except('show')
    ->middleware('role:organizer,administrator');
```

This one declaration creates:

| User action | Method and URL | Controller method |
|---|---|---|
| View events | `GET /events` | `index()` |
| Open create form | `GET /events/create` | `create()` |
| Create event | `POST /events` | `store()` |
| Open edit form | `GET /events/{event}/edit` | `edit()` |
| Save edit | `PUT /events/{event}` | `update()` |
| Delete | `DELETE /events/{event}` | `destroy()` |

### Controller file

`app/Http/Controllers/EventController.php`

`store()` performs these operations:

1. Confirms an organizer belongs to an active society.
2. Validates all submitted values.
3. Applies the working-hours policy.
4. Assigns the current user as the creator.
5. Creates organizer events as drafts.
6. Creates administrator events as already approved.
7. Redirects to the event edit page.

### Model file

`app/Models/Event.php`

The model defines:

- Fields that can be mass assigned.
- Boolean, date, datetime, and enum casts.
- Duration and time normalization.
- Relationships to organizer, society, schedule, registrations, tasks, announcements, and attendance.
- Society-based event access rules.

### Database files

The `events` table is assembled through several migrations, including:

- `database/migrations/2026_04_15_161840_create_events_table.php`
- `database/migrations/2026_04_22_163559_update_events_table_remove_time_fields.php`
- `database/migrations/2026_08_13_000000_extend_events_for_workflow.php`
- `database/migrations/2026_09_17_000000_add_outside_working_hours_to_events_table.php`
- `database/migrations/2026_09_19_000000_create_societies_and_add_ownership.php`

Migrations define database structure. They are run during setup/deployment with `php artisan migrate`; they do not run on each browser request.

## 4. Outside-working-hours field flow

The active migration adds the database column:

File: `database/migrations/2026_09_17_000000_add_outside_working_hours_to_events_table.php`

```php
$table->boolean('is_outside_working_hours')
    ->default(false)
    ->after('duration_minutes');
```

The complete runtime flow is:

```text
Checkbox in resources/views/events/_form.blade.php
    -> POST/PUT form field: is_outside_working_hours=1
    -> EventController::validated()
    -> request->boolean('is_outside_working_hours')
    -> Event model boolean cast
    -> events.is_outside_working_hours
    -> SchedulingTimePolicy chooses 18:00 or 23:00 closing
    -> venue requests, manual schedules, and GA use that rule
```

Normal events:

- Earliest start: 08:00.
- Latest finish: 18:00.
- Maximum duration: 600 minutes.

Outside-working-hours events:

- Earliest start: 08:00.
- Latest finish: 23:00.
- Maximum duration: 900 minutes.

The flag is read in these important files:

- `app/Http/Controllers/EventController.php`
- `app/Http/Controllers/VenueRequestController.php`
- `app/Services/SchedulingTimePolicy.php`
- `app/Services/SchedulingConstraintService.php`
- `app/Services/AutomaticTimeslotService.php`
- `app/Services/GeneticScheduleOptimizer.php`

## 5. Organizer-to-student event lifecycle

```text
Organizer creates event
    events/_form.blade.php
    -> EventController::store()
    -> events row with status=draft

Organizer submits proposal
    events/index.blade.php
    -> POST /events/{event}/submit
    -> EventProposalController::submit()
    -> status=submitted

Administrator approves
    proposals/index.blade.php
    -> PATCH /proposals/{event}/approve
    -> EventProposalController::approve()
    -> status=approved

Organizer requests venue
    venue-requests/create.blade.php
    -> VenueRequestController::store()
    -> venue_requests row with status=pending

Administrator approves venue
    venue-requests/index.blade.php
    -> VenueRequestController::approve()
    -> event_schedules row created
    -> event status=scheduled

Administrator publishes
    events/index.blade.php
    -> EventPublicationController::publish()
    -> event status=published

Student discovers and registers
    discovery/index.blade.php
    -> EventRegistrationController::store()
    -> event_registrations row created

Organizer opens attendance
    attendance/manage.blade.php
    -> AttendanceSessionController::store()
    -> attendance_sessions row and QR code

Student scans and confirms
    attendance/check-in.blade.php
    -> AttendanceCheckInController::store()
    -> attendance_records row created
```

## 6. Main feature-to-file map

| Feature | Frontend view | Controller | Main model/service |
|---|---|---|---|
| Login | `auth/login.blade.php` | `Auth/AuthenticatedSessionController.php` | `Auth/LoginRequest.php`, `User.php` |
| Dashboard | `dashboards/*.blade.php` | `DashboardController.php` | Multiple models |
| Event CRUD | `events/*.blade.php` | `EventController.php` | `Event.php` |
| Proposal review | `proposals/index.blade.php` | `EventProposalController.php` | `Event.php` |
| Venue request | `venue-requests/*.blade.php` | `VenueRequestController.php` | `SchedulingConstraintService.php` |
| Manual schedule | `schedules/*.blade.php` | `EventScheduleController.php` | `EventSchedule.php` |
| Event discovery | `discovery/*.blade.php` | `EventDiscoveryController.php` | `Event.php` |
| Registration | `discovery/*.blade.php` | `EventRegistrationController.php` | `CalendarConflictService.php` |
| Personal calendar | `calendar/index.blade.php` | `CalendarController.php` | `PersonalCommitment.php` |
| Planning tasks | `planning/show.blade.php` | `EventTaskController.php` | `EventTask.php` |
| Announcements | `planning/show.blade.php` | `EventAnnouncementController.php` | Notification classes |
| QR attendance | `attendance/*.blade.php` | Attendance controllers | `QrCodeService.php` |
| GA scheduling | `optimizer/*.blade.php` | `OptimizationRunController.php` | `GeneticScheduleOptimizer.php` |
| Analytics | `analytics/index.blade.php` | `AnalyticsController.php` | Event/attendance models |
| CSV reports | `reports/index.blade.php` | `ReportController.php` | Streamed response |

## 7. How to trace any button yourself

Use this repeatable process:

1. Find the visible text in `resources/views`:

   ```bash
   rg "Register now" resources/views
   ```

2. Read the surrounding `href` or `<form action>` and note the route name.

3. Find that route name:

   ```bash
   rg "events.register" routes app resources/views
   ```

4. Open the controller method named by the route.

5. Follow model calls such as `Event::`, `$event->registrations()`, `create()`, `update()`, or `delete()`.

6. Follow injected services such as `CalendarConflictService` or `SchedulingConstraintService`.

7. Read the final `view()`, `redirect()`, `back()`, or download response.

That path shows the complete flow from the user's click to the result displayed by the browser.
