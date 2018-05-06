<?php
namespace App\Entity;

use Symfony\Component\HttpFoundation\Request;

class DatabaseUsernamePasswordRow extends DatabaseRow
{
    private $username;
    private $password;
    private $note;

    public function __construct($id = null, $name = '', $attributes = array(), $value = array())
    {
        parent::__construct($id, $name, $attributes, $value);
        if (is_array($value) && (count($value) == 2 || count($value) == 3)) {
            $this->setUsername($value[0]);
            $this->setPassword($value[1]);
            if (count($value) == 3) {
                $this->setNote($value[2]);
            }
        }
    }

    public function getType()
    {
        return 'UsernamePassword';
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getNote()
    {
        return $this->note;
    }

    public function setNote($note)
    {
        $this->note = $note;
    }

    public function save(Request $request)
    {
        parent::save($request);

        $username = $request->request->get('username');
        $password = $request->request->get('password');
        $note = $request->request->get('note');

        $this->setUsername($username);
        $this->setPassword($password);
        $this->setNote($note);
    }

    public function jsonSerialize()
    {
        return array_merge(parent::jsonSerialize(), array('value' => array($this->getUsername(), $this->getPassword(), $this->getNote())));
    }
}
