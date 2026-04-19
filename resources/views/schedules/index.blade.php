<!DOCTYPE html>
<html>
<head>
    <title>Schedules</title>
</head>
<body>
    <h1>Schedule List</h1>

    @if (session('success'))
        <p style="color: green;">{{ session('success') }}</p>
    @endif

    <a href="{{ route('schedules.create') }}">Create New Schedule</a>

    @if ($schedules->isEmpty())
        <p>No schedules yet.</p>
    @else
        <table border="1" cellpadding="10" cellspacing="0" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Venue</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($schedules as $schedule)
                    <tr>
                        <td>{{ $schedule->event->title }}</td>
                        <td>{{ $schedule->venue->name }}</td>
                        <td>{{ $schedule->timeslot->slot_date }}</td>
                        <td>{{ $schedule->timeslot->start_time }} - {{ $schedule->timeslot->end_time }}</td>
                        <td>{{ $schedule->status }}</td>
                        <td>
                            <a href="{{ route('schedules.edit', $schedule) }}">Edit</a>

                            <form action="{{ route('schedules.destroy', $schedule) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Delete this schedule?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>