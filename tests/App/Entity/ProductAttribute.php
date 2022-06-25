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

namespace Sonata\DoctrineORMAdminBundle\Tests\App\Entity;

use Doctrine\ORM\Mapping as ORM;

/** @ORM\Entity */
class ProductAttribute
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Product")
     */
    private Product $product;

    /**
     * @ORM\Column(type="string")
     */
    private string $name;

    public function __construct(Product $product, string $name)
    {
        $this->product = $product;
        $this->name = $name;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
