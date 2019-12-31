<?php
namespace App\Security\Authentication;

use App\Security\EncryptionService;
use Symfony\Component\HttpFoundation\Session\Storage\Proxy\SessionHandlerProxy;

class EncryptedSession extends SessionHandlerProxy
{

    private $encryptionService;

    public function __construct(EncryptedSessionHandler $handler, EncryptionService $encryptionService)
    {
        parent::__construct($handler);
        $this->encryptionService = $encryptionService;
    }

    public function read($id)
    {
        $data = parent::read($id);
        if ( empty($data) ) {
            return '';
        }

        $key = $this->getEncryptionKey();
        if ( !$key ) {
            return '';
        }

        $data = $this->encryptionService->decrypt_sodium($key, hex2bin($data));
        $data = hex2bin($data);

        return $data;
    }

    public function write($id, $data)
    {
        if ( empty($data) ) {
            return true;
        }

        $key = $this->getEncryptionKey();
        if ( !$key ) {
            return false;
        }

        $data = bin2hex($data);
        $data = $this->encryptionService->encrypt_sodium($key, $data);

        return parent::write($id, bin2hex($data));
    }

    private function getEncryptionKey() {
        if ( !isset($_COOKIE[EncryptedSessionHandler::$ENCRYPTION_KEY]) || empty($_COOKIE[EncryptedSessionHandler::$ENCRYPTION_KEY]) ) {
            return null;
        }
        return hex2bin($_COOKIE[EncryptedSessionHandler::$ENCRYPTION_KEY]);
    }

}