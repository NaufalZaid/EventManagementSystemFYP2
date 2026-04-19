<?php

use Illuminate\Support\Facades\Route;

Route::get("/", function () {
    return view("welcome");
});

use App\Http\Controllers\EventController;

Route::resource("events", EventController::class);

use App\Http\Controllers\VenueController;

Route::resource("venues", VenueController::class);

use App\Http\Controllers\TimeslotController;

Route::resource('timeslots', TimeslotController::class);

use App\Http\Controllers\EventScheduleController;

Route::resource('schedules', EventScheduleController::class);