<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

class HtmlSanitizer
{
    /**
     * Purify an HTML string using the named config profile from config/purifier.php.
     */
    public function clean(string $dirty, string $config = 'default'): string
    {
        $settings = config("purifier.settings.{$config}", config('purifier.settings.default', []));

        $purifierConfig = HTMLPurifier_Config::createDefault();
        $purifierConfig->set('Core.Encoding', config('purifier.encoding', 'UTF-8'));

        if (config('purifier.finalize', true)) {
            $purifierConfig->set('Cache.DefinitionImpl', null);
        }

        $cachePath = config('purifier.cachePath');
        if ($cachePath) {
            if (! is_dir($cachePath)) {
                mkdir($cachePath, config('purifier.cacheFileMode', 0755), true);
            }
            $purifierConfig->set('Cache.SerializerPath', $cachePath);
        }

        foreach ($settings as $key => $value) {
            $purifierConfig->set($key, $value);
        }

        return (new HTMLPurifier($purifierConfig))->purify($dirty);
    }
}
