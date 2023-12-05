<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage; // Add this line

use Illuminate\Http\Request;
use App\Models\Audio;

class AudioController extends Controller
{
    public function index()
    {
        $audios = Audio::all();
        return view('audios.index', compact('audios'));
    }

    public function create()
    {
        return view('audios.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'audio_file' => 'required|mimes:mp3,wav', // Add more allowed audio file types if needed
        ]);

        $audioFile = $request->file('audio_file');
        $file_path = $audioFile->store('audio_files', 'public');

        Audio::create([
            'title' => $request->input('title'),
            'file_path' => $file_path,
        ]);

        return redirect()->route('audios.index')->with('success', 'Audio uploaded successfully');
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
    $audio = Audio::findOrFail($id);
    return view('audios.edit', compact('audio'));
}
public function update(Request $request, $id)
{
    // $request->validate([
    //     'title' => 'required',
    //     'audio_file' => 'nullable|mimes:mp3,wav', // Allow audio file to be optional during update
    // ]);

    $audio = Audio::findOrFail($id);

    $audio->title = $request->input('title');

    if ($request->hasFile('audio_file')) {
        // Delete the existing audio file from storage
        Storage::disk('public')->delete($audio->file_path);

        // Upload the new audio file
        $audioFile = $request->file('audio_file');
        $audio->file_path = $audioFile->store('audio_files', 'public');
    }

    $audio->save();

    return redirect()->route('audios.index')->with('success', 'Audio updated successfully');
}
}
