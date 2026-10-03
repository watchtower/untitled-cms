<?php

namespace Tests\Feature;

use App\Models\AiHub;
use App\Services\AiService;
use Tests\TestCase;

class AiServiceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        AiHub::truncate();
    }

    protected function tearDown(): void
    {
        AiHub::truncate();
        parent::tearDown();
    }

    public function test_bedrock_hub_is_rejected(): void
    {
        $hub = new AiHub(['name' => 'Bedrock', 'default_model' => 'any', 'is_active' => true]);
        $hub->forceFill(['api_key' => 'test-key'])->save();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The Bedrock provider is not supported.');

        app(AiService::class)->rawPrompt('system', 'hello');
    }
}
