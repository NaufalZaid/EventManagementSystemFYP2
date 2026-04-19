<!DOCTYPE html>
<html>
<head>
    <title>Create Venue</title>
</head>
<body>
    <h1>Create Venue</h1>

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

    <form action="{{ route('venues.store') }}" method="POST" style="margin-top: 20px;">
        @csrf

        <div>
            <label>Name:</label><br>
            <input type="text" name="name" value="{{ old('name') }}">
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

        <div>
            <label>Description:</label><br>
            <textarea name="description">{{ old('description') }}</textarea>
        </div>

        <br>

        <button type="submit">Save Venue</button>
    </form>
</body>
</html>