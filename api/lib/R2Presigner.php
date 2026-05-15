<?php
/**
 * R2Presigner - CriaVibe
 * Gera URLs assinadas para upload direto do navegador ao Cloudflare R2.
 */
class R2Presigner {
    private string $accessKey;
    private string $secretKey;
    private string $bucket;
    private string $endpoint;
    private string $region = 'auto';

    public function __construct(string $accessKey, string $secretKey, string $bucket, string $endpoint) {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->bucket = $bucket;
        $this->endpoint = rtrim($endpoint, '/');
    }

    public function signedPutUrl(string $r2Path, int $expires = 900): string {
        $host = parse_url($this->endpoint, PHP_URL_HOST);
        $baseUrl = $this->endpoint . '/' . ltrim($r2Path, '/');
        $timestamp = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');
        $credentialScope = "$date/{$this->region}/s3/aws4_request";
        $credential = $this->accessKey . '/' . $credentialScope;

        $query = [
            'X-Amz-Algorithm' => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential' => $credential,
            'X-Amz-Date' => $timestamp,
            'X-Amz-Expires' => (string)max(60, min($expires, 3600)),
            'X-Amz-SignedHeaders' => 'host',
        ];

        $canonicalQuery = $this->canonicalQuery($query);
        $canonicalUri = '/' . $this->bucket . '/' . ltrim($r2Path, '/');
        $canonicalHeaders = "host:$host\n";
        $canonicalRequest = "PUT\n$canonicalUri\n$canonicalQuery\n$canonicalHeaders\nhost\nUNSIGNED-PAYLOAD";

        $stringToSign = "AWS4-HMAC-SHA256\n$timestamp\n$credentialScope\n" . hash('sha256', $canonicalRequest);
        $signature = $this->signature($date, $stringToSign);

        return $baseUrl . '?' . $canonicalQuery . '&X-Amz-Signature=' . $signature;
    }

    private function canonicalQuery(array $query): string {
        ksort($query);
        $parts = [];
        foreach ($query as $key => $value) {
            $parts[] = rawurlencode($key) . '=' . rawurlencode($value);
        }
        return implode('&', $parts);
    }

    private function signature(string $date, string $stringToSign): string {
        $kDate = hash_hmac('sha256', $date, 'AWS4' . $this->secretKey, true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        return hash_hmac('sha256', $stringToSign, $kSigning);
    }
}
