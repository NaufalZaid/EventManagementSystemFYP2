<!DOCTYPE html>
<html>
<head>
    <title>Create Event</title>
</head>
<body>
    <h1>Create Event</h1>

    <a href="{{ route('events.index') }}">Back to Event List</a>

    @if ($errors->any())
        <div style="color: red; margin-top: 10px;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('events.store') }}" method="POST" style="margin-top: 20px;">
        @csrf

        <div>
            <label>Title:</label><br>
            <input type="text" name="title" value="{{ old('title') }}">
        </div>

        <br>

        <div>
            <label>Description:</label><br>
            <textarea name="description">{{ old('description') }}</textarea>
        </div>

        <br>

        <div>
            <label>Event Date:</label><br>
            <input type="date" name="event_date" value="{{ old('event_date') }}">
        </div>

        <br>

        <div>
            <label>Start Time:</label><br>
            <input type="time" name="start_time" value="{{ old('start_time') }}">
        </div>

        <br>

        <div>
            <label>End Time:</label><br>
            <input type="time" name="end_time" value="{{ old('end_time') }}">
        </div>

        <br>

        <div>
            <label>Location:</label><br>
            <input type="text" name="location" value="{{ old('location') }}">
        </div>

        <br>

        <div>
            <label>Capacity:</label><br>
            <input type="number" name="capacity" value="{{ old('capacity', 0) }}">
        </div>

        <br>

        <button type="submit">Save Event</button>
    </form>
</body>
</html>
