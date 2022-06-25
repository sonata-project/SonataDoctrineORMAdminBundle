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

/** @ORM\Embeddable() */
class Address
{
    /**
     * @ORM\Column(type="string")
     */
    private string $street;

    public function __construct(string $street = '')
    {
        $this->street = $street;
    }

    public function __toString(): string
    {
        return $this->street;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreet(string $street): void
    {
        $this->street = $street;
    }
}
