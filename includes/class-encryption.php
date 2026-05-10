<?php
class MEVP_Encryption {

    /**
     * Encrypt data
     */
    public static function encrypt($data) {
        if (empty($data)) {
            return '';
        }

        $encryption_key = mevp_get_encryption_key();
        $method = 'aes-256-cbc';
        $iv_length = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($iv_length);

        $encrypted = openssl_encrypt(
            $data,
            $method,
            base64_decode($encryption_key),
            OPENSSL_RAW_DATA,
            $iv
        );

        // Combine IV and encrypted data
        $combined = base64_encode($iv . $encrypted);

        return $combined;
    }

    /**
     * Decrypt data
     */
    public static function decrypt($encrypted_data) {
        if (empty($encrypted_data)) {
            return '';
        }

        $encryption_key = mevp_get_encryption_key();
        $method = 'aes-256-cbc';
        $iv_length = openssl_cipher_iv_length($method);

        $decoded = base64_decode($encrypted_data);
        $iv = substr($decoded, 0, $iv_length);
        $encrypted = substr($decoded, $iv_length);

        $decrypted = openssl_decrypt(
            $encrypted,
            $method,
            base64_decode($encryption_key),
            OPENSSL_RAW_DATA,
            $iv
        );

        return $decrypted;
    }
}
?>