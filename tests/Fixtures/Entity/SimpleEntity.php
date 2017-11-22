<?php

namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity;

class SimpleEntity
{
    private $schmeckles;
    private $multiWordProperty;

    public function getSchmeckles()
    {
        return $this->schmeckles;
    }

    public function setSchmeckles($value)
    {
        $this->schmeckles = $value;
    }

    public function getMultiWordProperty()
    {
        return $this->multiWordProperty;
    }

    public function setMultiWordProperty($value)
    {
        $this->multiWordProperty = $value;
    }
}
