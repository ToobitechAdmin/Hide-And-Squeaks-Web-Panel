<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Video;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Handler\AbstractHandler;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Illuminate\Http\UploadedFile;
use File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Pion\Laravel\ChunkUpload\Exceptions\UploadFailedException;
use App\Http\Controllers\API\BaseController as BaseController;
use Validator;
use Str;
use Auth;
use Symfony\Component\HttpFoundation\Response; // Make sure to import Response
use Pawlox\VideoThumbnail\VideoThumbnail;
use FFMpeg;
use FFMpeg\Coordinate\TimeCode;
class VideoController extends BaseController
{
    public function index()
    {
        try {
            $videos = Video::with(['likes', 'comments'])->get();

            $data = $videos->map(function ($video) {
                return [
                    'id' => $video->id,
                    'title' => $video->title,
                    'file_path' => $video->file_path,
                    'thumbnail_path' => $video->thumbnail_path,
                    'video_type' => $video->video_type,
                    'total_comments' => $video->comments->count(),
                    'total_likes' => $video->likes->count(),
                    'created_at' => $video->created_at,
                ];
            });

            return response()->json(['data' => $data,'status' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => "Something went wrong ",'status' => "false"], 500);
        }
    }
  /**
     * Handles the file upload for multiple videos
     *
     * @param Request $request
     * @return JsonResponse
     * @throws UploadMissingFileException
     * @throws UploadFailedException
     */
    public function storeMultiple(Request $request)
    {
        try {
           //  Auth::user();
              $user_id = auth()->user()->id;
            // Check if the files are present in the request
            if (!$request->hasFile('files')) {
                throw new UploadMissingFileException('No files uploaded.');
            }

            $files = $request->file('files');

            $uploadedVideos = [];

            foreach ($files as $file) {
                // Check if the upload is successful
                if (!$file->isValid()) {
                    throw new UploadFailedException('File upload failed.');
                }

                // Save the file to storage and database
                $response = $this->saveFile($file, $request->input('title'), $request->input('description'),  $user_id);

                // Check the type of response and handle accordingly
                if ($response instanceof JsonResponse) {
                    // If it's a JsonResponse, extract the data
                    $responseData = $response->getData(true);
                    $uploadedVideos[] = $responseData['video'];
                } else {
                    // Assume it's an array or an object
                    $uploadedVideos[] = $response['video'];
                }
            }

            return response()->json([
                'message' => 'Videos uploaded successfully',
                'videos' => $uploadedVideos,
            ]);
        } catch (UploadMissingFileException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (UploadFailedException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected exception: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }


    /**
     * Handles the file upload
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws UploadMissingFileException
     * @throws UploadFailedException
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'title' => 'required',
            'description' => 'required',
            'video_type' => 'required',

        ]);
        if($validator->fails()){
            return $this->sendError($validator->errors()->first());

        }
        try {
            $thumbnail_file = null;
            // Check if the file is present in the request
            if (!$request->hasFile('file_path')) {
                throw new UploadMissingFileException('No file uploaded.');
            }
            // if (!$request->hasFile('thumbnail')) {
            //     return $this->sendError("The Thumbnail field is required.");
            // }
            // if($request->hasFile('thumbnail'))
            // {
            //     $img = Str::random(20).$request->file('thumbnail')->getClientOriginalName();
            //     $thumbnail_file = 'documents/thumbnail/'.$img;
            //     $request->thumbnail->move(public_path("documents/thumbnail"), $img);
            // }
             $user_id = auth()->id();

            // Create the file receiver
            $receiver = new FileReceiver("file_path", $request, HandlerFactory::classFromRequest($request));

            // Check if the upload is successful
            if (!$receiver->isUploaded()) {
                throw new UploadFailedException('File upload failed.');
            }

            // Receive the file
            $save = $receiver->receive();

            // Check if the upload has finished
            if ($save->isFinished()) {
                // Save the file to storage and database
                $response = $this->saveFile($save->getFile(), $request->input('title'), $request->input('description'),$request->input('video_type'),$user_id);

                return $response;
            }

            // We are in chunk mode, send the current progress
            $handler = $save->handler();

            return response()->json([
                "done" => $handler->getPercentageDone(),
                'status' => true
            ]);
        } catch (UploadMissingFileException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (UploadFailedException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            // Log other unexpected exceptions
            Log::error('Unexpected exception: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }

    /**
     * Saves the file to storage and database
     *
     * @param UploadedFile $file
     * @param string $title
     * @param int $user_id
     * @param string $description
     * @return JsonResponse
     */
    protected function saveFile(UploadedFile $file, $title, $description,$video_type,$user_id)
    {
        try {
            // Validate file size, mime type, or any other relevant validation

            $fileName = $this->createFilename($file);
            // Build the file path
            $filePath = "upload/";
            $finalPath = storage_path("app/public/" . $filePath);

            // Move the file
            $file->move($finalPath, $fileName);
            $videoPath = $filePath . $fileName;
            // $thumbnailPath = 'storage/'.$this->generateThumbnail($videoPath);
            $thumbnailPath = 'default.png';

            // Save video information to the database
            $video = Video::create([
                'title' => $title??null,
                'description' => $description??null,
                'file_path' => 'storage/'.$filePath . $fileName??null,
                'user_id'=>$user_id??null,
                "video_type"=>$video_type,
                "thumbnail_path"=>$thumbnailPath??null
            ]);

            $response = [
                'message' => 'Video uploaded successfully',
                'data' => [
                    'video_path' => $video->file_path??null,
                    'title' => $video->title??null,
                    'description' => $video->description??null,
                    "video_type"=>$video_type??null,
                    "thumbnail_path"=>$video->thumbnail_path??null,
                ],
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            // Log other unexpected exceptions
            Log::error('Unexpected exception: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }

    private function generateThumbnail($videoPath)
    {

        $thumbnailPath = 'thumbnails/' . pathinfo($videoPath, PATHINFO_FILENAME) . '.jpg';
        FFMpeg::fromDisk('public')
            ->open($videoPath)
            ->getFrameFromSeconds(1) // Adjust the time (in seconds) to capture the frame
            ->export()
            ->toDisk('public')
            ->save($thumbnailPath);

        return $thumbnailPath;
    }
    /**
     * Create unique filename for uploaded file
     *
     * @param UploadedFile $file
     * @return string
     */
    protected function createFilename(UploadedFile $file)
    {
        $extension = $file->getClientOriginalExtension();
        $filename = str_replace("." . $extension, "", $file->getClientOriginalName()); // Filename without extension

        // Add timestamp hash to the name of the file
        $filename .= "_" . md5(time()) . "." . $extension;

        return $filename;
    }

    public function destory(Request $request){
        $validator = Validator::make($request->all(), [

            'video_id' => 'required',

        ]);
        if($validator->fails()){
            return $this->sendError($validator->errors()->first());

        }
        try {
            $id = auth()->id();
            $data = Video::where(['id'=>$request->video_id,'user_id'=>$id])->first();
            if(!isset($data)){
                return $this->sendError("Video Not Found");
            }

            $data->delete();
            return $this->sendResponse([], 'Video Delete Successfully');
        } catch (\Exception $e) {

            return response()->json(['error' => 'Something went wrong'], 500);
        }
        return $request->all();
    }
}
