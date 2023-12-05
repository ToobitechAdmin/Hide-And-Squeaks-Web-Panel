<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Files</title>
    <style>
        .video-container {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: black;
            z-index: 1000;
        }

        video {
            width: 100%;
            height: 100%;
        }
    </style>
</head>
<body>
    <h2>Video Files</h2>

    <ul>
        @foreach($videos as $video)
            <li>
                {{ $video->title }}

                <!-- Show video as a link -->
                <a href="#" class="video-link" data-src="{{ asset('storage/' . $video->file_path) }}">Watch Video</a>

                <!-- Download link -->
                <a href="{{ asset('storage/' . $video->file_path) }}" download>Download</a>

                <!-- Delete form -->
                <form action="{{ route('videos.destroy', $video->id) }}" method="post" style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit">Delete</button>
                </form>
            </li>
        @endforeach
    </ul>

    <a href="{{ route('videos.create') }}">Upload Video</a>

    <!-- JavaScript to play video in a popup -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var videoLinks = document.querySelectorAll('.video-link');

            videoLinks.forEach(function (link) {
                link.addEventListener('click', function (event) {
                    event.preventDefault();

                    var videoContainer = document.querySelector('.video-container');
                    var video = videoContainer.querySelector('video');
                    var videoSource = video.querySelector('source');

                    var videoSrc = link.getAttribute('data-src');

                    if (videoContainer.style.display === 'block') {
                        // Video container is visible, hide it
                        videoContainer.style.display = 'none';
                        video.pause();
                    } else {
                        // Video container is hidden, show it and play the video
                        videoSource.src = videoSrc;
                        video.load();
                        video.play();
                        videoContainer.style.display = 'block';
                    }
                });
            });

            // Fullscreen functionality
            var videoContainer = document.querySelector('.video-container');
            videoContainer.addEventListener('click', function () {
                if (videoContainer.requestFullscreen) {
                    videoContainer.requestFullscreen();
                } else if (videoContainer.mozRequestFullScreen) {
                    videoContainer.mozRequestFullScreen();
                } else if (videoContainer.webkitRequestFullscreen) {
                    videoContainer.webkitRequestFullscreen();
                } else if (videoContainer.msRequestFullscreen) {
                    videoContainer.msRequestFullscreen();
                }
            });
        });
    </script>
    
    <!-- Video container -->
    <div class="video-container">
        <video controls>
            <source src="" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>
</body>
</html>
