<h1>Edit Event</h1>

@if ($errors->any())
    <div style="color:red;">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('events.update', $event) }}" method="POST">
    @csrf
    @method('PUT')

    <div>
        <label>Title:</label><br>
        <input type="text" name="title" value="{{ old('title', $event->title) }}">
    </div>

    <br>

    <div>
        <label>Description:</label><br>
        <textarea name="description">{{ old('description', $event->description) }}</textarea>
    </div>

    <br>

    <div>
        <label>Capacity:</label><br>
        <input type="number" name="capacity" value="{{ old('capacity', $event->capacity) }}">
    </div>

    <br>

    <div>
        <label>Duration (minutes):</label><br>
        <input type="number" name="duration_minutes" value="{{ old('duration_minutes', $event->duration_minutes) }}">
    </div>

    <br>

    <button type="submit">Update Event</button>
</form>