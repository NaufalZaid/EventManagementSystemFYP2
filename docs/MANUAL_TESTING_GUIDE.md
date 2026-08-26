# Manual End-to-End Testing Guide

This guide verifies the Event Management System from the perspective of guests, students, event organizers, and administrators. It covers the main success paths, cross-role handoffs, validation failures, access control, reporting, the Genetic Algorithm (GA), performance testing, and user feedback.

## 1. Test objective

Confirm that:

- each role can access only its permitted features;
- an event can move through its complete lifecycle;
- venue, capacity, duration, blackout, overlap, and personal-calendar conflicts are prevented;
- student registration, notifications, attendance, and reporting remain consistent;
- GA results can be reviewed and applied safely; and
- operational reports, performance results, and user feedback can be collected and reviewed.

Use this result notation while testing:

- `[ ]` Not tested
- `[P]` Passed
- `[F]` Failed
- `[B]` Blocked

For every failure, record the test ID, screenshot, user role, input data, expected result, actual result, browser, and time.

## 2. Test environment preparation

### Prerequisites

- MySQL is running.
- `.env` uses `DB_CONNECTION=mysql` and the correct local database credentials.
- PHP, Composer, Node.js, and npm dependencies are installed.
- Application migrations have run successfully.

Run these non-destructive checks:

```bash
php artisan about
php artisan migrate:status
php artisan test
npm run build
```

Start the development environment:

```bash
composer run dev
```

Open `http://127.0.0.1:8000`.

### Optional clean test database

> Warning: `migrate:fresh` deletes every table and all existing data in the configured database. Use it only on a disposable local development database.

```bash
php artisan migrate:fresh --seed
```

To preserve existing records and only ensure that the seeded test accounts exist, run:

```bash
php artisan db:seed
```

### Seeded test accounts

All seeded accounts use the password `password`.

| Role | Email |
|---|---|
| Student | `student@example.com` |
| Event organizer | `organizer@example.com` |
| Administrator | `admin@example.com` |

Use separate browser profiles, private windows, or different browsers for the organizer, administrator, and student. This makes cross-role handoffs much easier to test.

### Recommended test data

Choose future dates so registration, calendars, reminders, and GA eligibility behave predictably.

| Record | Suggested values |
|---|---|
| Primary venue | Test Main Hall, capacity 120, active |
| Small venue | Test Small Room, capacity 20, active |
| Inactive venue | Test Closed Hall, capacity 200, inactive |
| Morning slot | A future date, 09:00–11:00 |
| Overlap slot | Same date, 10:00–12:00 |
| Afternoon slot | Same or another future date, 14:00–16:00 |
| Primary event | UAT Test Workshop, capacity 80, duration 60 minutes |
| Oversized event | Capacity 150, duration 60 minutes |
| Long event | Capacity 50, duration 180 minutes |

## 3. Master lifecycle scenario

Run this scenario first. It gives later tests a published event, registration, notification, and attendance record.

### A. Administrator prepares scheduling data

- [P] `E2E-01` Sign in as `admin@example.com` and confirm the administrator dashboard opens.
- [P] `E2E-02` Open **Venues**, create **Test Main Hall** with capacity `120`, and keep it active.
  - Expected: success message appears and the venue is listed.
- [P] `E2E-03` Open **Timeslots**, create a future `09:00–11:00` slot.
  - Expected: the date and times are shown in the timeslot list.

### B. Organizer creates and submits an event

- [P] `E2E-04` Sign in as `organizer@example.com` and open **Events**.
- [P] `E2E-05` Create **UAT Test Workshop** with type `Workshop`, committee `UAT Test Team`, capacity `80`, duration `60`, a description, and optional optimizer preferences.
  - Expected: the event is saved as a draft and belongs to the organizer.
- [P] `E2E-06` Edit the draft and confirm the changes persist.
- [P] `E2E-07` Submit the event for review.
  - Expected: status changes from **Draft** to **Submitted** and editing is no longer offered.

### C. Administrator reviews the proposal

- [P] `E2E-08` As administrator, open **Proposals** and locate **UAT Test Workshop**.
- [P] `E2E-09` Reject it once with a clear reason such as `Please clarify the event description.`
  - Expected: status becomes **Rejected** and the reason is visible to the organizer.
- [P] `E2E-10` As organizer, edit the rejected event and submit it again.
  - Expected: the old rejection is cleared and the event returns to **Submitted**.
- [P] `E2E-11` As administrator, approve the resubmitted proposal.
  - Expected: status becomes **Approved** and it becomes eligible for a venue request or GA scheduling.

### D. Organizer requests a venue

- [P] `E2E-12` As organizer, open **Venue Requests**, create a request for the approved event, **Test Main Hall**, and the future morning slot.
  - Expected: a single **Pending** request appears.
- [P] `E2E-13` As administrator, reject this request once with an administrator note.
  - Expected: it becomes **Rejected** and the organizer can create another request.
- [P] `E2E-14` As organizer, submit the request again.
- [P] `E2E-15` As administrator, approve the request.
  - Expected: the request becomes **Approved**, one schedule is created, and the event status becomes **Scheduled**.

### E. Administrator publishes the event

- [P] `E2E-16` As administrator, open **Events** and publish **UAT Test Workshop**.
  - Expected: status becomes **Published** and the event appears in student discovery.

### F. Student registers

- [P] `E2E-17` As `student@example.com`, open **Discover** and search for `UAT Test Workshop`.
  - Expected: the published event appears with its schedule and availability.
- [P] `E2E-18` Open the event details and register.
  - Expected: confirmation appears and the event is present in **My Events** and **Calendar**.

### G. Organizer plans and communicates

- [P] `E2E-19` As organizer, open the event planning workspace.
- [P] `E2E-20` Add low-, medium-, and high-priority preparation tasks with due dates.
  - Expected: tasks are listed with the correct priority and due date.
- [P] `E2E-21` Mark a task complete, toggle it back, and remove one task.
  - Expected: completion counts and task styling update correctly.
- [P] `E2E-22` Send an announcement to registered students.
  - Expected: it is stored in the event workspace and a notification is created for the registered student.
- [P] `E2E-23` As student, open **Notifications**, read the announcement, and use **Mark all as read**.
  - Expected: notification content is correct and unread indicators clear.

### H. Attendance

- [P] `E2E-24` As organizer, open the event’s attendance page and start a QR session for at least five minutes.
  - Expected: a QR code and active-session information appear.
- [P] `E2E-25` Scan the QR code with the student device, or open its check-in URL in the student browser, then confirm check-in.
  - Expected: one QR attendance record is created.
- [P] `E2E-26` Attempt the same check-in again.
  - Expected: duplicate attendance is rejected and the original record remains unchanged.
- [P] `E2E-27` As student, open **Attendance History**.
  - Expected: the event, venue, date, and check-in method/time are displayed.
- [P] `E2E-28` As organizer, confirm the attended count increased, then close the attendance session.
  - Expected: the session becomes inactive and further check-ins are rejected.

### I. Reports and feedback

- [P] `E2E-29` As organizer, open **Analytics** and **Reports**.
  - Expected: registrations and attendance are reflected; reports show only events belonging to this organizer.
- [P] `E2E-30` Select a date range containing the event and download Event CSV and Venue CSV.
  - Expected: both files download, contain headers, and include the correct event/venue metrics.
- [P] `E2E-31` As each role, open **Give Feedback**, provide all four ratings, accept consent, and submit.
  - Expected: each user has one saved response and can update it later.
- [P] `E2E-32` As administrator, open **Feedback Results**.
  - Expected: overall averages, role summaries, response count, and comments are visible without displaying participant identity.

## 4. Guest and authentication tests

- [P] `AUTH-01` Visit the landing, login, registration, and forgot-password pages while signed out.
  - Expected: all public pages render normally.
- [P] `AUTH-02` Register a new account with a unique email.
  - Expected: the account is created as **Student**, never organizer or administrator.
- [P] `AUTH-03` Submit registration with mismatched passwords or an existing email.
  - Expected: validation messages appear and no duplicate user is created.
- [P] `AUTH-04` Log in with a valid seeded account.
  - Expected: redirect to that role’s dashboard.
- [P] `AUTH-05` Log in with an incorrect password.
  - Expected: authentication is rejected without revealing whether an account exists.
- [P] `AUTH-06` Request a password-reset link.
  - Expected: a neutral confirmation is shown. With the default log mailer, inspect `storage/logs/laravel.log` for the local test message.
- [P] `AUTH-07` Log out and use the browser Back button.
  - Expected: protected pages cannot be used and a fresh protected request redirects to login.
- [P] `AUTH-08` Repeatedly submit invalid login credentials.
  - Expected: login throttling eventually limits attempts.

## 5. Role and authorization tests

Enter restricted URLs directly as well as checking that their navigation links are hidden.

- [P] `ROLE-01` As student, try `/events`, `/venues`, `/timeslots`, `/schedules`, `/proposals`, `/optimizer`, `/experiments`, `/reports`, and `/evaluation-results`.
  - Expected: management routes are forbidden, while the feedback form at `/evaluation` is allowed.
- [P] `ROLE-02` As organizer, try `/venues`, `/timeslots`, `/schedules`, `/proposals`, `/optimizer`, `/experiments`, and `/evaluation-results`.
  - Expected: administrator-only routes are forbidden; organizer events, venue requests, analytics, reports, and the feedback form are allowed.
- [P] `ROLE-03` As administrator, verify access to all administrative master-data, proposal, request, schedule, optimizer, performance-test, report, and feedback-result screens.
- [P] `ROLE-04` While signed out, visit a protected URL directly.
  - Expected: redirect to login.
- [B] `ROLE-05` Attempt to edit or operate on a record owned by another user by changing its numeric URL ID.
  - Expected: `403 Forbidden` or `404 Not Found`; the record is unchanged.

## 6. Administrator tests

### Venue management

- [P] `ADMIN-VEN-01` Create, view, edit, activate/deactivate, and delete a disposable venue.
- [P] `ADMIN-VEN-02` Submit a venue with missing name, negative capacity, or invalid availability.
  - Expected: validation prevents saving.
- [P] `ADMIN-VEN-03` Create a full-day blackout for **Test Main Hall** on a future date.
  - Expected: blackout appears on the venue’s blackout screen.
- [P] `ADMIN-VEN-04` Create a partial blackout, then edit test inputs so start time is after end time.
  - Expected: valid blackout saves; invalid range is rejected.
- [P] `ADMIN-VEN-05` Remove a disposable blackout.
  - Expected: it disappears and scheduling becomes available again.

### Timeslots and schedules

- [P] `ADMIN-SCH-01` Create, edit, and delete a disposable timeslot.
- [P] `ADMIN-SCH-02` Create a timeslot whose end is not after its start.
  - Expected: validation rejects it.
- [P] `ADMIN-SCH-03` Manually schedule an approved, unscheduled event into a suitable active venue and sufficiently long slot.
  - Expected: one schedule is created and the event becomes scheduled.
- [P] `ADMIN-SCH-04` Try to schedule an 80-person event into **Test Small Room** with capacity 20.
  - Expected: insufficient-capacity error; no schedule is created.
- [P] `ADMIN-SCH-05` Try to schedule a 180-minute event into a 120-minute slot.
  - Expected: insufficient-duration error.
- [P] `ADMIN-SCH-06` Try to schedule an event during a venue blackout.
  - Expected: blackout conflict is reported.
- [P] `ADMIN-SCH-07` Schedule one event, then try to use the same venue in an overlapping timeslot for another event.
  - Expected: double-booking is rejected.
- [P] `ADMIN-SCH-08` Try to create a second schedule for the same event.
  - Expected: duplicate event assignment is rejected.
- [P] `ADMIN-SCH-09` Edit and delete a disposable manual schedule.
  - Expected: changes persist; deleting does not remove the event, venue, or timeslot.

### Proposal and venue-request review

- [P] `ADMIN-REV-01` Approve a submitted proposal.
- [P] `ADMIN-REV-02` Reject another submitted proposal without a reason.
  - Expected: rejection reason is required.
- [P] `ADMIN-REV-03` Try approving an already approved or rejected proposal.
  - Expected: invalid status transition is rejected.
- [P] `ADMIN-REV-04` Reject a pending venue request without administrator notes.
  - Expected: notes are required.
- [P] `ADMIN-REV-05` Approve a request after another schedule has made it conflict.
  - Expected: constraints are checked again at approval time and approval is rejected.

### Publication

- [P] `ADMIN-PUB-01` Try publishing a draft, submitted, approved-but-unscheduled, or rejected event.
  - Expected: only a scheduled event can be published.
- [P] `ADMIN-PUB-02` Publish a scheduled event.
  - Expected: it appears in student discovery.
- [P] `ADMIN-PUB-03` Unpublish an event with an existing registration.
  - Expected: it disappears from discovery but the existing registration is preserved.
- [P] `ADMIN-PUB-04` Publish it again.
  - Expected: existing registration still exists and discovery resumes.

### GA optimizer

Prepare at least two approved, unscheduled events, two active venues, and two future timeslots.

- [P] `ADMIN-GA-01` Open **GA Optimizer** and confirm eligible event, venue, and slot counts.
- [P] `ADMIN-GA-02` Run with population `20`, generations `20`, mutation `0.08`, and seed `12345`.
  - Expected: a persisted run shows fitness, conflicts, utilization, runtime, seed, assignments, and preference results.
- [P] `ADMIN-GA-03` Run again against the unchanged dataset using seed `12345` and the same parameters.
  - Expected: evolutionary assignments and fitness are reproducible; measured runtime may differ.
- [P] `ADMIN-GA-04` Review a zero-hard-conflict result and apply it.
  - Expected: schedules are created transactionally, events become scheduled, and the run is marked applied.
- [P] `ADMIN-GA-05` Create an infeasible dataset, such as only a venue smaller than the event, and run the optimizer.
  - Expected: the result records hard conflicts and cannot be applied.
- [P] `ADMIN-GA-06` Open **Compare Schedules**.
  - Expected: manual and generated counts, conflicts, utilization, unused seats, fitness, and runtime appear.
- [P] `ADMIN-GA-07` Submit values outside the accepted population, generation, mutation, or seed ranges.
  - Expected: validation errors appear and no run is stored.

### Repeatable GA performance tests

- [P] `ADMIN-EXP-01` Open **GA Performance Tests** with eligible data available.
- [P] `ADMIN-EXP-02` Run a named performance test with repetitions `3`, population `20`, generations `20`, mutation `0.08`, and base seed `700`.
  - Expected: three trials use seeds `700`, `701`, and `702` and aggregate success rate, fitness, utilization, and runtime are saved.
- [P] `ADMIN-EXP-03` Open the saved performance test later.
  - Expected: controlled parameters, dataset IDs, aggregate metrics, and trial results remain available.
- [P] `ADMIN-EXP-04` Download the performance-results CSV.
  - Expected: the file includes the test and its aggregate metrics.
- [ ] `ADMIN-EXP-05` Try a performance test without approved unscheduled events, active venues, or future timeslots.
  - Expected: execution is rejected with an explanation and no invalid result is saved.

### Administrator reporting and feedback

- [P] `ADMIN-REP-01` Open **Reports** and choose a valid date range.
  - Expected: events from all organizers in the period are included.
- [P] `ADMIN-REP-02` Choose a range that contains no scheduled events.
  - Expected: clear empty states appear.
- [P] `ADMIN-REP-03` Set the `To` date earlier than `From`.
  - Expected: date validation rejects the filter.
- [P] `ADMIN-REP-04` Download Event, Venue, and GA performance-results CSV files and open them in a spreadsheet application.
  - Expected: headers and data columns align, dates/numbers are usable, and no HTML is present.
- [P] `ADMIN-EVAL-01` Open **Feedback Results** before and after responses are submitted.
  - Expected: empty/aggregate states render safely, role counts are correct, and respondent names/emails are not shown.

## 7. Organizer tests

### Event ownership and lifecycle

- [P] `ORG-EVT-01` Create a draft with every field, including venue/date/start-time preferences.
- [P] `ORG-EVT-02` Create a minimum valid draft with optional fields blank.
- [P] `ORG-EVT-03` Submit missing title, type, capacity, or duration, and try negative/invalid numbers.
  - Expected: field-level validation appears and invalid data is not saved.
- [P] `ORG-EVT-04` Edit and delete an owned draft.
- [P] `ORG-EVT-05` Submit an owned draft and attempt to submit it again.
  - Expected: first submission succeeds; invalid repeat transition is rejected.
- [P] `ORG-EVT-06` Attempt to edit a submitted or approved event.
  - Expected: lifecycle rules prevent unauthorized editing.
- [P] `ORG-EVT-07` Open another organizer’s event ID if a second organizer account exists.
  - Expected: access is forbidden.

### Venue requests

- [P] `ORG-REQ-01` Verify only owned, approved, eligible events appear in the venue-request form.
- [P] `ORG-REQ-02` Submit a valid request with organizer notes.
- [P] `ORG-REQ-03` Attempt a second active request for the same event.
  - Expected: duplicate active request is rejected.
- [P] `ORG-REQ-04` Request an undersized venue, too-short timeslot, blackout period, or already occupied venue.
  - Expected: the appropriate constraint message appears and no request is created.
- [P] `ORG-REQ-05` Verify the organizer request list contains only their own requests and administrator notes.

### Planning and announcements

- [P] `ORG-PLAN-01` Add tasks with each priority and optional description/due date.
- [P] `ORG-PLAN-02` Try a blank task or overlong text.
  - Expected: validation rejects it.
- [P] `ORG-PLAN-03` Toggle completion twice and remove a task.
- [P] `ORG-PLAN-04` Try sending an announcement before publication.
  - Expected: the action is unavailable or rejected.
- [P] `ORG-PLAN-05` Send an announcement after publication with zero registrations.
  - Expected: announcement stores successfully and sends zero participant notifications.
- [P] `ORG-PLAN-06` After a student registers, send another announcement.
  - Expected: only active registrants receive it.
- [P] `ORG-PLAN-07` Remove a stored announcement.
  - Expected: the announcement disappears; previously delivered notifications may remain as historical messages.

### Attendance and analytics

- [ ] `ORG-ATT-01` Try opening attendance for an unpublished event.
  - Expected: rejected.
- [ ] `ORG-ATT-02` Start one session, then try starting another while it is active.
  - Expected: duplicate active session is rejected.
- [ ] `ORG-ATT-03` Manually check in an active registered student.
  - Expected: method is recorded as manual and attendance count increases.
- [ ] `ORG-ATT-04` Manually check in the same student again, a cancelled registration, or a registration belonging to another event.
  - Expected: all are rejected without duplicate records.
- [ ] `ORG-ATT-05` Close the session and attempt another manual record.
  - Expected: rejected because the session is inactive.
- [ ] `ORG-ATT-06` Review **Analytics** and verify registration, attendance, and rate calculations.
- [ ] `ORG-ATT-07` Review **Reports** and verify no other organizer’s event is exposed in the page or downloaded CSV.

## 8. Student tests

### Discovery and registration

- [ ] `STU-DIS-01` Search published events by title.
- [ ] `STU-DIS-02` Filter by event type, date, and committee/organizer where available.
  - Expected: filters narrow the catalogue correctly and unpublished events never appear.
- [ ] `STU-DIS-03` Open event details and verify description, organizer, venue, time, capacity, and availability.
- [ ] `STU-REG-01` Register for an event.
  - Expected: one active registration exists and the event appears in **My Events** and **Calendar**.
- [ ] `STU-REG-02` Try registering for the same event again.
  - Expected: duplicate registration is rejected.
- [ ] `STU-REG-03` Cancel the registration.
  - Expected: it leaves active **My Events** but its single database registration record is retained as cancelled.
- [ ] `STU-REG-04` Register again after cancellation.
  - Expected: the existing record is reactivated rather than duplicated.
- [ ] `STU-REG-05` Try registering for an unpublished event by directly submitting its known URL.
  - Expected: rejected.
- [ ] `STU-REG-06` Test event capacity with a capacity-one event and two student accounts.
  - Expected: the first registration succeeds and the second receives a full-capacity message.
- [ ] `STU-REG-07` After attendance is recorded, try cancelling the registration.
  - Expected: cancellation is rejected to preserve the attendance record.

### Calendar and conflict protection

- [ ] `STU-CAL-01` Add a class, test, meeting, study block, and personal commitment on future dates.
  - Expected: each appears in the combined calendar.
- [ ] `STU-CAL-02` Edit and delete an owned commitment.
- [ ] `STU-CAL-03` Create an overlapping commitment.
  - Expected: it saves with a clash warning/highlight so the user can resolve it.
- [ ] `STU-CAL-04` Try adding a commitment whose end is not after its start.
  - Expected: validation rejects it.
- [ ] `STU-CAL-05` Create a commitment overlapping a published event, then try registering for that event.
  - Expected: registration is blocked and names the conflict.
- [ ] `STU-CAL-06` Register for one event, then try registering for another event with an overlapping schedule.
  - Expected: the second registration is blocked.
- [ ] `STU-CAL-07` Attempt to edit/delete another student’s commitment by changing its URL ID.
  - Expected: forbidden or not found.

### Notifications and reminders

- [ ] `STU-NOT-01` Confirm an organizer announcement appears only after this student has registered.
- [ ] `STU-NOT-02` Open a notification.
  - Expected: it becomes read and links to appropriate information.
- [ ] `STU-NOT-03` Use **Mark all as read** with multiple unread items.
- [ ] `STU-NOT-04` Attempt to open another user’s notification ID.
  - Expected: forbidden or not found.
- [ ] `STU-NOT-05` For test events near the one-week, one-day, or one-hour thresholds, run:

  ```bash
  php artisan events:send-reminders
  ```

  - Expected: eligible active registrants receive reminders.
- [ ] `STU-NOT-06` Run the reminder command again for the same milestone.
  - Expected: no duplicate reminder is delivered.

### QR attendance

- [ ] `STU-ATT-01` Open an active QR link while registered and confirm attendance.
- [ ] `STU-ATT-02` Open it while signed out.
  - Expected: authentication is required before check-in.
- [ ] `STU-ATT-03` Open it as an unregistered student.
  - Expected: attendance is rejected.
- [ ] `STU-ATT-04` Reuse the link after the organizer closes it or after expiry.
  - Expected: attendance is rejected.
- [ ] `STU-ATT-05` Attempt a second confirmation.
  - Expected: duplicate attendance is rejected.
- [ ] `STU-ATT-06` Verify **Attendance History** shows only the signed-in student’s records.

### Service feedback

- [ ] `STU-EVAL-01` Submit without selecting all four ratings.
  - Expected: every rating is required.
- [ ] `STU-EVAL-02` Submit without accepting consent.
  - Expected: response is not stored.
- [ ] `STU-EVAL-03` Submit all ratings, consent, and an optional comment.
- [ ] `STU-EVAL-04` Return later and change a rating.
  - Expected: the same response is updated; a second response is not created.

## 9. Cross-cutting validation and resilience

- [ ] `VAL-01` Submit required forms empty.
  - Expected: understandable errors appear near the workflow and typed values are retained where appropriate.
- [ ] `VAL-02` Enter values beyond maximum text lengths.
  - Expected: client/server validation rejects them safely.
- [ ] `VAL-03` Enter HTML or script text in descriptions, notes, announcements, tasks, and feedback comments.
  - Expected: content is displayed as escaped text and does not execute.
- [ ] `VAL-04` Double-click important submit buttons or refresh after submission.
  - Expected: database constraints and application logic prevent harmful duplicates.
- [ ] `VAL-05` Use two browser sessions to approve/register/check in at nearly the same time.
  - Expected: transactions and unique constraints maintain consistent schedules, capacity, and attendance.
- [ ] `VAL-06` Temporarily stop MySQL and load a database-backed page, then restart MySQL.
  - Expected: failure is contained; after restart, the application reconnects and stored data remains intact.
- [ ] `VAL-07` Test desktop and narrow mobile widths.
  - Expected: sidebar/navigation, forms, tables, alerts, QR code, and action buttons remain usable without inaccessible content.
- [ ] `VAL-08` Test keyboard-only navigation and visible focus states.
  - Expected: links, controls, forms, and dialogs can be operated in a logical order.

## 10. Automated regression tests

After manual testing or any code change, run:

```bash
php artisan test
vendor/bin/pint --dirty --test
npm run build
composer validate --no-check-publish
```

Current expected application test result:

```text
51 passed (205 assertions)
```

The automated suite covers authentication, authorization, ownership, proposals, venue requests, scheduling constraints, discovery, registration, personal conflicts, planning, notifications, reminders, QR/manual attendance, analytics, GA generation/application, seeded reproducibility, reports, CSV exports, and feedback consent.

## 11. Test completion summary

Complete this table at the end of a test cycle.

| Area | Passed | Failed | Blocked | Tester notes |
|---|---:|---:|---:|---|
| Environment and authentication |  |  |  |  |
| Role authorization |  |  |  |  |
| Administrator master data |  |  |  |  |
| Proposal and venue approval |  |  |  |  |
| Organizer planning |  |  |  |  |
| Student discovery and registration |  |  |  |  |
| Calendar and conflicts |  |  |  |  |
| Notifications and reminders |  |  |  |  |
| Attendance |  |  |  |  |
| Analytics and reports |  |  |  |  |
| GA optimizer and performance tests |  |  |  |  |
| User feedback |  |  |  |  |
| Validation, resilience, and responsive UI |  |  |  |  |

### Exit criteria

The application is ready to complete the UAT cycle when:

- every master lifecycle test passes;
- no role can access another role’s protected functions or private records;
- no high-severity defect can corrupt schedules, registrations, or attendance;
- automated tests and the production asset build pass;
- CSV exports open correctly;
- GA optimization runs and performance tests have been recorded using controlled data; and
- representative users can complete and update the service feedback form.
