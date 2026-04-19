<!DOCTYPE html>
<html>
<head>
    <title>Events</title>
</head>
<body>
    <h1>Event List</h1>

    @if (session('success'))
        <p style="color: green;">{{ session('success') }}</p>
    @endif

    <a href="{{ route('events.create') }}">Create New Event</a>

    @if ($events->isEmpty())
        <p>No events yet.</p>
    @else
        <table border="1" cellpadding="10" cellspacing="0" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Location</th>
                    <th>Capacity</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($events as $event)
                    <tr>
                        <td>{{ $event->title }}</td>
                        <td>{{ $event->event_date }}</td>
                        <td>{{ $event->start_time }} - {{ $event->end_time }}</td>
                        <td>{{ $event->location }}</td>
                        <td>{{ $event->capacity }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
