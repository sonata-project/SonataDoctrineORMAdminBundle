<?php


namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity;


use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType\Uuid;

final class UuidBinaryEntity
{
    private $uuid;

    public function __construct(Uuid $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getId(): Uuid
    {
        return $this->uuid;
    }
}
