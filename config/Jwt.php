<?php  
 * Class Jwt  
 * Implementasi JWT (JSON Web Token) sederhana dengan algoritma HS256.  
 * Dibuat manual (tanpa library eksternal / composer) agar sesuai  * ketentuan proyek:  tidak pakai library eksternal kecuali diminta.
 */ 
 class Jwt 
 { 
 Ganti dengan secret key yang lebih aman untuk production     
 private static string $secretKey = 'ganti_secret_key_kelompok4_2026';    
 private static int $expireSeconds = 86400; // 24 jam 
 private static function base64UrlEncode(string $data): string    
 {        
 return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');   
 }      

    private static function base64UrlDecode($data)
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/'));
    }

    public static function encode(array $payload): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];

        $payload['iat'] = time();
        $payload['exp'] = time() + self::$expireSeconds;

        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, self::$secretKey, true);
        $signatureEncoded = self::base64UrlEncode($signature);

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    public static function decode($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;

        $signature = self::base64UrlEncode(hash_hmac(
            'sha256',
            $headerEncoded . '.' . $payloadEncoded,
            self::$secretKey,
            true
        ));

        if (!hash_equals($signature, $signatureEncoded)) {
            return false;
        }

        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        if (!$payload || (isset($payload['exp']) && $payload['exp'] < time())) {
            return false;
        }

        return $payload;
    }

    public static function getBearerToken()
    {
        $authHeader = '';

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
