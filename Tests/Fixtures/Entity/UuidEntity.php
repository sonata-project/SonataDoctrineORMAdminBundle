<?php

namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity;

class UuidEntity
{
    private $uuid;

    public function __construct($uuid)
    {
        $this->uuid = $uuid;
    }

    public function getId()
    {
        return $this->uuid;
    }
}
