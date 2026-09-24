<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AttendanceCheckInController;
use App\Http\Controllers\AttendanceHistoryController;
use App\Http\Controllers\AttendanceSessionController;
use App\Http\Controllers\AutomaticEventSchedulingController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventAnnouncementController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventDiscoveryController;
use App\Http\Controllers\EventPlanningController;
use App\Http\Controllers\EventProposalController;
use App\Http\Controllers\EventRegistrationController;
use App\Http\Controllers\EventScheduleController;
use App\Http\Controllers\EventTaskController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OptimizationExperimentController;
use App\Http\Controllers\PersonalCommitmentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SocietyController;
use App\Http\Controllers\UserEvaluationController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\VenueBlackoutController;
use App\Http\Controllers\VenueController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('evaluation', [UserEvaluationController::class, 'edit'])->name('evaluation.edit');
    Route::put('evaluation', [UserEvaluationController::class, 'update'])->name('evaluation.update');

    Route::get('events', [EventController::class, 'index'])
        ->middleware('role:organizer,administrator')->name('events.index');

    Route::middleware('role:organizer')->group(function (): void {
        Route::resource('events', EventController::class)->only(['create', 'store', 'edit', 'update', 'destroy']);
        Route::post('events/{event}/submit', [EventProposalController::class, 'submit'])->name('events.submit');
        Route::get('events/{event}/allocate', [AutomaticEventSchedulingController::class, 'create'])->name('events.allocation.create');
        Route::post('events/{event}/allocate', [AutomaticEventSchedulingController::class, 'store'])->name('events.allocation.store');
    });

    Route::middleware('role:organizer,administrator')->group(function (): void {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/events.csv', [ReportController::class, 'eventsCsv'])->name('reports.events.csv');
        Route::get('reports/venues.csv', [ReportController::class, 'venuesCsv'])->name('reports.venues.csv');
        Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('events/{event}/planning', [EventPlanningController::class, 'show'])->name('events.planning');
        Route::get('events/{event}/attendance', [AttendanceSessionController::class, 'show'])->name('events.attendance.show');
        Route::post('events/{event}/attendance/sessions', [AttendanceSessionController::class, 'store'])->name('events.attendance.sessions.store');
        Route::patch('events/{event}/attendance/sessions/{session}/close', [AttendanceSessionController::class, 'close'])->name('events.attendance.sessions.close');
        Route::post('events/{event}/attendance/sessions/{session}/manual', [AttendanceSessionController::class, 'manual'])->name('events.attendance.manual');
        Route::post('events/{event}/tasks', [EventTaskController::class, 'store'])->name('events.tasks.store');
        Route::patch('events/{event}/tasks/{task}/toggle', [EventTaskController::class, 'toggle'])->name('events.tasks.toggle');
        Route::delete('events/{event}/tasks/{task}', [EventTaskController::class, 'destroy'])->name('events.tasks.destroy');
        Route::post('events/{event}/announcements', [EventAnnouncementController::class, 'store'])->name('events.announcements.store');
        Route::delete('events/{event}/announcements/{announcement}', [EventAnnouncementController::class, 'destroy'])->name('events.announcements.destroy');
    });

    Route::middleware('role:student')->group(function (): void {
        Route::get('discover', [EventDiscoveryController::class, 'index'])->name('discover.index');
        Route::get('discover/{event}', [EventDiscoveryController::class, 'show'])->name('discover.show');
        Route::get('my-events', [EventRegistrationController::class, 'index'])->name('my-events.index');
        Route::post('events/{event}/register', [EventRegistrationController::class, 'store'])->name('events.register');
        Route::delete('events/{event}/register', [EventRegistrationController::class, 'destroy'])->name('events.registration.destroy');
        Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
        Route::resource('commitments', PersonalCommitmentController::class)->only(['create', 'store', 'edit', 'update', 'destroy']);
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::patch('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::get('notifications/{notification}', [NotificationController::class, 'read'])->name('notifications.read');
        Route::get('attendance-history', AttendanceHistoryController::class)->name('attendance.history');
        Route::get('check-in/{token}', [AttendanceCheckInController::class, 'show'])->name('attendance.check-in.show');
        Route::post('check-in/{token}', [AttendanceCheckInController::class, 'store'])->name('attendance.check-in.store');
    });

    Route::middleware('role:administrator')->group(function (): void {
        Route::resource('users', UserManagementController::class)->only(['index', 'edit', 'update']);
        Route::get('experiments', [OptimizationExperimentController::class, 'index'])->name('experiments.index');
        Route::post('experiments', [OptimizationExperimentController::class, 'store'])->name('experiments.store');
        Route::get('experiments/{experiment}', [OptimizationExperimentController::class, 'show'])->name('experiments.show');
        Route::get('reports/experiments.csv', [ReportController::class, 'experimentsCsv'])->name('reports.experiments.csv');
        Route::get('evaluation-results', [UserEvaluationController::class, 'results'])->name('evaluation.results');
        Route::get('proposals', [EventProposalController::class, 'index'])->name('proposals.index');
        Route::patch('proposals/{event}/approve', [EventProposalController::class, 'approve'])->name('proposals.approve');
        Route::patch('proposals/{event}/reject', [EventProposalController::class, 'reject'])->name('proposals.reject');
        Route::resource('venues', VenueController::class)->except('show');
        Route::resource('societies', SocietyController::class)->except('show');
        Route::get('venues/{venue}/blackouts', [VenueBlackoutController::class, 'index'])->name('venues.blackouts.index');
        Route::post('venues/{venue}/blackouts', [VenueBlackoutController::class, 'store'])->name('venues.blackouts.store');
        Route::delete('venues/{venue}/blackouts/{blackout}', [VenueBlackoutController::class, 'destroy'])->name('venues.blackouts.destroy');
        Route::get('schedules', [EventScheduleController::class, 'index'])->name('schedules.index');
    });
});
