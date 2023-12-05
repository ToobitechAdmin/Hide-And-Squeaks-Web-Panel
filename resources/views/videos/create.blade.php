<!DOCTYPE html>
<html>
<head>
    <title>Upload Video</title>
</head>
<body>
    <h2>Upload Video</h2>

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

    <form action="{{ route('videos.store') }}" method="post" enctype="multipart/form-data">
        @csrf
        <label for="title">Title:</label>
        <input type="text" name="title" required>
        <br>
        <label for="video_file">Video File:</label>
        <input type="file" name="video_file" accept="video/*" required>
        <br>
        <button type="submit">Upload</button>
    </form>

    <br>
    <a href="{{ route('videos.index') }}">Back to Video Files</a>
</body>
</html>
