<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class PublicMediaUrl
{
    public static function for(Request $request, string $path, string $disk = 'public'): string
    {
        $url = Storage::disk($disk)->url($path);
        $urlPath = parse_url($url, PHP_URL_PATH);

        if (! is_string($urlPath) || $urlPath === '') {
            return $url;
        }

        $host = parse_url($url, PHP_URL_HOST);
        $isLocalConfiguredHost = is_string($host)
            && in_array(strtolower($host), ['localhost', '127.0.0.1', '0.0.0.0'], true);

        if ($host === null || $isLocalConfiguredHost) {
            $requestHost = $request->header('host');
            $requestOrigin = is_string($requestHost) && $requestHost !== ''
                ? $request->getScheme().'://'.$requestHost
                : $request->getSchemeAndHttpHost();

            return rtrim($requestOrigin, '/').'/'.ltrim($urlPath, '/');
        }

        return $url;
    }
}
