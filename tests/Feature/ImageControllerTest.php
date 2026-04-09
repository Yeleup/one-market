<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('returns 404 when the source image does not exist', function (): void {
    Storage::fake('public');

    $this->get(route('image.show', ['path' => 'products/missing.png']))
        ->assertNotFound();
});

it('renders a transformed image response', function (): void {
    Storage::fake('public');

    $imagePath = UploadedFile::fake()->image('sample.jpg')->storeAs('products', 'sample.jpg', 'public');

    $response = $this->get(route('image.show', [
        'path' => $imagePath,
        'w' => 120,
        'fm' => 'webp',
    ], absolute: false));

    $response->assertOk();
    $response->assertHeader('content-type', 'image/webp');
});
