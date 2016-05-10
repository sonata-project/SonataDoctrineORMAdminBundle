<?php

namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity;

use rhumsaa\Uuid\Uuid;

class UuidEntity
{
    private $uuid;

    public function __construct()
    {
        $this->uuid = Uuid::uuid4();
    }

    public function getId()
    {
        return $this->uuid;
    }
}
