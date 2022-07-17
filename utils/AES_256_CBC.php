<?php
namespace utils;

/**
 * A static class providing the basic methods for AES-256-CBC encrypt&decrypt for plain texts.
 */
class AES_256_CBC{
    /**
     * This is a static class. It cannot be constructed.
     */
    private function __construct() {}

    /**
     * Encrypt a string text with a corresponding password text.
     * @param $plaintext string The text to be encrypted.
     * @param $password string The password text.
     * @param $returnBinary bool Whether should the function return a raw binary data. If set to false, it will return hex.
     * @return string The binary string
     */
    static function Encrypt(string $plaintext, string $password, bool $returnBinary = true): string{
        $method = "AES-256-CBC";
        $key = hash('sha256', $password, true);
        $iv = openssl_random_pseudo_bytes(16);
        $ciphertext = openssl_encrypt($plaintext, $method, $key, OPENSSL_RAW_DATA, $iv);
        $hash = hash_hmac('sha256', $ciphertext . $iv, $key, true);
        $result = $iv . $hash . $ciphertext;
        if(!$returnBinary) $result = bin2hex($result);
        return $result;
    }

    /**
     * Decrypt a string text with the corresponding password text.
     * @param string $ivHashCiphertext The raw binary text to be decrypted.
     * @param string $password The password text.
     * @return string|null Returns the decrypted string on success; Otherwise, returns null.
     */
    static function Decrypt(string $ivHashCiphertext, string $password): string|null{
        $method = "AES-256-CBC";
        $iv = substr($ivHashCiphertext, 0, 16);
        $hash = substr($ivHashCiphertext, 16, 32);
        $ciphertext = substr($ivHashCiphertext, 48);
        $key = hash('sha256', $password, true);
        if (!hash_equals(hash_hmac('sha256', $ciphertext . $iv, $key, true), $hash)) return null;
        return openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $iv);
    }
}
