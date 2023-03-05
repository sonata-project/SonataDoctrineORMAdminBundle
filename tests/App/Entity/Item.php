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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** @ORM\Entity */
#[ORM\Entity]
class Item
{
    /**
     * @ORM\Column(type="decimal")
     */
    #[ORM\Column(type: Types::DECIMAL)]
    private string $offeredPrice;

    public function __construct(
        /**
         * @ORM\Id
         * @ORM\ManyToOne(targetEntity="Command")
         */
        #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: Command::class)]
        private Command $command,
        /**
         * @ORM\Id
         * @ORM\ManyToOne(targetEntity="Product")
         */
        #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: Product::class)]
        private Product $product
    ) {
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
