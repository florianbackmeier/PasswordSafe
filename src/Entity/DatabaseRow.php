<?php
namespace App\Entity;

use JsonSerializable;
use Symfony\Component\HttpFoundation\Request;

abstract class DatabaseRow implements JsonSerializable
{
    private $id;
    private $name;
    private $value;
    private $attributes;

    private $sharedItem;


    public function __construct($id = '', $name = '', $attributes = array(), $value = '')
    {
        $this->id = $id;
        $this->setName($name);
        $this->setValue($value);
        $this->setAttributes($attributes);
    }

    abstract public function getType();

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getId()
    {
        if ($this->id == null) {
            $this->id = uniqid();
        }
        return $this->id;
    }

    public function getAttributes()
    {
        if ($this->attributes == null) {
            $this->attributes = array();
        }
        return $this->attributes;
    }

    public function getAttribute($name)
    {
        if ($this->attributes == null) {
            $this->attributes = array();
        }
        return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : null;
    }

    public function setAttributes($attributes)
    {
        if (is_object($attributes)) {
            $attributes = get_object_vars($attributes);
        }
        $this->attributes = $attributes;
    }

    public function updateAttribute($name, $value)
    {
        if ($this->attributes == null) {
            $this->attributes = array();
        }
        $this->attributes[$name] = $value;
    }

    public function getCategory()
    {
        if (array_key_exists('category', $this->getAttributes())) {
            $attr = $this->getAttributes();
            return $attr['category'];
        }
        return '';
    }

    public function save(Request $request)
    {
        $id = $request->request->get('id');
        $name = $request->request->get('name');
        $value = $request->request->get('value');

        $category = $request->request->get('category');
        if ($category == 'newCategory') {
            $category = $request->request->get('category_input');
        }

        $this->setName($name);
        $this->setValue($value);
        $this->updateAttribute('category', $category);
    }

    public function getSharedItem()
    {
        return $this->sharedItem;
    }

    public function setSharedItem($sharedItem)
    {
        $this->sharedItem = $sharedItem;
    }

    public function jsonSerialize()
    {
        return array('type' => $this->getType(), 'id' => $this->getId(), 'name' => $this->getName(), 'value' => $this->getValue(), 'attributes' => $this->getAttributes());
    }
}
