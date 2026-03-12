<?php

declare(strict_types=1);

namespace Eymen\Auth;

/**
 * Pure PHP JWT (JSON Web Token) encoder/decoder.
 *
 * Supports HS256 (HMAC-SHA256) and RS256 (RSA-SHA256) algorithms.
 * Uses timing-safe comparison via hash_equals to prevent timing attacks.
 */
final class JwtEncoder
{
    /**
     * Encode a payload into a JWT token.
     *
     * @param array<string, mixed> $payload The token payload (claims)
     * @param string $secret The signing secret (or PEM private key for RS256)
     * @param string $algo The signing algorithm (HS256 or RS256)
     * @return string The encoded JWT token
     *
     * @throws \InvalidArgumentException If algorithm is unsupported
     * @throws \RuntimeException If signing fails
     */
    public function encode(array $payload, string $secret, string $algo = 'HS256'): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => $algo,
        ];

        $segments = [];
        $segments[] = $this->base64urlEncode(json_encode($header, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        $segments[] = $this->base64urlEncode(json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        $signingInput = implode('.', $segments);
        $signature = $this->sign($signingInput, $secret, $algo);
        $segments[] = $this->base64urlEncode($signature);

        return implode('.', $segments);
    }

    /**
     * Decode a JWT token and return the payload.
     *
     * @param string $token The JWT token to decode
     * @param string $secret The signing secret (or PEM public key for RS256)
     * @param string $algo The expected signing algorithm
     * @return array<string, mixed> The decoded payload
     *
     * @throws \InvalidArgumentException If the token format is invalid
     * @throws \RuntimeException If signature verification fails or token is expired
     */
    public function decode(string $token, string $secret, string $algo = 'HS256'): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('Invalid JWT token format: expected 3 segments.');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $headerJson = $this->base64urlDecode($headerB64);
        if ($headerJson === '') {
            throw new \InvalidArgumentException('Invalid JWT header encoding.');
        }

        $header = json_decode($headerJson, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($header)) {
            throw new \InvalidArgumentException('Invalid JWT header structure.');
        }

        if (($header['alg'] ?? '') !== $algo) {
            throw new \InvalidArgumentException(
                sprintf('Algorithm mismatch: expected %s, got %s.', $algo, $header['alg'] ?? 'none')
            );
        }

        $signingInput = $headerB64 . '.' . $payloadB64;
        $signature = $this->base64urlDecode($signatureB64);

        if (!$this->verify($signingInput, $signature, $secret, $algo)) {
            throw new \RuntimeException('Invalid JWT signature.');
        }

        $payloadJson = $this->base64urlDecode($payloadB64);
        if ($payloadJson === '') {
            throw new \InvalidArgumentException('Invalid JWT payload encoding.');
        }

        $payload = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($payload)) {
            throw new \InvalidArgumentException('Invalid JWT payload structure.');
        }

        // Check expiration claim
        if (isset($payload['exp']) && is_numeric($payload['exp'])) {
            if ((int) $payload['exp'] < time()) {
                throw new \RuntimeException('JWT token has expired.');
            }
        }

        return $payload;
    }

    /**
     * Check if a JWT token is expired without verifying the signature.
     *
     * @param string $token The JWT token
     * @return bool Whether the token is expired
     */
    public function isExpired(string $token): bool
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return true;
        }

        $payloadJson = $this->base64urlDecode($parts[1]);
        if ($payloadJson === '') {
            return true;
        }

        try {
            $payload = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return true;
        }

        if (!is_array($payload) || !isset($payload['exp'])) {
            return false; // No expiration claim means not expired
        }

        if (!is_numeric($payload['exp'])) {
            return true;
        }

        return (int) $payload['exp'] < time();
    }

    /**
     * Base64url encode data (RFC 4648 section 5).
     *
     * @param string $data The data to encode
     * @return string The base64url encoded string
     */
    private function base64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64url decode data (RFC 4648 section 5).
     *
     * @param string $data The base64url encoded string
     * @return string The decoded data
     */
    private function base64urlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;

        if ($remainder !== 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        if ($decoded === false) {
            return '';
        }

        return $decoded;
    }

    /**
     * Sign data using the specified algorithm.
     *
     * @param string $data The data to sign
     * @param string $secret The signing secret or private key
     * @param string $algo The signing algorithm
     * @return string The raw signature bytes
     *
     * @throws \InvalidArgumentException If algorithm is unsupported
     * @throws \RuntimeException If signing fails
     */
    private function sign(string $data, string $secret, string $algo): string
    {
        return match ($algo) {
            'HS256' => hash_hmac('sha256', $data, $secret, true),
            'HS384' => hash_hmac('sha384', $data, $secret, true),
            'HS512' => hash_hmac('sha512', $data, $secret, true),
            'RS256' => $this->rsaSign($data, $secret, OPENSSL_ALGO_SHA256),
            'RS384' => $this->rsaSign($data, $secret, OPENSSL_ALGO_SHA384),
            'RS512' => $this->rsaSign($data, $secret, OPENSSL_ALGO_SHA512),
            default => throw new \InvalidArgumentException(
                sprintf('Unsupported JWT algorithm: %s. Supported: HS256, HS384, HS512, RS256, RS384, RS512.', $algo)
            ),
        };
    }

    /**
     * Verify a signature using the specified algorithm.
     *
     * Uses timing-safe comparison via hash_equals to prevent timing attacks.
     *
     * @param string $data The original data
     * @param string $signature The signature to verify
     * @param string $secret The signing secret or public key
     * @param string $algo The signing algorithm
     * @return bool Whether the signature is valid
     */
    private function verify(string $data, string $signature, string $secret, string $algo): bool
    {
        return match ($algo) {
            'HS256', 'HS384', 'HS512' => hash_equals(
                $this->sign($data, $secret, $algo),
                $signature
            ),
            'RS256' => $this->rsaVerify($data, $signature, $secret, OPENSSL_ALGO_SHA256),
            'RS384' => $this->rsaVerify($data, $signature, $secret, OPENSSL_ALGO_SHA384),
            'RS512' => $this->rsaVerify($data, $signature, $secret, OPENSSL_ALGO_SHA512),
            default => false,
        };
    }

    /**
     * Sign data using RSA.
     *
     * @param string $data The data to sign
     * @param string $privateKey The PEM-encoded private key
     * @param int $algorithm The OpenSSL algorithm constant
     * @return string The raw signature bytes
     *
     * @throws \RuntimeException If signing fails
     */
    private function rsaSign(string $data, string $privateKey, int $algorithm): string
    {
        $key = openssl_pkey_get_private($privateKey);

        if ($key === false) {
            throw new \RuntimeException('Invalid RSA private key.');
        }

        $signature = '';
        $success = openssl_sign($data, $signature, $key, $algorithm);

        if (!$success) {
            throw new \RuntimeException('RSA signing failed: ' . openssl_error_string());
        }

        return $signature;
    }

    /**
     * Verify data using RSA.
     *
     * @param string $data The original data
     * @param string $signature The signature to verify
     * @param string $publicKey The PEM-encoded public key
     * @param int $algorithm The OpenSSL algorithm constant
     * @return bool Whether the signature is valid
     */
    private function rsaVerify(string $data, string $signature, string $publicKey, int $algorithm): bool
    {
        $key = openssl_pkey_get_public($publicKey);

        if ($key === false) {
            return false;
        }

        return openssl_verify($data, $signature, $key, $algorithm) === 1;
    }
}
