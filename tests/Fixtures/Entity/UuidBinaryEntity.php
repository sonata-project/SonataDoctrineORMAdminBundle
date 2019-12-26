<?php

namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity;

final class UuidBinaryEntity
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
