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
class Item
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Command")
     */
    private Command $command;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Product")
     */
    private Product $product;

    /**
     * @ORM\Column(type="decimal")
     */
    private string $offeredPrice;

    public function __construct(Command $command, Product $product)
    {
        $this->command = $command;
        $this->product = $product;
        $this->offeredPrice = $product->getCurrentPrice();
    }

    public function getCommand(): Command
    {
        return $this->command;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getOfferedPrice(): string
    {
        return $this->offeredPrice;
    }
}
