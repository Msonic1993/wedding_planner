<?php

namespace Tests\Unit\Support;

use App\Support\PublicMediaUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicMediaUrlTest extends TestCase
{
    public function test_it_replaces_a_configured_localhost_with_the_request_host(): void
    {
        Storage::fake('public');
        config()->set('filesystems.disks.public.url', 'http://localhost:8081/storage');
        $request = Request::create('http://192.168.8.101:8081/api/v1/profile');

        $this->assertSame(
            'http://192.168.8.101:8081/storage/profiles/photo.jpg',
            PublicMediaUrl::for($request, 'profiles/photo.jpg'),
        );
    }
}
