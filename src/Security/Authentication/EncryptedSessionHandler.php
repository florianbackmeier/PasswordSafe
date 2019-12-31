<?php
namespace App\Security\Authentication;

use App\Security\EncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

class EncryptedSessionHandler extends PdoSessionHandler
{
    public static $ENCRYPTION_KEY = 'PHPSESSKEY';
    private $encryptionService;

    public function __construct(EntityManagerInterface $entityManager, EncryptionService $encryptionService) {
        parent::__construct($entityManager->getConnection()->getWrappedConnection(), ['lock_mode' => PdoSessionHandler::LOCK_ADVISORY]);
        $this->encryptionService = $encryptionService;
    }

    public function open($savePath, $sessionName)
    {
        if ( !isset($_COOKIE[self::$ENCRYPTION_KEY]) ) {
            $key = $this->encryptionService->generateSalt();
            $salt = $this->encryptionService->generateSalt();
            $secret = $this->encryptionService->generateKey($key, $salt, 500);
            setcookie(self::$ENCRYPTION_KEY, bin2hex($secret), 0, '/', '', NULL, true);
        }
        return parent::open($savePath, $sessionName);
    }

}