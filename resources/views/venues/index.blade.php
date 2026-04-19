<!DOCTYPE html>
<html>
<head>
    <title>Venues</title>
</head>
<body>
    <h1>Venue List</h1>

    @if (session('success'))
        <p style="color: green;">{{ session('success') }}</p>
    @endif

    <a href="{{ route('venues.create') }}">Create New Venue</a>

    @if ($venues->isEmpty())
        <p>No venues yet.</p>
    @else
        <table border="1" cellpadding="10" cellspacing="0" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Location</th>
                    <th>Capacity</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($venues as $venue)
                    <tr>
                        <td>{{ $venue->name }}</td>
                        <td>{{ $venue->location }}</td>
                        <td>{{ $venue->capacity }}</td>
                        <td>{{ $venue->description }}</td>
                        <td>
                            <a href="{{ route('venues.edit', $venue) }}">Edit</a>

                            <form action="{{ route('venues.destroy', $venue) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Delete this venue?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>