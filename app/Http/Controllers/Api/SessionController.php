<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SessionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'booth_id' => ['required', 'exists:booths,id'],
            'taken_at' => ['nullable', 'date'],

            'media' => ['required', 'array', 'min:1'],
            'media.*.type' => ['required', 'in:photo,video'],
            'media.*.file_name' => ['required', 'string'],
            'media.*.path' => ['required', 'string'],
            'media.*.size' => ['required', 'integer'],
        ]);

        DB::beginTransaction();

        try {

            $session = Session::create([
                'booth_id' => $validated['booth_id'],
                'session_code' => now()->format('YmdHis') . rand(100, 999),
                'taken_at' => $validated['taken_at'] ?? now(),
            ]);

            foreach ($validated['media'] as $item) {

                $base64 = $item['path'];

                // hilangkan prefix
                preg_match('/^data:image\/(\w+);base64,/', $base64, $matches);

                $extension = $matches[1] ?? 'jpg';

                $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64);

                $image = base64_decode($base64);

                $filename = Str::uuid() . '.' . $extension;

                $folder = "sessions/{$session->session_code}";

                Storage::disk('public')->put(
                    "{$folder}/{$filename}",
                    $image
                );

                $session->media()->create([
                    'type' => $item['type'],
                    'file_name' => $filename,
                    'path' => "storage/{$folder}/{$filename}",
                    'size' => strlen($image),
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Session created successfully.',
                'data' => $session->load(['booth', 'media'])
            ], 201);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Failed create session.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
