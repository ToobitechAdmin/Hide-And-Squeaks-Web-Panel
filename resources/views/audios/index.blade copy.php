<!DOCTYPE html>
<html>
<head>
    <title>Audio Files</title>
</head>
<body>
    <h2>Audio Files</h2>

    <ul>
        @foreach($audios as $audio)
            <li>
                {{ $audio->title }}
                <audio controls>
                    <source src="{{ asset('storage/' . $audio->file_path) }}" type="audio/mp3">
                    Your browser does not support the audio tag.
                </audio>
                <a href="{{ asset('storage/' . $audio->file_path) }}" download>Download</a>

                <form action="{{ route('audios.destroy', $audio->id) }}" method="post" style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit">Delete</button>
                </form>
            </li>
        @endforeach
    </ul>

    <a href="{{ route('audios.create') }}">Upload Audio</a>
</body>
</html>
