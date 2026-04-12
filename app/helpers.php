<?php

if (! function_exists('clean')) {
    /**
     * Sanitize an HTML string using HTMLPurifier.
     * Drop-in replacement for mews/purifier's clean() helper.
     */
    function clean(string $dirty, string $config = 'default'): string
    {
        return app(\App\Services\HtmlSanitizer::class)->clean($dirty, $config);
    }
}
