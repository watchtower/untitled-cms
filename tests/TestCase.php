<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Prevent ViteManifestNotFoundException in tests — hot file makes
        // Vite use dev-server URLs instead of looking up the manifest.
        file_put_contents(public_path('hot'), 'http://localhost:5173');
    }

    protected function tearDown(): void
    {
        @unlink(public_path('hot'));
        parent::tearDown();
    }
}
