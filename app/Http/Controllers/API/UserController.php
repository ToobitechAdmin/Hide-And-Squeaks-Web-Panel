<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Http\JsonResponse;
use Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use App\Mail\sendEmail;
use App\Mail\ResetPasswordEmail;

use Log;
class UserController extends BaseController
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required',
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors()->first());

        }
        try {
            if($request->hasFile('profile'))
            {
                $img = Str::random(20).$request->file('profile')->getClientOriginalName();
                $input['profile'] = $img;
                $request->profile->move(public_path("documents/profile"), $img);
            }else{
                $input['profile'] = 'documents/profile/default.png';
            }
            $input = $request->except(['password','name','last_name'],$request->all());
            $name = $request->name;
            $last_name = $request->last_name;
            $input['name'] =   $name .' '.$last_name;
            $input['password'] = Hash::make($request->password);
            $user = User::create($input);
            $success['token'] =  $user->createToken('Hide-and-squeaks')->accessToken;
            $success['name'] =  Str::upper($user->name);
            $success['id'] = $user->id;
            return $this->sendResponse($success, 'User register successfully.');
            # code...
        } catch (\Throwable $e) {
            return $this->sendError('SomeThing went wrong.');
        }

    }
    public function login(Request $request)
    {
        try {
            if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){
                $user = Auth::user();
                $success['token'] =  $user->createToken('Hide-and-squeaks')->accessToken;

                $success['name'] =  Str::upper($user->name) ;
                $success['id'] =  $user->id ;


                return $this->sendResponse($success, 'User login successfully.');
            }
            else{
                return $this->sendError('Unauthorized.');
            }
            //code...
        } catch (\Throwable $th) {
            return $this->sendError('SomeThing went wrong.');
        }
    }
    public function requestOtp(Request $request)
    {
        $otp = rand(1000, 9999);
        $resetToken = Str::random(64); // Generate a random token for password reset

        Log::info("otp = " . $otp);

        $user = User::where('email', $request->email)->update([
            'otp' => $otp,
            'reset_token' => $resetToken,
        ]);

        if ($user) {
            Mail::to($request->email)->send(new ResetPasswordEmail($otp));

            $success['otp'] =  $otp;
            $success['reset_token'] = $resetToken;

            return $this->sendResponse($success, 'OTP Sent Successfully');
        } else {
            return $this->sendError('Otp not sent.');
        }
    }

    public function verifyOtp(Request $request)
    {
        $otp_match = User::where([
            'email' => $request->email,
            'otp' => $request->otp,
        ])->first();

        if ($otp_match) {
            return $this->sendResponse('OTP matched. Proceed to reset password.');
        } else {
            return $this->sendError('Invalid OTP');
        }
    }
    public function resetPassword(Request $request)
    {
        $user = User::where([
            'email' => $request->email,
            'reset_token' => $request->reset_token,
        ])->first();

        if ($user && $user->otp == $request->otp) {
            // Reset the password
            $user->password = bcrypt($request->new_password);
            $user->otp = null; // Clear the OTP
            $user->reset_token = null; // Clear the reset token
            $user->save();

            return $this->sendResponse('Password reset successfully');
        } else {
            return $this->sendError('Invalid OTP or reset token');
        }
    }
   }
