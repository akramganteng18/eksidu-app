<?php

namespace App\Support;

class TotpAuthenticator
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const PERIOD = 30;
    private const DIGITS = 6;

    public function generateSecret(int $bytes = 20): string
    {
        return $this->base32Encode(random_bytes($bytes));
    }

    public function provisioningUri(string $secret, string $account, string $issuer = 'EKSiDU'): string
    {
        $label = rawurlencode($issuer . ':' . $account);
        $query = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ], '', '&', PHP_QUERY_RFC3986);

        return 'otpauth://totp/' . $label . '?' . $query;
    }

    public function currentCode(string $secret, ?int $timestamp = null): string
    {
        return $this->codeForCounter($this->base32Decode($secret), intdiv($timestamp ?? time(), self::PERIOD));
    }

    public function verify(string $secret, string $code, ?int $timestamp = null, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if (! preg_match('/^\d{6}$/', $code)) return false;

        try {
            $key = $this->base32Decode($secret);
        } catch (\InvalidArgumentException) {
            return false;
        }

        $timestamp ??= time();
        $counter = intdiv($timestamp, self::PERIOD);
        for ($offset = -$window; $offset <= $window; $offset++) {
            if (hash_equals($this->codeForCounter($key, $counter + $offset), $code)) {
                return true;
            }
        }

        return false;
    }

    private function codeForCounter(string $key, int $counter): string
    {
        $high = intdiv($counter, 0x100000000);
        $low = $counter & 0xffffffff;
        $hash = hash_hmac('sha1', pack('N2', $high, $low), $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0f;
        $binary = ((ord($hash[$offset]) & 0x7f) << 24)
            | ((ord($hash[$offset + 1]) & 0xff) << 16)
            | ((ord($hash[$offset + 2]) & 0xff) << 8)
            | (ord($hash[$offset + 3]) & 0xff);

        return str_pad((string) ($binary % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    private function base32Encode(string $data): string
    {
        $bits = '';
        foreach (str_split($data) as $char) $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        $encoded = '';
        foreach (str_split($bits, 5) as $chunk) {
            if (strlen($chunk) < 5) $chunk = str_pad($chunk, 5, '0');
            $encoded .= self::ALPHABET[bindec($chunk)];
        }
        return $encoded;
    }

    private function base32Decode(string $encoded): string
    {
        $encoded = strtoupper(preg_replace('/\s+/', '', $encoded) ?? '');
        if ($encoded === '' || preg_match('/[^A-Z2-7=]/', $encoded)) {
            throw new \InvalidArgumentException('Secret TOTP tidak valid.');
        }
        $encoded = rtrim($encoded, '=');
        $bits = '';
        foreach (str_split($encoded) as $char) {
            $position = strpos(self::ALPHABET, $char);
            if ($position === false) throw new \InvalidArgumentException('Secret TOTP tidak valid.');
            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }
        $binary = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) $binary .= chr(bindec($chunk));
        }
        if ($binary === '') throw new \InvalidArgumentException('Secret TOTP tidak valid.');
        return $binary;
    }
}
