<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use League\Glide\Filesystem\FileNotFoundException;
use League\Glide\Filesystem\FilesystemException;
use League\Glide\ServerFactory;

class ImageController extends Controller
{
    /**
     * @throws FileNotFoundException
     * @throws FilesystemException
     */
    public function show(Request $request, string $path): Response
    {
        $disk = Storage::disk('public');

        abort_unless($disk->exists($path), 404);

        $server = ServerFactory::create([
            'source' => $disk->getDriver(),
            'cache' => $disk->getDriver(),
            'cache_path_prefix' => 'cache',
            'base_url' => 'img',
            'driver' => extension_loaded('imagick') ? 'imagick' : 'gd',
        ]);

        $cachedPath = $server->makeImage($path, $request->query());

        return response($disk->getDriver()->read($cachedPath), 200, [
            'Content-Type' => $disk->mimeType($cachedPath) ?? 'application/octet-stream',
            'Content-Length' => (string) ($disk->size($cachedPath) ?? 0),
            'Cache-Control' => 'max-age=31536000, public',
            'Expires' => now()->addYear()->toRfc7231String(),
        ]);
    }
}
