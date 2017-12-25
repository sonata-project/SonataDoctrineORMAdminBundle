<?php

namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\ORM;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Store
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="StoreProduct", mappedBy="store")
     */
    private $stores;
}
