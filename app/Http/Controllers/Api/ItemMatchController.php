<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ItemMatch;
use Illuminate\Http\Request;

class ItemMatchController extends Controller
{
    public function index(Request $request)
    {
        $query = ItemMatch::with(['lostItem', 'foundItem']);

        if ($request->filled('user_id')) {
            $userId = $request->query('user_id');

            $query->where(function ($q) use ($userId) {
                $q->whereHas('lostItem', function ($itemQuery) use ($userId) {
                    $itemQuery->where('user_id', $userId);
                })->orWhereHas('foundItem', function ($itemQuery) use ($userId) {
                    $itemQuery->where('user_id', $userId);
                });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('lost_item_id')) {
            $query->where('lost_item_id', $request->query('lost_item_id'));
        }

        if ($request->filled('found_item_id')) {
            $query->where('found_item_id', $request->query('found_item_id'));
        }

        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lost_item_id' => 'required|exists:items,id|different:found_item_id',
            'found_item_id' => 'required|exists:items,id',
            'match_score' => 'nullable|numeric|min:0|max:100',
            'status' => 'sometimes|string|max:20',
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
            'status' => 'sometimes|string|max:20',
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

        $itemMatch->update($validated);

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
}
