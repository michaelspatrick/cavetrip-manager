<?php

declare(strict_types=1);

namespace CaveTrip\Services;

use CaveTrip\Core\Application;

final class SecretService
{
    public function __construct(private readonly Application $app) {}

    public function encrypt(string $plaintext): string
    {
        if ($plaintext === '') return '';
        $key = $this->key();
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($ciphertext === false) throw new \RuntimeException('Unable to encrypt email credential.');
        return base64_encode($iv . $tag . $ciphertext);
    }

    public function decrypt(?string $encoded): string
    {
        $encoded = trim((string)$encoded);
        if ($encoded === '') return '';
        $raw = base64_decode($encoded, true);
        if ($raw === false || strlen($raw) < 29) throw new \RuntimeException('Stored email credential is invalid.');
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $ciphertext = substr($raw, 28);
        $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $this->key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($plaintext === false) throw new \RuntimeException('Unable to decrypt stored email credential.');
        return $plaintext;
    }

    private function key(): string
    {
        $dir = $this->app->rootPath('storage/keys');
        $path = $dir . '/mail.key';
        if (!is_dir($dir) && !mkdir($dir, 0770, true) && !is_dir($dir)) {
            throw new \RuntimeException('Unable to create storage/keys for email credential encryption.');
        }
        if (!is_file($path)) {
            $key = random_bytes(32);
            if (file_put_contents($path, base64_encode($key), LOCK_EX) === false) {
                throw new \RuntimeException('Unable to create email credential encryption key.');
            }
            @chmod($path, 0600);
            return $key;
        }
        $encoded = trim((string)file_get_contents($path));
        $key = base64_decode($encoded, true);
        if ($key === false || strlen($key) !== 32) throw new \RuntimeException('Email credential encryption key is invalid.');
        return $key;
    }
}
