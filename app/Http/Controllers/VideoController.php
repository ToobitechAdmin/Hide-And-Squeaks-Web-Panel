<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage;

use Illuminate\Http\Request;
use App\Models\Video;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Exceptions\UploadFailedException;
class VideoController extends Controller
{
    public function index()
    {
        $videos = Video::all();
        return view('pages.apps.videos.index', compact('videos'));
    }

    // public function create()
    // {
    //     return view('videos.create');
    // }
   /**
     * Handles the file upload
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws UploadMissingFileException
     * @throws UploadFailedException
     */
    public function store(Request $request)
    {
        // create the file receiver
        $receiver = new FileReceiver("file", $request, HandlerFactory::classFromRequest($request));

        // check if the upload is successful, throw exception or return the response you need
        if ($receiver->isUploaded() === false) {
            throw new UploadMissingFileException();
        }

        // receive the file
        $save = $receiver->receive();

        // check if the upload has finished (in chunk mode it will send smaller files)
        if ($save->isFinished()) {
            // save the file to storage and database
            $response = $this->saveFile($save->getFile(), $request->input('title'));

            return $response;
        }

        // we are in chunk mode, let's send the current progress
        $handler = $save->handler();

        return response()->json([
            "done" => $handler->getPercentageDone(),
            'status' => true
        ]);
    }

    /**
     * Saves the file to storage and database
     *
     * @param UploadedFile $file
     * @param string $title
     *
     * @return JsonResponse
     */
    protected function saveFile(UploadedFile $file, $title)
    {
        $fileName = $this->createFilename($file);

        // Group files by mime type
        $mime = str_replace('/', '-', $file->getMimeType());

        // Group files by the date (week)
        $dateFolder = date("Y-m-W");

        // Build the file path
        $filePath = "upload/";
        $finalPath = storage_path("app/public/" . $filePath);

        // move the file name
        $file->move($finalPath, $fileName);

        // Save video information to the database
        $video = Video::create([
            'title' => $title,
            'file_path' => $filePath . $fileName,
        ]);

        $response = [
            'path' => asset('storage/' . $filePath),
            'name' => $fileName,
            'mime_type' => $mime,
            'title' => $video->title, // Return the title in the response
        ];

        return response()->json($response);
    }
    /**
     * Create unique filename for uploaded file
     * @param UploadedFile $file
     * @return string
     */
    protected function createFilename(UploadedFile $file)
    {
        $extension = $file->getClientOriginalExtension();
        $filename = str_replace("." . $extension, "", $file->getClientOriginalName()); // Filename without extension

        // Add timestamp hash to name of the file
        $filename .= "_" . md5(time()) . "." . $extension;

        return $filename;
    }
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'title' => 'required',
    //         'video_file' => 'required|mimes:mp4,avi,mov', // Add more allowed video file types if needed
    //     ]);

    //     $videoFile = $request->file('video_file');
    //     $file_path = $videoFile->store('video_files', 'public');

    //     Video::create([
    //         'title' => $request->input('title'),
    //         'file_path' => $file_path,
    //     ]);

    //     return redirect()->route('videos.index')->with('success', 'Video uploaded successfully');
    // }

    public function destroy($id)
    {
        try {
            $video = Video::findOrFail($id);

            // Log the file path for debugging
            \Log::info('Deleting video file: ' . $video->file_path);

            // Delete the video file from storage
            Storage::disk('public')->delete($video->file_path);

            // Log a message after successful deletion
            \Log::info('Video file deleted successfully');

            // Delete the video record from the database
            $video->delete();

            return redirect()->route('videos.index')->with('success', 'Video deleted successfully');
        } catch (\Exception $e) {
            // Log the error
            \Log::error('Error deleting video file: ' . $e->getMessage());

            // You can customize the error handling, such as displaying a message to the user
            return redirect()->route('videos.index')->with('error', 'Error deleting video file');
        }
    }
    public function edit($id)
{
    $videos = Video::findOrFail($id);
    return view('pages.apps.videos.edit', compact('videos'));
}
public function update(Request $request, $id)
{
    // $request->validate([
    //     'title' => 'required',
    //     'video_file' => 'required|mimes:mp4,avi,mov', // Allow video file to be optional during update
    // ]);

    $videos = Video::findOrFail($id);

    $videos->title = $request->input('title');

    if ($request->hasFile('video_file')) {
        // Delete the existing video file from storage
        Storage::disk('public')->delete($videos->file_path);

        // Upload the new video file
        $videoFile = $request->file('video_file');
        $videos->file_path = $videoFile->store('video_files', 'public');
    }

    $videos->save();

    return redirect()->route('videos.index')->with('success', 'Video updated successfully');
}
}
