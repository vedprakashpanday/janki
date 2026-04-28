<?php
namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\MediaConverterService; // Service ko yahan import karna zaroori hai

class MediaController extends Controller
{
    public function upload(Request $request, MediaConverterService $service)
    {
        $request->validate([
            'file' => 'required|file|max:51200', // 50MB Max
        ]);

        $media = $service->uploadAndConvert($request->file('file'));

        if ($media) {
            return response()->json([
                'status' => 'success',
                'message' => 'Media processed and saved successfully',
                'data' => $media
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'Failed to process media'], 422);
    }
}