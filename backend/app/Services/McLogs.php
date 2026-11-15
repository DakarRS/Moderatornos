<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class McLogs
{
    // Far bigger than any crash report, but stops a huge log from tying up a worker.
    private const MAX_BYTES = 10 * 1024 * 1024;

    // Only the id is taken from the link, so the request always goes to mclo.gs itself.
    public static function idFromLink(string $link): ?string
    {
        return preg_match('~^(?:https?://)?(?:www\.)?mclo\.gs/([A-Za-z0-9]{1,32})/?$~', trim($link), $match) ? $match[1] : null;
    }

    /**
     * @throws ValidationException when mclo.gs has no log with this id
     * @throws HttpException when mclo.gs can't be reached
     */
    public static function raw(string $id, string $field = 'url'): string
    {
        try {
            $response = Http::timeout(8)->accept('text/plain')->get("https://api.mclo.gs/1/raw/{$id}");
        } catch (ConnectionException) {
            abort(502, 'mclo.gs isn’t responding right now. Try again in a moment.');
        }

        if ($response->notFound()) {
            throw ValidationException::withMessages([$field => 'We couldn’t find that log on mclo.gs.']);
        }
        if (! $response->successful()) {
            abort(502, 'mclo.gs isn’t responding right now. Try again in a moment.');
        }

        return substr($response->body(), 0, self::MAX_BYTES);
    }
}
