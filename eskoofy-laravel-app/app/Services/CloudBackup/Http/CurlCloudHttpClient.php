<?php

namespace App\Services\CloudBackup\Http;

use App\Services\CloudBackup\Contracts\CloudHttpClient;

/**
 * cURL transport. HTTPS only, TLS verification on, always time-bounded.
 */
class CurlCloudHttpClient implements CloudHttpClient
{
    public function request(string $method, string $url, array $headers = [], string|array|null $body = null): array
    {
        if (! str_starts_with($url, 'https://')) {
            return [
                'status' => 0,
                'body' => '',
                'headers' => [],
                'error' => 'Refusing non-HTTPS request.',
            ];
        }

        $handle = curl_init();
        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name.': '.$value;
        }

        // Collected via CURLOPT_HEADERFUNCTION rather than CURLINFO_RESPONSE_HEADERS:
        // the latter hands back one raw multi-line string, which cannot be split
        // into name => value pairs.
        $responseHeaders = [];
        $collectHeader = function ($handle, string $line) use (&$responseHeaders): int {
            $length = strlen($line);
            $line = trim($line);

            // A blank line ends a header block; 1xx/2xx interim responses and any
            // redirect hop are discarded so only the final block survives.
            if ($line === '') {
                $responseHeaders = [];

                return $length;
            }

            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $responseHeaders[strtolower(trim($name))] = trim($value);
            }

            return $length;
        };

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            // No redirects and no protocol downgrade: a provider must not be able
            // to bounce an authenticated request to another host or scheme.
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_MAXREDIRS => 0,
            CURLOPT_PROTOCOLS_STR => 'https',
            CURLOPT_REDIR_PROTOCOLS_STR => 'https',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => (int) config('backup.http.timeout', 60),
            CURLOPT_CONNECTTIMEOUT => (int) config('backup.http.connect_timeout', 15),
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_HEADERFUNCTION => $collectHeader,
        ];

        if (is_array($body)) {
            $parts = $this->multipart($body);
            $options[CURLOPT_POSTFIELDS] = $parts;
            // Let cURL add the multipart boundary itself.
            $options[CURLOPT_HTTPHEADER] = array_values(array_filter(
                $headerLines,
                static fn (string $line) => ! str_starts_with(strtolower($line), 'content-type:')
            ));
        } elseif ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }

        curl_setopt_array($handle, $options);
        $response = curl_exec($handle);
        $error = curl_errno($handle) !== 0 ? curl_error($handle) : null;
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        return [
            'status' => $status,
            'body' => $response === false ? '' : (string) $response,
            'headers' => $responseHeaders,
            'error' => $error,
        ];
    }

    /**
     * Build a multipart field list.
     *
     * A field value is either a scalar (sent verbatim) or a file descriptor
     * `['path' => string, 'filename' => string, 'mime' => string]`, which
     * becomes a real file part. `path` is the on-disk file — CURLFile needs a
     * path, not the bytes. Sending an explicit MIME type matters too:
     * providers reject a `.zip` that arrives as `application/octet-stream`.
     *
     * @param  array<string, mixed>  $fields
     * @return array<int, mixed>
     */
    private function multipart(array $fields): array
    {
        $parts = [];
        foreach ($fields as $name => $value) {
            if (is_array($value)) {
                $path = (string) ($value['path'] ?? '');
                if ($path !== '' && is_file($path)) {
                    $parts[] = new \CURLFile(
                        $path,
                        (string) ($value['mime'] ?? 'application/octet-stream'),
                        (string) ($value['filename'] ?? basename($path))
                    );

                    continue;
                }

                // Not a readable file — fall through and send it as a plain field
                // rather than building a part that points at nothing.
            }

            $parts[] = [
                'name' => (string) $name,
                'contents' => is_scalar($value) || $value === null ? (string) $value : '',
            ];
        }

        return $parts;
    }
}
