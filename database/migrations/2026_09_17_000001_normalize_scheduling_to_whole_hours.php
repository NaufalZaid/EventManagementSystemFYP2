<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('timeslots')->orderBy('id')->get()->each(function ($timeslot): void {
            $start = date_parse($timeslot->start_time);
            $end = date_parse($timeslot->end_time);
            $startHour = max(8, min(22, (int) $start['hour']));
            $endHour = (int) $end['hour'] + (((int) $end['minute'] > 0 || (int) $end['second'] > 0) ? 1 : 0);
            $endHour = max(9, min(23, $endHour));

            if ($endHour <= $startHour) {
                $endHour = min(23, $startHour + 1);
            }

            DB::table('timeslots')->where('id', $timeslot->id)->update([
                'start_time' => sprintf('%02d:00:00', $startHour),
                'end_time' => sprintf('%02d:00:00', $endHour),
            ]);
        });

        DB::table('events')->orderBy('id')->get()->each(function ($event): void {
            $maximumMinutes = $event->is_outside_working_hours ? 900 : 600;
            $duration = max(60, min($maximumMinutes, (int) ceil(((int) $event->duration_minutes) / 60) * 60));
            $updates = ['duration_minutes' => $duration];

            if ($event->preferred_start_time) {
                $preferred = date_parse($event->preferred_start_time);
                $latestStart = $event->is_outside_working_hours ? 22 : 17;
                $preferredHour = max(8, min($latestStart, (int) $preferred['hour']));
                $updates['preferred_start_time'] = sprintf('%02d:00:00', $preferredHour);
            }

            DB::table('events')->where('id', $event->id)->update($updates);
        });
    }

    public function down(): void
    {
        // Minute precision cannot be reconstructed after normalization.
    }
};
