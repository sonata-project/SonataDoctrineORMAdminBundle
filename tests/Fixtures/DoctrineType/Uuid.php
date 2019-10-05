<?php

namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType;

class Uuid
{
    private $uuid;

    public function __construct($uuid)
    {
        $this->uuid = $uuid;
    }

    public function getValue()
    {
        return (string) $this;
    }

    public function __toString()
    {
        return $this->uuid;
    }
}
