<?php

/**
 * class Jwt
 * Implementasi JWT (JSON Web Token) sederhana dengan algoritma HS256.
 * Dibuat manual (tanpa library eksternal / composer) agar sesuai
 * ketentuan proyek: tidak pakai library eksternal kecuali diminta.
 */

class Jwt
{
    /**
     * Ganti dengan secret key yang lebih aman untuk production.
     * @var string
     */
    private static $secretKey = 'ganti_secret_key_kelompok4_2026';
    /**
     * @var int
     */
    private static $expireSeconds = 86400; // 24 jam

    /**
     * @param string $data
     * @return string
     */
    private static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @param string $data
     * @return string|false
     */
    private static function base64UrlDecode($data)
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Membuat token JWT baru dari payload (misalnya id user & role).
     */
    /**
     * @param array $payload
     * @return string
     */
    public static function encode($payload)
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];

        $payload['iat'] = time();
        $payload['exp'] = time() + self::$expireSeconds;

        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac(
            'sha256',
            $headerEncoded . '.' . $payloadEncoded,
            self::$secretKey,
            true
        );

        $signatureEncoded = self::base64UrlEncode($signature);

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    /**
     * Memverifikasi dan mendekode token JWT.
     * Mengembalikan array payload jika valid, atau false jika tidak valid/kedaluwarsa.
     */
    /**
     * @param string $token
     * @return array|false
     */
    public static function decode($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        $headerEncoded = $parts[0];
        $payloadEncoded = $parts[1];
        $signatureEncoded = $parts[2];

        $signature = self::base64UrlEncode(hash_hmac(
            'sha256',
            $headerEncoded . '.' . $payloadEncoded,
            self::$secretKey,
            true
        ));

        if (!hash_equals($signature, $signatureEncoded)) {
            return false; // Tanda tangan tidak valid
        }

        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        if (!$payload || (isset($payload['exp']) && $payload['exp'] < time())) {
            return false; // Token kedaluwarsa
        }

        return $payload;
    }

    /**
     * Mengambil token dari header Authorization: Bearer <token>.
     */
    /**
     * @return string|null
     */
    public static function getBearerToken()
    {
        $authHeader = '';

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (empty($authHeader) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = trim($_SERVER['HTTP_AUTHORIZATION']);
        }

        if (empty($authHeader) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        }

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
