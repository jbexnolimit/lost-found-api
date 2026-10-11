<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
use App\Services\ItemMatchingService;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::with(['category', 'photos'])
            ->orderByDesc('created_at');

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate(15));
    }

public function store(
    Request $request,
    ItemMatchingService $matchingService
) {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'required|in:lost,found',
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'identifying_features' => 'nullable|string',
            'serial_number' => 'nullable|string|max:255',
            'incident_date' => 'nullable|date',
            'incident_time' => 'nullable|date_format:H:i',
            'location' => 'nullable|string|max:255',
            'last_known_location' => 'nullable|string|max:255',
            'additional_details' => 'nullable|string',
            'preferred_contact_method' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:255',
            'status' => 'sometimes|string|max:30',
            'submitted_at' => 'nullable|date',
        ]);

        $item = Item::create($validated);

        // Automatically search for possible matches.
        $matchingService->findMatches($item);

        return response()->json(
            $item->load(['category', 'photos']),
            201
        );
    }

    public function show(Item $item)
    {
        return response()->json(
            $item->load(['category', 'photos'])
        );
    }

    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'user_id' => 'sometimes|required|exists:users,id',
            'category_id' => 'sometimes|nullable|exists:categories,id',
            'type' => 'sometimes|required|in:lost,found',
            'name' => 'sometimes|required|string|max:255',
            'brand' => 'sometimes|nullable|string|max:255',
            'model' => 'sometimes|nullable|string|max:255',
            'color' => 'sometimes|nullable|string|max:100',
            'description' => 'sometimes|nullable|string',
            'identifying_features' => 'sometimes|nullable|string',
            'serial_number' => 'sometimes|nullable|string|max:255',
            'incident_date' => 'sometimes|nullable|date',
            'incident_time' => 'sometimes|nullable|date_format:H:i',
            'location' => 'sometimes|nullable|string|max:255',
            'last_known_location' => 'sometimes|nullable|string|max:255',
            'additional_details' => 'sometimes|nullable|string',
            'preferred_contact_method' => 'sometimes|nullable|string|max:255',
            'contact_phone' => 'sometimes|nullable|string|max:50',
            'contact_email' => 'sometimes|nullable|email|max:255',
            'status' => 'sometimes|string|max:30',
            'submitted_at' => 'sometimes|nullable|date',
        ]);

        $item->update($validated);

        return response()->json(
            $item->fresh()->load(['category', 'photos'])
        );
    }

    public function destroy(Item $item)
    {
        $item->delete();

        return response()->json([
            'message' => 'Item deleted successfully.'
        ]);
    }
}
