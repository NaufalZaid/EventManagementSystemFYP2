<!DOCTYPE html>
<html>
<head>
    <title>Create Timeslot</title>
</head>
<body>
    <h1>Create Timeslot</h1>

    <a href="{{ route('timeslots.index') }}">Back to Timeslot List</a>

    @if ($errors->any())
        <div style="color: red; margin-top: 10px;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('timeslots.store') }}" method="POST" style="margin-top: 20px;">
        @csrf

        <div>
            <label>Date:</label><br>
            <input type="date" name="slot_date" value="{{ old('slot_date') }}">
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

        <button type="submit">Save Timeslot</button>
    </form>
</body>
</html>