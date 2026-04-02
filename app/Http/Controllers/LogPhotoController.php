<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\LogPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LogPhotoController extends Controller
{
    public function index(Request $request, int $logId): JsonResponse
    {
        $log = Log::where('user_id', $request->user()->id)->find($logId);

        if (!$log) {
            return response()->json(['message' => 'Log not found'], 404);
        }

        $photos = $log->photos()->get()->map(function ($photo) {
            return [
                'id' => $photo->id,
                'log_id' => $photo->log_id,
                'caption' => $photo->caption,
                'url' => $photo->url,
                'created_at' => $photo->created_at->toIso8601String(),
            ];
        });

        return response()->json(['data' => $photos]);
    }

    public function store(Request $request, int $logId): JsonResponse
    {
        $request->validate([
            'photos' => 'required|array|max:10',
            'photos.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'caption' => 'nullable|string|max:255',
        ]);

        $log = Log::where('user_id', $request->user()->id)->find($logId);

        if (!$log) {
            return response()->json(['message' => 'Log not found'], 404);
        }

        $uploadedPhotos = [];

        foreach ($request->file('photos') as $index => $photoFile) {
            $filename = Str::uuid() . '.' . $photoFile->getClientOriginalExtension();
            $path = 'logs/' . $logId . '/photos/' . $filename;

            Storage::disk('local')->putFileAs(
                'private/logs/' . $logId . '/photos',
                $photoFile,
                $filename
            );

            $caption = $request->input('caption') ?? $request->input("captions.{$index}");

            $photo = $log->photos()->create([
                'file_path' => $path,
                'caption' => $caption,
            ]);

            $uploadedPhotos[] = [
                'id' => $photo->id,
                'log_id' => $photo->log_id,
                'caption' => $photo->caption,
                'url' => $photo->url,
                'created_at' => $photo->created_at->toIso8601String(),
            ];
        }

        return response()->json([
            'data' => $uploadedPhotos,
            'message' => 'Photos uploaded successfully',
        ], 201);
    }

    public function showPhoto(Request $request, int $logId, int $photoId): JsonResponse
    {
        $log = Log::where('user_id', $request->user()->id)->find($logId);

        if (!$log) {
            return response()->json(['message' => 'Log not found'], 404);
        }

        $photo = $log->photos()->find($photoId);

        if (!$photo) {
            return response()->json(['message' => 'Photo not found'], 404);
        }

        $fullPath = storage_path('app/' . $photo->file_path);

        if (!file_exists($fullPath)) {
            return response()->json(['message' => 'Photo file not found'], 404);
        }

        $mimeType = mime_content_type($fullPath);

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function destroy(Request $request, int $logId, int $photoId): JsonResponse
    {
        $log = Log::where('user_id', $request->user()->id)->find($logId);

        if (!$log) {
            return response()->json(['message' => 'Log not found'], 404);
        }

        $photo = $log->photos()->find($photoId);

        if (!$photo) {
            return response()->json(['message' => 'Photo not found'], 404);
        }

        Storage::disk('local')->delete($photo->file_path);
        $photo->delete();

        return response()->json(['message' => 'Photo deleted successfully']);
    }
}
