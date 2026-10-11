<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ItemPhoto;
use Illuminate\Http\Request;

class ItemPhotoController extends Controller
{
    public function index(Request $request)
    {
        $query = ItemPhoto::with('item');

        if ($request->filled('item_id')) {
            $query->where('item_id', $request->query('item_id'));
        }

        return response()->json($query->orderBy('sort_order')->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'path' => 'required|string|max:255',
            'is_primary' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        if (!empty($validated['is_primary'])) {
            ItemPhoto::where('item_id', $validated['item_id'])
                ->update(['is_primary' => false]);
        }

        $photo = ItemPhoto::create($validated);

        return response()->json($photo, 201);
    }

    public function show(ItemPhoto $itemPhoto)
    {
        return response()->json($itemPhoto->load('item'));
    }

    public function update(Request $request, ItemPhoto $itemPhoto)
    {
        $validated = $request->validate([
            'item_id' => 'sometimes|required|exists:items,id',
            'path' => 'sometimes|required|string|max:255',
            'is_primary' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $targetItemId = $validated['item_id'] ?? $itemPhoto->item_id;

        if (!empty($validated['is_primary'])) {
            ItemPhoto::where('item_id', $targetItemId)
                ->where('id', '!=', $itemPhoto->id)
                ->update(['is_primary' => false]);
        }

        $itemPhoto->update($validated);

        return response()->json($itemPhoto->fresh());
    }

    public function destroy(ItemPhoto $itemPhoto)
    {
        $itemPhoto->delete();

        return response()->json([
            'message' => 'Item photo deleted successfully.'
        ]);
    }
}
