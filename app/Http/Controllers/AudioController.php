<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage; // Add this line

use Illuminate\Http\Request;
use App\Models\Audio;

class AudioController extends Controller
{
    public function index()
    {
        try {
            # code...
            $audios = Audio::all();
            return view('pages.apps.audios.index', compact('audios'));
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }

    public function create()
    {
        return view('pages.apps.audios.index');
    }

    public function store(Request $request)
    {

        try {
            # code...
            $request->validate([
                'title' => 'required',
                'audio_file' => 'required|mimes:mp3,wav',

            ]);

            $audioFile = $request->file('audio_file');
            $file_path = $audioFile->store('audio_files', 'public');

            Audio::create([
                'title' => $request->input('title'),
                'file_path' => $file_path,
                'type'=>$request->input('type'),
                'price' =>$request->input('price'),
            ]);

            return redirect()->route('audios.index')->with('success', 'Audio uploaded successfully');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }
    public function destroy($id)
    {
        $audio = Audio::findOrFail($id);

        // Delete the audio file from storage
        Storage::disk('public')->delete($audio->file_path);

        // Delete the audio record from the database
        $audio->delete();

        return redirect()->route('audios.index')->with('success', 'Audio deleted successfully');
    }
    public function edit($id)
    {
        try {
            # code...
            $audio = Audio::findOrFail($id);
            return view('pages.apps.audios.edit', compact('audio'));
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }
    public function update(Request $request, $id)
    {
        try {
            # code...
            $audio = Audio::findOrFail($id);

            $audio->title = $request->input('title');
            $audio->type = $request->input('type');
            $audio->price = $request->input('price');

            if ($request->hasFile('audio_file')) {
                // Delete the existing audio file from storage
                Storage::disk('public')->delete($audio->file_path);

                // Upload the new audio file
                $audioFile = $request->file('audio_file');
                $audio->file_path = $audioFile->store('audio_files', 'public');
            }

            $audio->save();

            return redirect()->route('audios.index')->with('success', 'Audio updated successfully');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Something went wrong');
        }

    }
}
