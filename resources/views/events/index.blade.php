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
                    <th>Description</th>
                    <th>Capacity</th>
                    <th>Duration (minutes)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($events as $event)
                    <tr>
                        <td>{{ $event->title }}</td>
                        <td>{{ $event->description }}</td>
                        <td>{{ $event->capacity }}</td>
                        <td>{{ $event->duration_minutes }}</td>
                        <td>
                            <a href="{{ route('events.edit', $event) }}">Edit</a>
            
                            <form action="{{ route('events.destroy', $event) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Delete this event?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
