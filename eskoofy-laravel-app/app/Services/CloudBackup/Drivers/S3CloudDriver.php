<?php

namespace App\Services\CloudBackup\Drivers;

/**
 * Amazon S3 (or any S3-compatible endpoint) using the REST API with
 * AWS Signature Version 4 header authentication — no AWS SDK dependency, which
 * keeps the app's runtime dependency set unchanged.
 *
 * S3 verifies a signature against the *headers actually sent*, so this driver
 * has to return the `x-amz-date` / `x-amz-content-sha256` headers alongside the
 * Authorization header and sign exactly those. Signing with query-string auth
 * parameters while sending a header Authorization is a signature mismatch, so
 * everything here is the header form, including ListObjectsV2.
 */
class S3CloudDriver extends AbstractCloudDriver
{
    public function key(): string
    {
        return 's3';
    }

    public function label(): string
    {
        return (string) (config('backup.providers.s3.label') ?? 'Amazon S3 (or compatible)');
    }

    public function test(): array
    {
        $bucket = $this->credential('bucket');
        if ($bucket === null) {
            return $this->fail('S3 bucket is missing.');
        }
        if ($this->credential('key') === null || $this->credential('secret') === null) {
            return $this->fail('S3 access key / secret is missing.');
        }

        // ListObjectsV2 is a cheap, harmless probe that proves the credentials,
        // the region and the endpoint all agree.
        $response = $this->http->request('GET', $this->bucketUrl().'?'.self::query([
            'list-type' => '2',
            'max-keys' => '1',
        ]), $this->signedHeaders('GET', '/', self::query(['list-type' => '2', 'max-keys' => '1']), ''));

        if (($response['error'] ?? null) !== null) {
            return $this->fail('S3 request failed: '.$response['error']);
        }

        if ($response['status'] === 403) {
            return $this->fail('S3 rejected the credentials (HTTP 403). Check the key, secret, region and bucket.');
        }
        if ($response['status'] >= 400) {
            return $this->fail('S3 rejected the request (HTTP '.$response['status'].').');
        }

        return $this->pass('Connected to S3 bucket "'.$bucket.'".');
    }

    public function upload(string $localPath, string $fileName): array
    {
        if (! is_file($localPath)) {
            return ['error' => 'Backup file not found.'];
        }

        $name = $this->sanitizeFileName($fileName);
        $key = $this->folder().'/'.$name;
        $hash = hash_file('sha256', $localPath);

        $response = $this->http->request('PUT', $this->objectUrl($key), $this->signedHeaders(
            'PUT',
            '/'.$this->uriEncodePath($this->bucket().'/'.$key),
            '',
            $hash,
            ['Content-Type' => 'application/zip']
        ), (string) file_get_contents($localPath));

        if (($response['error'] ?? null) !== null || $response['status'] >= 400) {
            return ['error' => 'S3 upload failed (HTTP '.$response['status'].'): '.($response['error'] ?? $this->errorSummary($response))];
        }

        return [
            'id' => $key,
            'name' => $name,
            'size' => (int) filesize($localPath),
            'path' => $key,
        ];
    }

    public function download(string $remoteId, string $localPath): ?string
    {
        $key = ltrim($this->sanitizeFileName($remoteId), '/');
        $response = $this->http->request('GET', $this->objectUrl($key), $this->signedHeaders(
            'GET',
            '/'.$this->uriEncodePath($this->bucket().'/'.$key)
        ));

        if (($response['error'] ?? null) !== null || $response['status'] !== 200) {
            return null;
        }

        $dir = dirname($localPath);
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            return null;
        }

        return @file_put_contents($localPath, $response['body']) !== false ? $localPath : null;
    }

    public function delete(string $remoteId): bool
    {
        $key = ltrim($this->sanitizeFileName($remoteId), '/');
        $response = $this->http->request('DELETE', $this->objectUrl($key), $this->signedHeaders(
            'DELETE',
            '/'.$this->uriEncodePath($this->bucket().'/'.$key)
        ));

        return ($response['error'] ?? null) === null && $response['status'] < 400;
    }

    public function list(): array
    {
        $bucket = $this->credential('bucket');
        if ($bucket === null) {
            return [];
        }

        $query = self::query([
            'list-type' => '2',
            'prefix' => $this->folder().'/',
            'max-keys' => '200',
        ]);

        $response = $this->http->request(
            'GET',
            $this->bucketUrl().'?'.$query,
            $this->signedHeaders('GET', '/'.$this->uriEncodePath($bucket), $query)
        );

        if (($response['error'] ?? null) !== null || $response['status'] !== 200) {
            return [];
        }

        $items = [];
        foreach ($this->decode($response['body'])['Contents'] ?? [] as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $items[] = [
                'id' => (string) ($entry['Key'] ?? ''),
                'name' => basename((string) ($entry['Key'] ?? '')),
                'size' => (int) ($entry['Size'] ?? 0),
                'modified' => strtotime((string) ($entry['LastModified'] ?? '')) ?: 0,
            ];
        }

        usort($items, static fn ($a, $b) => $b['modified'] <=> $a['modified']);

        return $items;
    }

    // ------------------------------------------------------------------ sigv4

    /**
     * Build the request headers for a signed S3 call: the `x-amz-*` headers plus
     * the Authorization header that signs exactly them.
     *
     * @param  array<string, string>  $extra
     * @return array<string, string>
     */
    private function signedHeaders(
        string $method,
        string $canonicalUri,
        string $canonicalQuery = '',
        ?string $payloadHash = null,
        array $extra = [],
    ): array {
        $key = $this->credential('key');
        $secret = $this->credential('secret');
        $region = $this->credential('region') ?? 'us-east-1';

        $payloadHash ??= hash('sha256', '');
        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = substr($amzDate, 0, 8);
        $host = parse_url($this->bucketUrl(), PHP_URL_HOST) ?: 's3.amazonaws.com';

        $headers = array_merge($extra, [
            'Host' => $host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date' => $amzDate,
        ]);

        ksort($headers, SORT_STRING);
        $canonicalHeaders = '';
        $signedHeaderNames = [];
        foreach ($headers as $name => $value) {
            $lower = strtolower($name);
            $signedHeaderNames[] = $lower;
            $canonicalHeaders .= $lower.':'.trim(preg_replace('/\s+/', ' ', $value) ?? (string) $value)."\n";
        }
        $signedHeaders = implode(';', $signedHeaderNames);

        $canonicalRequest = implode("\n", [
            strtoupper($method),
            $canonicalUri === '' ? '/' : $canonicalUri,
            $canonicalQuery,
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash,
        ]);

        $scope = $dateStamp.'/'.$region.'/s3/aws4_request';
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $amzDate,
            $scope,
            hash('sha256', $canonicalRequest),
        ]);

        $sign = static fn (string $k, string $d): string => hash_hmac('sha256', $d, $k);
        $kDate = $sign('AWS4'.(string) $secret, $dateStamp);
        $kRegion = $sign($kDate, $region);
        $kService = $sign($kRegion, 's3');
        $kSigning = $sign($kService, 'aws4_request');

        $headers['Authorization'] = sprintf(
            'AWS4-HMAC-SHA256 Credential=%s/%s, SignedHeaders=%s, Signature=%s',
            (string) $key,
            $scope,
            $signedHeaders,
            hash_hmac('sha256', $stringToSign, $kSigning)
        );

        return $headers;
    }

    /**
     * RFC 3986 encoding for the canonical URI: every byte outside the unreserved
     * set is percent-encoded, and `/` is *not* treated as a path separator.
     */
    private function uriEncodePath(string $path): string
    {
        $segments = explode('/', ltrim($path, '/'));

        return '/'.implode('/', array_map(
            static fn (string $segment): string => rawurlencode($segment),
            $segments
        ));
    }

    /**
     * A canonical query string: RFC 3986 encoding, sorted by key then value.
     *
     * @param  array<string, string>  $params
     */
    private static function query(array $params): string
    {
        ksort($params, SORT_STRING);

        $pairs = [];
        foreach ($params as $name => $value) {
            $pairs[] = [rawurlencode((string) $name), rawurlencode((string) $value)];
        }
        usort($pairs, static fn (array $a, array $b) => $a[0] === $b[0] ? strcmp($a[1], $b[1]) : strcmp($a[0], $b[0]));

        return implode('&', array_map(
            static fn (array $pair): string => $pair[0].'='.$pair[1],
            $pairs
        ));
    }

    private function bucketUrl(): string
    {
        $endpoint = rtrim((string) ($this->credential('endpoint') ?? 'https://s3.amazonaws.com'), '/');
        $bucket = (string) $this->credential('bucket');

        return str_contains($endpoint, '://'.$bucket.'.') ? $endpoint : $endpoint.'/'.$bucket;
    }

    private function objectUrl(string $key): string
    {
        return $this->bucketUrl().'/'.ltrim($key, '/');
    }

    private function errorSummary(array $response): string
    {
        $decoded = $this->decode((string) ($response['body'] ?? ''));

        return (string) ($decoded['Message'] ?? $decoded['message'] ?? 'unknown error');
    }
}
