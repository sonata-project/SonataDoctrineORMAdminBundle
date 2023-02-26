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
class Product
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    /**
     * @ORM\Column(type="string")
     */
    #[ORM\Column(type: Types::STRING)]
    private string $name;

    /**
     * @ORM\Column(type="decimal")
     */
    #[ORM\Column(type: Types::DECIMAL)]
    private string $currentPrice;

    public function __construct(int $id, string $name = '', string $currentPrice = '0.0')
    {
        $this->id = $id;
        $this->name = $name;
        $this->currentPrice = $currentPrice;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCurrentPrice(): string
    {
        return $this->currentPrice;
    }

    public function setCurrentPrice(string $currentPrice): void
    {
        $this->currentPrice = $currentPrice;
    }
}
