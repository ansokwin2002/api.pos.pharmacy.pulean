<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FileController extends Controller
{
    /**
     * POST /manageapi/fileupload/upload
     * Uploads a file (image) and returns its public path.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'image', 'max:5120'],
        ]);

        $file = $request->file('file');
        $path = $file->store('uploads', 'public');

        $url = rtrim($request->root(), '/') . '/storage/' . ltrim($path, '/');

        return response()->json([
            'code' => '1',
            'message' => 'File uploaded successfully.',
            'data' => [
                'filePath' => $url,
                'path' => $path,
                'url' => $url,
            ],
        ]);
    }
}
