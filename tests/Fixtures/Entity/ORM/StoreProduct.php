<?php

namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\ORM;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class StoreProduct
{
    /**
     * @ORM\ManyToOne(targetEntity="Store", inversedBy="stores")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @ORM\Id()
     * @ORM\GeneratedValue("NONE")
     */
    protected $store;

    /**
     * @ORM\ManyToOne(targetEntity="Product", inversedBy="products")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @ORM\Id()
     * @ORM\GeneratedValue("NONE")
     */
    protected $product;

    /**
     * @ORM\Column(type="string")
     */
    protected $name;
}
