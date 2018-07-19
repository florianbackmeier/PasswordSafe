<?php
namespace App\Security;

class EncryptionService
{
    const DB_PATH = 'databases/';

    public function generateSalt()
    {
        return substr(base64_encode(random_bytes(64)), 0, -2) . uniqid();
    }

    public function generateKey($password, $salt, $iterations)
    {
        $hash = hash_pbkdf2("sha256", $password, $salt, $iterations);
        $key = pack('H*', $hash);
        return $key;
    }

    public function encrypt($key, $db, $data) {
        $ciphertext = $this->encrypt_sodium($key, $data);
        $db->setData($ciphertext);
    }
    public function decrypt($key, $db) {
        $data = $db->getData();
        if ( substr( $data, 0, 2 ) == '2:' ) {
            return $this->decrypt_sodium($key, $data);
        }
        return null;
    }

    // V2
    // XSalsa20 with Sodium
    public function encrypt_sodium($key, $data) {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($this->prepareData($data), $nonce, $key);

        return '2:'.bin2hex($nonce).':'.$ciphertext;
    }

    // V2
    // XSalsa20 with Sodium
    public function decrypt_sodium($key, $data) {
        $data = substr($data, 2); // remove version number
        $posNonceDelimiter = strpos($data, ':');
        $nonce = hex2bin(substr($data, 0, $posNonceDelimiter));
        $ciphertext = substr($data, $posNonceDelimiter+1);

        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
        return $this->extractData($plaintext);
    }

    private function prepareData($data)
    {
        $data = bin2hex(json_encode($data));
        return md5($data) . '::::' . $data;
    }

    private function extractData($data)
    {
        $md5 = substr($data, 0, 32);
        $data = trim(substr($data, 36));
        if (md5($data) == $md5) {
            return json_decode(hex2bin($data));
        }
        return null;
    }

    public function isValidKey($key, $db)
    {
        if ($this->decrypt($key, $db) !== null) {
            return true;
        }
        return false;
    }

    private function saveDB($name, $db)
    {
        file_put_contents(self::DB_PATH . $name, gzencode(json_encode($db)));
    }

    private function openDB($name)
    {
        if (!file_exists(self::DB_PATH . $name)) {
            return null;
        }
        $db = new SafeDatabase();
        $obj = json_decode(gzdecode(file_get_contents(self::DB_PATH . $name)));
        $db->deSerialize($obj);
        return $db;
    }
}
