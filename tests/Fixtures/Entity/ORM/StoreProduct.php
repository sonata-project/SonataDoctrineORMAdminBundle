<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
