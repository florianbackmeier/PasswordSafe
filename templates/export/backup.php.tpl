<?php
if (PHP_OS == 'WINNT') {
    echo 'Your password: ';
    $password = stream_get_line(STDIN, 1024, PHP_EOL);
} else {
    $password = readline('Your password:  ');
}

$db = new Database();

$service = new EncryptionService();
$key = $service->generateKey($password, $db->getSalt(), $db->getIterations());
$db = $service->decrypt($key, $db);

var_dump($db);

class Database {
    private $salt = '${salt}';
    private $data = '${data}';
    private $iterations = ${iterations};

    public function getData() {
        return hex2bin($this->data);
    }
    public function getSalt() {
        return hex2bin($this->salt);
    }
    public function getIterations() {
        return $this->iterations;
    }
}

${service}
