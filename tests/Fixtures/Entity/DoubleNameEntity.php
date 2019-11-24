<?php

declare(strict_types=1);

namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;

/** @Entity */
final class DoubleNameEntity
{
    /** @Id @Column(type="integer") */
    protected $id;

    /** @Column(type="string") */
    public $name;

    /** @Column(type="string", nullable=true) */
    public $name2;

    public function __construct($id, $name, $name2)
    {
        $this->id = $id;
        $this->name = $name;
        $this->name2 = $name2;
    }
}
