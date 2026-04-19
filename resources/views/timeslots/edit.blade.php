<!DOCTYPE html>
<html>
<head>
    <title>Edit Timeslot</title>
</head>
<body>
    <h1>Edit Timeslot</h1>

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

    <form action="{{ route('timeslots.update', $timeslot) }}" method="POST" style="margin-top: 20px;">
        @csrf
        @method('PUT')

        <div>
            <label>Date:</label><br>
            <input type="date" name="slot_date" value="{{ old('slot_date', $timeslot->slot_date) }}">
        </div>

        <br>

        <div>
            <label>Start Time:</label><br>
            <input type="time" name="start_time" value="{{ old('start_time', $timeslot->start_time) }}">
        </div>

        <br>

        <div>
            <label>End Time:</label><br>
            <input type="time" name="end_time" value="{{ old('end_time', $timeslot->end_time) }}">
        </div>

        <br>

        <button type="submit">Update Timeslot</button>
    </form>
</body>
</html>