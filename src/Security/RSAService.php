<?php
namespace App\Security;

use App\Entity\SharedPassword;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\DatabaseRow;
use App\Entity\User;
use phpseclib\Crypt\RSA;

class RSAService
{
    private $entity_manager;
    private $databaseService;

    public function __construct(EntityManagerInterface $entity_manager, DatabaseService $databaseService)
    {
        $this->entity_manager = $entity_manager;
        $this->databaseService = $databaseService;

    }

    public function generateKeys($token)
    {
        $rsa = new RSA();
        $rsa->setPrivateKeyFormat(RSA::PRIVATE_FORMAT_PKCS8);
        $rsa->setPublicKeyFormat(RSA::PUBLIC_FORMAT_PKCS8);
        $keys = $rsa->createKey(4096);

        $items = $this->entity_manager->getRepository(SharedPassword::class)->findByReceiver($token->getUser());
        $rsaOld = new RSA();
        $rsaOld->loadKey($this->databaseService->getMeta($token)->get('privateKey'));

        $meta = $this->databaseService->getMeta($token);
        $meta->add('privateKey', $keys['privatekey']);
        $meta->add('publicKey', $keys['publickey']);
        $this->databaseService->saveMetaRow($token, $meta);
        $token->getUser()->setPublicKey($keys['publickey']);
        $this->entity_manager->persist($token->getUser());
        $this->entity_manager->flush();

        $rsa->loadKey($token->getUser()->getPublicKey());
        foreach ($items as $item) {
            $decrypted = $rsaOld->decrypt($item->getEncryptedData());
            $item->setEncryptedData($rsa->encrypt($decrypted));
            $this->entity_manager->persist($item);
        }
        $this->entity_manager->flush();
    }

    public function getSharedItems($token)
    {
        $items = $this->entity_manager->getRepository(SharedPassword::class)->findByReceiver($token->getUser());

        $rsa = new RSA();
        $rsa->loadKey($this->databaseService->getMeta($token)->get('privateKey'));
        $result = array();
        foreach ($items as $item) {
            $entry = $this->decrypt($item, $rsa);
            if ($entry != null) {
                $result[] = $entry;
            }
        }

        return $result;
    }

    public function getSharedItem($id, $token)
    {
        $rsa = new RSA();
        $rsa->loadKey($this->databaseService->getMeta($token)->get('privateKey'));


        $item = $this->entity_manager->getRepository('PasswordSafeBundle:SharedPassword')->find($id);
        if ($item->getReceiver() != $token->getUser()) {
            return;
        }

        return $this->decrypt($item, $rsa);
    }

    public function decrypt($item, $rsa)
    {
        $row = json_decode($rsa->decrypt($item->getEncryptedData()));
        if ($row == null) {
            return null;
        }
        $class = 'App\Entity\Database' . $row->type . 'Row';
        if (class_exists($class)) {
            $entry = new $class($row->id, $row->name, $row->attributes, $row->value);

            $entry->setSharedItem($item);

            return $entry;
        }
        return null;
    }

    public function encrypt(DatabaseRow $row, User $receiver)
    {
        $rsa = new RSA();
        $rsa->loadKey($receiver->getPublicKey());
        $rsa->setEncryptionMode(RSA::ENCRYPTION_OAEP);

        $sharedRow = clone $row;
        $sharedRow->setAttributes(array());

        return $rsa->encrypt(json_encode($sharedRow));
    }
}
