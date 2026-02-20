<?php

namespace App\Vault\Pipes;

use Closure;
use Illuminate\Support\Str;

class GenerateUuid
{
    public function handle($payload, Closure $next)
    {
        // Generate a UUID for the storage filename
        // We persist it in the payload for the next pipe
        $payload['uuid'] = (string) Str::uuid();

        return $next($payload);
    }
}
