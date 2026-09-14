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

        // Static files are served from the public storage symlink (public/storage).
        // $request->root() may include the Laravel front controller (e.g.
        // "https://host/public/index.php"); that must be removed so the browser
        // requests the real file instead of routing it through Laravel (404).
        $base = rtrim(str_replace('/index.php', '', $request->root()), '/');
        $url = $base . '/storage/' . ltrim($path, '/');

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
