<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('student_id', $validated['student_id'])
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid student ID or password.',
            ], 401);
        }

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user->only([
                'id',
                'name',
                'email',
                'student_id',
                'year_level',
                'contact_no',
            ]),
        ], 200);
    }

    public function index()
    {
        return response()->json(
            User::query()
                ->select([
                    'id',
                    'name',
                    'email',
                    'student_id',
                    'year_level',
                    'contact_no',
                    'created_at',
                ])
                ->orderBy('name')
                ->paginate(15)
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'student_id' => ['required', 'string', 'max:255', 'unique:users,student_id'],
            'year_level' => ['nullable', 'string', 'max:50'],
            'contact_no' => ['nullable', 'string', 'max:11'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return response()->json([
            'message' => 'User created successfully.',
            'user' => $user->only([
                'id',
                'name',
                'email',
                'student_id',
                'year_level',
                'contact_no',
            ]),
        ], 201);
    }

    public function show(string $id)
    {
        $user = User::query()
            ->select([
                'id',
                'name',
                'email',
                'student_id',
                'year_level',
                'contact_no',
                'created_at',
            ])
            ->findOrFail($id);

        return response()->json($user);
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'student_id' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'student_id')->ignore($user->id),
            ],
            'year_level' => ['sometimes', 'nullable', 'string', 'max:50'],
            'contact_no' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $user->only([
                'id',
                'name',
                'email',
                'student_id',
                'year_level',
                'contact_no',
            ]),
        ]);
    }

    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }
}
