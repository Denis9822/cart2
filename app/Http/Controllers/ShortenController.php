<?php

namespace App\Http\Controllers;

use App\Actions\UrlShorten;
use Illuminate\Http\Request;
use Random\RandomException;

class ShortenController extends Controller
{
    /**
     * @throws RandomException
     */
    public function __invoke(): string
    {
        $url = "https://translate.google.com";

        $shortCode = UrlShorten::existUrl($url);
        if ($shortCode === false) {
            $shortCode = UrlShorten::generateShortCode();
            UrlShorten::saveUrl($url, $shortCode);
        }

        return UrlShorten::fullUrl($shortCode);
    }
}
