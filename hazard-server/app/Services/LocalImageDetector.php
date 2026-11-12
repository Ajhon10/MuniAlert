<?php

namespace App\Services;

use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Http;

class LocalImageDetector
{
    public function configured(): bool
    {
        $url = config('image_screening.endpoint');
        $parts = is_string($url) ? parse_url($url) : false;

        // Numeric loopback only: never forward private photos to a public host,
        // a DNS-controlled name, a redirect, or a credential/query-bearing URL.
        return config('image_screening.enabled') && $parts
            && in_array($parts['host'] ?? '', ['127.0.0.1', '[::1]'], true)
            && in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            && ! isset($parts['user']) && ! isset($parts['pass']) && ! isset($parts['query']) && ! isset($parts['fragment']);
    }

    public function assess(string $bytes): array
    {
        if (! $this->configured() || strlen($bytes) > 1024 * 1024) {
            throw new \RuntimeException('Local image assessment is unavailable.');
        }
        try {
            $client = Http::acceptJson()->connectTimeout(2)->timeout(8)->withoutRedirecting()->withOptions(['stream' => true, 'read_timeout' => 8]);
            if (filled(config('image_screening.token'))) {
                $client = $client->withToken(config('image_screening.token'));
            }
            $response = $client->attach('image', $bytes, 'evidence.webp', ['Content-Type' => 'image/webp'])->post(config('image_screening.endpoint'));
            $stream = $response->toPsrResponse()->getBody();
            try {
                $body = Utils::copyToString($stream, 16385);
            } finally {
                $stream->close();
            }
            if (! $response->successful() || strlen($body) > 16384) {
                throw new \RuntimeException;
            }
            $data = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
            $score = $data['ai_probability'] ?? null;
            $model = $data['model'] ?? null;
            if ((! is_float($score) && ! is_int($score)) || ! is_finite((float) $score) || $score < 0 || $score > 1 || ! is_string($model) || trim($model) === '' || mb_strlen($model) > 160) {
                throw new \RuntimeException;
            }

            return ['ai_probability' => $score, 'ai_model' => $model, 'ai_status' => $score >= .8 ? 'possible_ai' : ($score <= .2 ? 'low_signal' : 'inconclusive')];
        } catch (\Throwable) {
            // Do not serialize private bytes, detector credentials or response
            // bodies into queue failures or application exception context.
            throw new \RuntimeException('Local image assessment failed.');
        }
    }
}
