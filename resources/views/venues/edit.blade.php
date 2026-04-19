<!DOCTYPE html>
<html>
<head>
    <title>Edit Venue</title>
</head>
<body>
    <h1>Edit Venue</h1>

    <a href="{{ route('venues.index') }}">Back to Venue List</a>

    @if ($errors->any())
        <div style="color: red; margin-top: 10px;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('venues.update', $venue) }}" method="POST" style="margin-top: 20px;">
        @csrf
        @method('PUT')

        <div>
            <label>Name:</label><br>
            <input type="text" name="name" value="{{ old('name', $venue->name) }}">
        </div>

        <br>

        <div>
            <label>Location:</label><br>
            <input type="text" name="location" value="{{ old('location', $venue->location) }}">
        </div>

        <br>

        <div>
            <label>Capacity:</label><br>
            <input type="number" name="capacity" value="{{ old('capacity', $venue->capacity) }}">
        </div>

        <br>

        <div>
            <label>Description:</label><br>
            <textarea name="description">{{ old('description', $venue->description) }}</textarea>
        </div>

        <br>

        <button type="submit">Update Venue</button>
    </form>
</body>
</html>