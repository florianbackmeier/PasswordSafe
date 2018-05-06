<?php
namespace App\Entity;

class DatabaseTextRow extends DatabaseRow
{
    public function getType()
    {
        return 'Text';
    }
}
