<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCallSessionRequest;
use App\Http\Requests\UpdateCallSessionRequest;
use App\Http\Resources\CallSessionResource;
use App\Models\CallSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CallSessionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $calls = $request->user()
            ->callSessions()
            ->orderByDesc('started_at')
            ->paginate($request->input('per_page', 20));

        return CallSessionResource::collection($calls);
    }

    public function store(StoreCallSessionRequest $request): JsonResponse
    {
        $call = $request->user()->callSessions()->create($request->validated());

        return response()->json([
            'message' => 'Call session logged',
            'data' => new CallSessionResource($call),
        ], 201);
    }

    public function show(Request $request, int $id): CallSessionResource
    {
        $call = CallSession::where('user_id', $request->user()->id)->findOrFail($id);

        return new CallSessionResource($call);
    }

    public function update(UpdateCallSessionRequest $request, int $id): CallSessionResource
    {
        $call = CallSession::where('user_id', $request->user()->id)->findOrFail($id);
        $call->update($request->validated());

        return new CallSessionResource($call);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $call = CallSession::where('user_id', $request->user()->id)->findOrFail($id);
        $call->delete();

        return response()->json(['message' => 'Call session deleted']);
    }

    public function attachMemo(Request $request, int $id): JsonResponse
    {
        $call = CallSession::where('user_id', $request->user()->id)->findOrFail($id);
        $call->update(['has_voice_memo' => true]);

        return response()->json([
            'message' => 'Voice memo attached',
            'data' => new CallSessionResource($call),
        ]);
    }
}
