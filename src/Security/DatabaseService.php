<?php
namespace App\Security;

use App\Entity\DatabaseMetaRow;
use App\Security\Authentication\UsernameKeyToken;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class DatabaseService
{

    private $encryptionService;
    private $entityManager;
    private $logger;
    private $rounds;

    public function __construct(EncryptionService $encryptionService, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->encryptionService = $encryptionService;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->rounds = 80000;
    }

    public function saveDatabaseRow(UsernameKeyToken $token, Array $rows)
    {
        $meta = $this->getMeta($token);
        $rows[] = $meta;
        $this->rowsResult = null;
        $this->encryptionService->encrypt($token->getCredentials(), $token->getUser()->getSafeDatabase(), $rows);

        $this->entityManager->persist($token->getUser()->getSafeDatabase());
        $this->entityManager->flush();
    }

    public function saveMetaRow($token, $meta)
    {
        $rows = $this->getDatabaseRows($token);
        $found = false;
        for ($i = 0; $i < count($rows); $i++) {
            if ($rows[$i]->getType() == 'Meta') {
                $rows[$i] == $meta;
                $found = true;
            }
        }
        if (!$found) {
            $rows[] = $meta;
        }

        $this->saveDatabaseRow($token, $rows);
    }

    public function getDatabaseRows(UsernameKeyToken $token, $category = null)
    {
        $rows = $this->encryptionService->decrypt($token->getCredentials(), $token->getUser()->getSafeDatabase());

        $result = array();
        if ($rows == null) {
            return $result;
        }
        foreach ($rows as $i => $row) {
            if ($row->type == 'Meta') {
                continue;
            }
            $class = 'App\Entity\Database' . $row->type . 'Row';
            if (class_exists($class)) {
                $entry = new $class($row->id, $row->name, $row->attributes, $row->value);
                if ($category === null || $entry->getCategory() == $category) {
                    $result[] = $entry;
                }
            } else {
                $this->logger->error('Database could not be restored completly. ' . $row->type . ' is not available.');
            }
        }

        usort($result, array($this, "sortAZ"));
        return $result;
    }

    public function getMeta(UsernameKeyToken $token)
    {
        $rows = $this->encryptionService->decrypt($token->getCredentials(), $token->getUser()->getSafeDatabase());

        if ($rows == null) {
            return new DatabaseMetaRow();
        }
        foreach ($rows as $i => $row) {
            if ($row->type == 'Meta') {
                return new DatabaseMetaRow($row->id, $row->name, $row->attributes, $row->value);
            }
        }
        return new DatabaseMetaRow();
    }

    function sortAZ($a, $b)
    {
        return strcmp(strtolower($a->getName()), strtolower($b->getName()));
    }

    public function getCategories($token)
    {
        $rows = $this->getDatabaseRows($token);

        $categories = array();
        foreach ($rows as $row) {
            $categories[] = $row->getCategory();
        }
        $categories = array_unique($categories);
        return array_filter($categories);
    }

    public function createInitialDatabase($user)
    {
        if ($user->getSafeDatabase() != null) {
            return false;
        }

        $password = $this->generatePassword(20);

        $salt = $this->encryptionService->generateSalt();
        $key = $this->encryptionService->generateKey($password, $salt, $this->rounds);

        $db = new SafeDatabase();
        $db->setSalt($salt);
        $db->setKeyIterations($this->rounds);
        $this->encryptionService->encrypt($key, $db, array());

        $user->setSafeDatabase($db);
        $this->entityManager->persist($db);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $password;
    }

    private function generatePassword($length = 8)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $count = mb_strlen($chars);

        for ($i = 0, $result = ''; $i < $length; $i++) {
            $index = rand(0, $count - 1);
            $result .= mb_substr($chars, $index, 1);
        }

        return $result;
    }

}
