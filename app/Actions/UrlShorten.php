<?php

namespace App\Actions;

use App\Models\UrlShortener;
use Random\RandomException;

class UrlShorten
{
    /**
     * @throws RandomException
     */
    public static function generateShortCode(): string
    {
        return substr(base_convert(bin2hex(random_bytes(4)), 16, 36), 0, 6);
    }

    public static function existUrl(string $url): bool|string
    {
        $existingUrl = UrlShortener::query()
            ->where('original_url', $url)->first();
        if ($existingUrl)
            return $existingUrl->short_url;
        else
            return false;
    }

    public static function saveUrl(string $url, string $shortUrl): void
    {
        UrlShortener::query()->create([
            'original_url' => $url,
            'short_url' => $shortUrl,
        ]);
    }

    public static function fullUrl(string $shortUrl): string
    {
        return config('app.url') ."/". $shortUrl;
    }
}
