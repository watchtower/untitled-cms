<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Fake a provider config
config([
    'ai.default' => 'openai',
    'ai.providers.openai.key' => 'fake-key',
]);

$agent = new \Laravel\Ai\AnonymousAgent('You are a helpful assistant.', [], []);
try {
    $response = $agent->prompt('What is 1+1?');
    echo "Success: " . $response->text() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
