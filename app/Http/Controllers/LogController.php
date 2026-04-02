<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLogRequest;
use App\Http\Requests\UpdateLogRequest;
use App\Http\Resources\LogResource;
use App\Models\Log;
use App\Services\AIActionExtractor;
use App\Services\LogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LogController extends Controller
{
    public function __construct(
        private LogService $logService,
        private AIActionExtractor $extractor
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $logs = $request->user()
            ->logs()
            ->orderBy('recorded_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return LogResource::collection($logs);
    }

    public function store(StoreLogRequest $request): JsonResponse
    {
        $audioFile = $request->file('audio');
        $recordedAt = $request->recorded_at
            ? new \DateTime($request->recorded_at)
            : new \DateTime();

        $log = $this->logService->createFromAudio(
            $audioFile,
            $recordedAt,
            $request->user()->id
        );

        return response()->json([
            'data' => new LogResource($log),
            'message' => 'Log created successfully',
        ], 201);
    }

    public function show(Request $request, Log $log): JsonResponse
    {
        if ($log->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json(['data' => new LogResource($log)]);
    }

    public function update(UpdateLogRequest $request, Log $log): JsonResponse
    {
        if ($log->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $log->fill($request->validated());
        $log->save();

        return response()->json([
            'data' => new LogResource($log),
            'message' => 'Log updated successfully',
        ]);
    }

    public function destroy(Request $request, Log $log): JsonResponse
    {
        if ($log->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $log->delete();

        return response()->json(['message' => 'Log deleted successfully']);
    }
}
