<!DOCTYPE html>
<html>
<head>
    <title>Timeslots</title>
</head>
<body>
    <h1>Timeslot List</h1>

    @if (session('success'))
        <p style="color: green;">{{ session('success') }}</p>
    @endif

    <a href="{{ route('timeslots.create') }}">Create New Timeslot</a>

    @if ($timeslots->isEmpty())
        <p>No timeslots yet.</p>
    @else
        <table border="1" cellpadding="10" cellspacing="0" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($timeslots as $timeslot)
                    <tr>
                        <td>{{ $timeslot->slot_date }}</td>
                        <td>{{ $timeslot->start_time }}</td>
                        <td>{{ $timeslot->end_time }}</td>
                        <td>
                            <a href="{{ route('timeslots.edit', $timeslot) }}">Edit</a>

                            <form action="{{ route('timeslots.destroy', $timeslot) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Delete this timeslot?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>