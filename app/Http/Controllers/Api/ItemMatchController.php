<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ItemMatch;
use Illuminate\Http\Request;

class ItemMatchController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'status' => 'sometimes|string|max:30',
            'lost_item_id' => 'sometimes|integer|exists:items,id',
            'found_item_id' => 'sometimes|integer|exists:items,id',
        ]);

        $userId = (int) $validated['user_id'];

        $query = ItemMatch::with(['lostItem', 'foundItem'])
            ->where(function ($q) use ($userId) {
                $q->whereHas('lostItem', function ($itemQuery) use ($userId) {
                    $itemQuery->where('user_id', $userId);
                })->orWhereHas('foundItem', function ($itemQuery) use ($userId) {
                    $itemQuery->where('user_id', $userId);
                });
            });

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('lost_item_id')) {
            $query->where('lost_item_id', $request->query('lost_item_id'));
        }

        if ($request->filled('found_item_id')) {
            $query->where('found_item_id', $request->query('found_item_id'));
        }

        $matches = $query->paginate(15);

        $matches->getCollection()->transform(function ($match) use ($userId) {
            $userOwnsLostItem =
                (int) $match->lostItem->user_id === $userId;

            // Return only the other user's report.
            $oppositeItem = $userOwnsLostItem
                ? $match->foundItem
                : $match->lostItem;

            return [
                'id' => $match->id,
                'match_score' => $match->match_score,
                'status' => $match->status,
                'lost_item_id' => $match->lost_item_id,
                'found_item_id' => $match->found_item_id,
                'lost_reporter_response' => $match->lost_reporter_response,
                'found_reporter_response' => $match->found_reporter_response,
                'lost_user_confirmed_at' => $match->lost_user_confirmed_at,
                'found_user_confirmed_at' => $match->found_user_confirmed_at,
                'created_at' => $match->created_at,
                'updated_at' => $match->updated_at,

                'item' => $oppositeItem,
            ];
        });

        return response()->json($matches);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lost_item_id' => 'required|exists:items,id|different:found_item_id',
            'found_item_id' => 'required|exists:items,id',
            'match_score' => 'nullable|numeric|min:0|max:100',
            'status' => 'sometimes|string|max:20',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $lostItem = \App\Models\Item::findOrFail($validated['lost_item_id']);
        $foundItem = \App\Models\Item::findOrFail($validated['found_item_id']);

        if ($lostItem->type !== 'lost' || $foundItem->type !== 'found') {
            return response()->json([
                'message' => 'The lost item ID must refer to a lost report, and the found item ID must refer to a found report.'
            ], 422);
        }

        $match = ItemMatch::create($validated);

        return response()->json(
            $match->load(['lostItem', 'foundItem']),
            201
        );
    }

    public function show(ItemMatch $itemMatch)
    {
        return response()->json(
            $itemMatch->load(['lostItem', 'foundItem'])
        );
    }

    public function update(Request $request, ItemMatch $itemMatch)
    {
        $validated = $request->validate([
            'lost_item_id' => 'sometimes|required|exists:items,id',
            'found_item_id' => 'sometimes|required|exists:items,id',
            'match_score' => 'sometimes|nullable|numeric|min:0|max:100',

            'lost_reporter_response' => 'sometimes|nullable|in:interested,dismissed',
            'found_reporter_response' => 'sometimes|nullable|in:interested,dismissed',

            'lost_user_confirmed_at' => 'sometimes|nullable|date',
            'found_user_confirmed_at' => 'sometimes|nullable|date',

            'status' => 'sometimes|string|max:30',
        ]);

        $lostId = $validated['lost_item_id'] ?? $itemMatch->lost_item_id;
        $foundId = $validated['found_item_id'] ?? $itemMatch->found_item_id;

        if ($lostId == $foundId) {
            return response()->json([
                'message' => 'A report cannot match itself.'
            ], 422);
        }

        $lostItem = \App\Models\Item::findOrFail($lostId);
        $foundItem = \App\Models\Item::findOrFail($foundId);

        if ($lostItem->type !== 'lost' || $foundItem->type !== 'found') {
            return response()->json([
                'message' => 'Please select one lost report and one found report.'
            ], 422);
        }

        $userId = (int) $request->input('user_id');

        $lostResponseProvided = array_key_exists(
            'lost_reporter_response',
            $validated
        );

        $foundResponseProvided = array_key_exists(
            'found_reporter_response',
            $validated
        );

        if ($lostResponseProvided && $foundResponseProvided) {
            return response()->json([
                'message' => 'Update only your own response.',
            ], 422);
        }

        if (
            $lostResponseProvided &&
            (int) $itemMatch->lostItem->user_id !== $userId
        ) {
            return response()->json([
                'message' => 'You do not own the lost report.',
            ], 403);
        }

        if (
            $foundResponseProvided &&
            (int) $itemMatch->foundItem->user_id !== $userId
        ) {
            return response()->json([
                'message' => 'You do not own the found report.',
            ], 403);
        }

        $itemMatch->update($validated);
        $itemMatch->refresh();
        // Automatically update status when either user responds.
        if (
            array_key_exists('lost_reporter_response', $validated) ||
            array_key_exists('found_reporter_response', $validated)
        ) {
            if (
                $itemMatch->lost_reporter_response === 'dismissed' ||
                $itemMatch->found_reporter_response === 'dismissed'
            ) {
                $itemMatch->status = 'dismissed';
            } elseif (
                $itemMatch->lost_reporter_response === 'interested' &&
                $itemMatch->found_reporter_response === 'interested'
            ) {
                $itemMatch->status = 'verification_pending';
            } else {
                $itemMatch->status = 'pending';
            }

            $itemMatch->save();
        }

        return response()->json(
            $itemMatch->fresh()->load(['lostItem', 'foundItem'])
        );
    }

    public function destroy(ItemMatch $itemMatch)
    {
        $itemMatch->delete();

        return response()->json([
            'message' => 'Item match deleted successfully.'
        ]);
    }

    public function respond(Request $request, ItemMatch $itemMatch)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'response' => ['required', 'in:interested,dismissed'],
        ]);

        $itemMatch->load(['lostItem', 'foundItem']);

        $userId = (int) $validated['user_id'];
        $response = $validated['response'];

        // Determine which report belongs to this user.
        if ((int) $itemMatch->lostItem->user_id === $userId) {
            $responseField = 'lost_reporter_response';
        } elseif ((int) $itemMatch->foundItem->user_id === $userId) {
            $responseField = 'found_reporter_response';
        } else {
            return response()->json([
                'message' => 'You do not own either report in this match.',
            ], 403);
        }

        // Only active matches can receive responses.
        if (! in_array($itemMatch->status, [
            'pending',
            'pending_verification',
        ], true)) {
            return response()->json([
                'message' => 'This match can no longer receive responses.',
            ], 422);
        }

        // Do not allow changing a previous response.
        if ($itemMatch->{$responseField} !== null) {
            return response()->json([
                'message' => 'You have already responded to this match.',
            ], 422);
        }

        // Save only the current user's response.
        $itemMatch->{$responseField} = $response;

        if ($response === 'dismissed') {
            $itemMatch->status = 'dismissed';
        } elseif (
            $itemMatch->lost_reporter_response === 'interested'
            && $itemMatch->found_reporter_response === 'interested'
        ) {
            $itemMatch->status = 'pending_verification';
        } else {
            $itemMatch->status = 'pending';
        }

        $itemMatch->save();

        return response()->json([
            'message' => 'Your response has been recorded.',
            'data' => $itemMatch->fresh([
                'lostItem',
                'foundItem',
            ]),
        ]);
    }

    public function startHandover(ItemMatch $itemMatch)
        {
            if ($itemMatch->status !== 'verification_pending') {
                return response()->json([
                    'message' => 'The match must be awaiting verification first.',
                ], 422);
            }

            if (
                $itemMatch->lost_reporter_response !== 'interested' ||
                $itemMatch->found_reporter_response !== 'interested'
            ) {
                return response()->json([
                    'message' => 'Both reporters must express interest first.',
                ], 422);
            }

            $itemMatch->status = 'verified';
            $itemMatch->save();

            return response()->json(
                $itemMatch->fresh()->load(['lostItem', 'foundItem'])
            );
        }

    public function confirmHandover(Request $request, ItemMatch $itemMatch)
        {
            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
            ]);

            if ($itemMatch->status !== 'verified') {
                return response()->json([
                    'message' => 'The match is not awaiting handover confirmation.',
                ], 422);
            }

            $userId = (int) $validated['user_id'];

            $lostItem = $itemMatch->lostItem;
            $foundItem = $itemMatch->foundItem;

            if ($userId === (int) $lostItem->user_id) {
                $itemMatch->lost_user_confirmed_at ??= now();
            } elseif ($userId === (int) $foundItem->user_id) {
                $itemMatch->found_user_confirmed_at ??= now();
            } else {
                return response()->json([
                    'message' => 'You are not a reporter in this match.',
                ], 403);
            }

            if (
                $itemMatch->lost_user_confirmed_at &&
                $itemMatch->found_user_confirmed_at
            ) {
                \Illuminate\Support\Facades\DB::transaction(function () use (
                    $itemMatch,
                    $lostItem,
                    $foundItem
                ) {
                    $itemMatch->status = 'resolved';
                    $itemMatch->save();

                    $lostItem->status = 'resolved';
                    $lostItem->save();

                    $foundItem->status = 'resolved';
                    $foundItem->save();

                    // Other unresolved suggestions involving either resolved
                    // report are no longer actionable.
                    ItemMatch::where(function ($query) use ($lostItem, $foundItem) {
                        $query->whereIn('lost_item_id', [
                            $lostItem->id,
                            $foundItem->id,
                        ])->orWhereIn('found_item_id', [
                            $lostItem->id,
                            $foundItem->id,
                        ]);
                    })
                        ->where('id', '!=', $itemMatch->id)
                        ->whereNotIn('status', ['resolved', 'dismissed'])
                        ->update(['status' => 'dismissed']);
                });
            } else {
                $itemMatch->save();
            }

            return response()->json(
                $itemMatch->fresh()->load(['lostItem', 'foundItem'])
            );
        }


public function markResolved(Request $request, ItemMatch $itemMatch)
{
    $validated = $request->validate([
        'user_id' => ['required', 'integer', 'exists:users,id'],
    ]);

    $itemMatch->load(['lostItem', 'foundItem']);

    $userId = (int) $validated['user_id'];

    if (
        (int) $itemMatch->lostItem->user_id !== $userId &&
        (int) $itemMatch->foundItem->user_id !== $userId
    ) {
        return response()->json([
            'message' => 'You do not own either report in this match.',
        ], 403);
    }

    if ($itemMatch->status !== 'verified') {
        return response()->json([
            'message' => 'Only verified matches can be resolved.',
        ], 422);
    }

    // Record this reporter's handover confirmation.
    if ((int) $itemMatch->lostItem->user_id === $userId) {
        $itemMatch->lost_user_handover_at ??= now();
    } else {
        $itemMatch->found_user_handover_at ??= now();
    }

    // Resolve only after both reporters confirm.
    if (
        $itemMatch->lost_user_handover_at &&
        $itemMatch->found_user_handover_at
    ) {
        \Illuminate\Support\Facades\DB::transaction(
            function () use ($itemMatch) {
                $itemMatch->status = 'resolved';
                $itemMatch->save();

                $itemMatch->lostItem()->update([
                    'status' => 'resolved',
                ]);

                $itemMatch->foundItem()->update([
                    'status' => 'resolved',
                ]);

                // Dismiss other active suggestions involving either item.
                \App\Models\ItemMatch::where('id', '!=', $itemMatch->id)
                    ->whereIn('status', [
                        'pending',
                        'pending_verification',
                        'verified',
                        'handover_pending',
                    ])
                    ->where(function ($query) use ($itemMatch) {
                        $query->where(
                            'lost_item_id',
                            $itemMatch->lost_item_id
                        )->orWhere(
                            'found_item_id',
                            $itemMatch->lost_item_id
                        )->orWhere(
                            'lost_item_id',
                            $itemMatch->found_item_id
                        )->orWhere(
                            'found_item_id',
                            $itemMatch->found_item_id
                        );
                    })
                    ->update(['status' => 'dismissed']);
            }
        );
    } else {
        $itemMatch->save();
    }

    return response()->json([
        'message' => $itemMatch->fresh()->status === 'resolved'
            ? 'The handover is confirmed by both reporters. The match is resolved.'
            : 'Your handover confirmation has been recorded. Waiting for the other reporter.',
        'data' => $itemMatch->fresh()->load(['lostItem', 'foundItem']),
    ]);
}

}
