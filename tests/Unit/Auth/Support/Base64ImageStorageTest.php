<?php

namespace Tests\Unit\Auth\Support;

use App\Auth\Support\Base64ImageStorage;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Base64ImageStorageTest extends TestCase
{
    #[Test]
    public function store_rejects_invalid_base64_image_data(): void
    {
        Storage::fake('local');

        $this->expectException(\InvalidArgumentException::class);

        (new Base64ImageStorage)->store('data:image/png;base64,invalid*base64', 'id-photos');
    }
}
