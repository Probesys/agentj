<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CryptEncryptService
{
    public function __construct(private ParameterBagInterface $params)
    {
    }

    /**
     * Encrypt data and return it as a base64-encoded token.
     *
     * The lifetime (in seconds) of the token can be changed. It defaults to 7
     * days by default.
     *
     * @throws \Exception if the encryption fails
     */
    public function encrypt(string $data, int $lifetime = 7 * 24 * 3600): string
    {
        $key = $this->params->get('salt');
        $iv = $this->params->get('iv');

        $expiry = time() + $lifetime;

        $expiryStr = (string)$expiry;

        // Build payload: expiry and data separated by a colon
        $payload = 'v2:' . $expiryStr . ':' . $data;

        $ciphertext = openssl_encrypt($payload, 'aes-256-cbc', $key, 0, $iv);

        if ($ciphertext === false) {
            throw new \Exception('Encryption failed.');
        }

        // Return the Base64 encoded ciphertext
        return base64_encode($ciphertext);
    }

    /**
     * Decrypts the given token and returns an array with the first element as
     * the lifetime timestamp and the second element as the decrypted data.
     *
     * @return array{int, string|false}
     * @throws \Exception
     */
    public function decrypt(string $token): array
    {
        $key = $this->params->get('salt');
        $iv = $this->params->get('iv');

        $ciphertext = base64_decode($token);

        $payload = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, 0, $iv);

        if ($payload === false) {
            // Handle the old (deprecated) version of the token. In this
            // version, expiration date was concatanated to the ciphertext in
            // the base64 string.
            $expiry = (int) substr(base64_decode($token), 0, 10);
            $ciphertext = substr_replace(base64_decode($token), '', 0, 10);
            $data = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, 0, $iv);
            return [$expiry, $data];
        }

        if (strpos($payload, 'v2:') === 0) {
            $withoutVersion = substr($payload, 3);
            $parts = explode(':', $withoutVersion, 2);

            if (count($parts) !== 2) {
                throw new \Exception("Invalid payload format for v2 token.");
            }

            $expiry = (int)$parts[0];
            $data = $parts[1];
        } else {
            throw new \Exception('Decryption failed.');
        }

        return [$expiry, $data];
    }
}
