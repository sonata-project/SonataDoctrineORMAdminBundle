<?php

namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType;

final class ValueObjectWithToStringImpl
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

    public function toString()
    {
        return (string) $this->uuid;
    }
}
