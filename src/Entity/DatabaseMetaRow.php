<?php
namespace App\Entity;

class DatabaseMetaRow extends DatabaseRow
{

    private $map;

    public function __construct($id = null, $name = '', $attributes = array(), $value = array())
    {
        parent::__construct($id, $name, $attributes, $value);
        $this->map = json_decode(json_encode($value), true);
    }

    public function getType()
    {
        return 'Meta';
    }

    public function getMap()
    {
        return $this->map;
    }

    public function add($key, $value)
    {
        $this->map[$key] = $value;
    }

    public function get($key)
    {
        if (array_key_exists($key, $this->map)) {
            return $this->map[$key];
        }
        return '';
    }

    public function jsonSerialize()
    {
        return array_merge(parent::jsonSerialize(), array('value' => $this->getMap()));
    }
}
