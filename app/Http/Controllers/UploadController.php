<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    // POST /api/upload
    public function single(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|mimes:jpeg,png,webp,gif|max:5120',
        ]);

        $url = $this->storeFile($request->file('image'));

        return Response::json(['success' => true, 'data' => ['url' => $url]]);
    }

    // POST /api/upload/multiple
    public function multiple(Request $request): JsonResponse
    {
        $request->validate([
            'images'   => 'required|array|min:1|max:6',
            'images.*' => 'file|mimes:jpeg,png,webp,gif|max:5120',
        ]);

        $urls = [];
        foreach ($request->file('images') as $file) {
            $urls[] = $this->storeFile($file);
        }

        return Response::json(['success' => true, 'data' => ['urls' => $urls]]);
    }

    private function storeFile($file): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path     = $file->storeAs('uploads', $filename, 'public');
        return '/storage/' . $path;
    }
}
