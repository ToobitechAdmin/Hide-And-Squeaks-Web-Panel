<!DOCTYPE html>
<html>
<head>
    <title>Upload Audio</title>
</head>
<body>
    <h2>Upload Audio</h2>

    @if ($errors->any())
        <div>
            <strong>Validation errors:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('audios.store') }}" method="post" enctype="multipart/form-data">
        @csrf
        <label for="title">Title:</label>
        <input type="text" name="title" required>
        <br>
        <label for="audio_file">Audio File:</label>
        <input type="file" name="audio_file" accept="audio/*" required>
        <br>
        <button type="submit">Upload</button>
    </form>

    <br>
    <a href="{{ route('audios.index') }}">Back to Audio Files</a>
</body>
</html>
