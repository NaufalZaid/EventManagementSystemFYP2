<!DOCTYPE html>
<html>
<head>
    <title>Create Schedule</title>
</head>
<body>
    <h1>Create Schedule</h1>

    <a href="{{ route('schedules.index') }}">Back to Schedule List</a>

    @if ($errors->any())
        <div style="color: red; margin-top: 10px;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('schedules.store') }}" method="POST" style="margin-top: 20px;">
        @csrf

        <div>
            <label>Event:</label><br>
            <select name="event_id">
                <option value="">Select Event</option>
                @foreach ($events as $event)
                    <option value="{{ $event->id }}" {{ old('event_id') == $event->id ? 'selected' : '' }}>
                        {{ $event->title }}
                    </option>
                @endforeach
            </select>
        </div>

        <br>

        <div>
            <label>Venue:</label><br>
            <select name="venue_id">
                <option value="">Select Venue</option>
                @foreach ($venues as $venue)
                    <option value="{{ $venue->id }}" {{ old('venue_id') == $venue->id ? 'selected' : '' }}>
                        {{ $venue->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <br>

        <div>
            <label>Timeslot:</label><br>
            <select name="timeslot_id">
                <option value="">Select Timeslot</option>
                @foreach ($timeslots as $timeslot)
                    <option value="{{ $timeslot->id }}" {{ old('timeslot_id') == $timeslot->id ? 'selected' : '' }}>
                        {{ $timeslot->slot_date }} | {{ $timeslot->start_time }} - {{ $timeslot->end_time }}
                    </option>
                @endforeach
            </select>
        </div>

        <br>

        <div>
            <label>Status:</label><br>
            <select name="status">
                <option value="manual" {{ old('status') == 'manual' ? 'selected' : '' }}>manual</option>
                <option value="generated" {{ old('status') == 'generated' ? 'selected' : '' }}>generated</option>
            </select>
        </div>

        <br>

        <button type="submit">Save Schedule</button>
    </form>
</body>
</html>