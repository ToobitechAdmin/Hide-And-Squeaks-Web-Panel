<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Audio;
use App\Models\Video;
use App\Models\Like;
use App\Models\Comment;
use App\Models\Pet;
use App\Models\View;
use App\Models\Ensurance;
use Validator;

use App\Http\Controllers\API\BaseController as BaseController;

class HomeController extends BaseController
{
    //Audio Controller functions

    public function getAudio()
{
      try {

          $audio = Audio::all();

         return $this->sendResponse($audio);
    }
    catch (\Throwable $th) {

        return $this->sendError('Something went wrong');
    }
}

//Audio controller functions end


//Profile Controller functions

public function profilePost(Request $request)
{
    try {
    //code...
    $validator = Validator::make($request->all(), [
        'name' => 'required|string',
        'breed' => 'required|string',
        'age' => 'required',
    ]);

    if($validator->fails()){
        return $this->sendError($validator->errors()->first());

    }
    $user_id = auth()->user()->id;

   // $user = auth()->user();

    $pet =Pet::create([
        'name' => $request->input('name'),
        'breed' => $request->input('breed'),
        'status' => $request->input('status'),
        'age' =>$request->input('age'),
        'user_id' =>  $user_id,
    ]);

    $response = [

            'name' => $pet->name,
            'breed' => $pet->breed,
            'age' =>$pet->age,
            'status' => $pet->status,
            'user_id'=>$pet->user_id,

    ];

    return $this->sendResponse($response,'Profile Added');
        }
        catch (\Throwable $th)
         {
    return $this->sendError('Something went wrong');
         }
}

public function getProfile()
{
    // $user = auth()->user();



    try {
        //code...
        $profile = Pet::all();
        return $this->sendResponse($profile);
    } catch (\Throwable $th) {
        return $this->sendError('Something went wrong');
    }
}
public function getLegal()
{
  try
  {
    //code...
    $para = Ensurance::all();
    return $this->sendResponse($para);
   }
   catch (\Throwable $th) {
    return $this->sendError('Something went wrong');
}
}

//Profile controller functions end


//like Controller functions

public function likePost(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'video_id' => 'required',
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors()->first());

        }

        $check_video = Video::find($request->video_id);
        if (!isset($check_video)) {
            return $this->sendError('Video Not Found');
        }
        $user_id = auth()->id();
        $check_like = Like::where('user_id',$user_id)->where('video_id',$request->video_id)->first();
        if (isset($check_like)) {
            $check_like->delete();
            return $this->sendResponse($response=[],'Dislike Video');
        }
        $like =Like::create([
            'user_id'=> $user_id,
            'video_id'=>$request->input('video_id'),
        ]);

        $response = [
            'video_id' => $like->video_id,
        ];


       return $this->sendResponse($response,'like Video');
        //code...
    } catch (\Throwable $th) {
        return $this->sendError('Something went wrong');
    }

}

public function totalNumberOfLikes()
{
    try {

        $like = Like::all();
        return $this->sendResponse($like);
    } catch (\Throwable $th) {
        return $this->sendError('Something went wrong');
    }

}

//like controller functions end


//Comment Controller functions

public function commentPost(Request $request)
{
    try {
        //code...
        $validator = Validator::make($request->all(), [
            'video_id' => 'required',
            'comment' =>'required',
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors()->first());

        }
       // $user = auth()->user();

       $check_video = Video::find($request->video_id);
       if (!isset($check_video)) {
           return $this->sendError('Video Not Found');
       }
       $user_id = auth()->id();

        $comment =Comment::create([
            'comment' => $request->input('comment'),
            'user_id'=> $user_id,
            'video_id'=>$request->input('video_id'),
        ]);

        $response = [

                'comment' => $comment->comment,
                'user_id' => $comment->user_id,
                'video_id' => $comment->video_id,

        ];

        return $this->sendResponse($response,'Comment Video');
    } catch (\Throwable $th) {
        return $this->sendError('Something went wrong');
    }
}

public function getComment()
{
    // $user = auth()->user();



    $comment = Comment::all();
    return response()->json($comment);
}

//Comment controller functions end


// view video detail with total_views, total_likes, total_comments


public function videoById(Request $request)
{
    try {
        //code...
        $validator = Validator::make($request->all(), [
            'video_id' => 'required|exists:videos,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $video = Video::withCount(['comments', 'likes', 'views'])
            ->findOrFail($request->input('video_id'));

        $response=[
            'video' => [
                'id' => $video->id,
                'title' => $video->title,
                'description' => $video->description,
                'file_path' => $video->file_path,
                'comments' => $video->comments->toArray(),
                'likes' => $video->likes->toArray(),
                'views' => $video->views->toArray(),
            ],
            'total_comments' => $video->comments_count,
            'total_likes' => $video->likes_count,
            'total_views' => $video->views_count,

        ];
        return $this->sendResponse($response,'View Video Detail');

    } catch (\Throwable $th) {
        return $this->sendError('Something went wrong');
    }
}

public function addView(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'video_id' => 'required',
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors()->first());

        }

        $check_video = Video::find($request->video_id);
        if (!isset($check_video)) {
            return $this->sendError('Video Not Found');
        }
        $user_id = auth()->id();

        $view =View::create([
            'user_id'=> $user_id,
            'video_id'=>$request->input('video_id'),
        ]);

        $response = [
            'video_id' => $view->video_id,
        ];


       return $this->sendResponse($response,'View Video');
        //code...
    } catch (\Throwable $th) {
        return $this->sendError('Something went wrong');
    }
}

// view video detail with total_views, total_likes, total_comments ---end

}