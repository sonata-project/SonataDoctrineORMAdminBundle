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
class Car
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    private string $name;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private int $year;

    public function __construct(string $name = '', int $year = 0)
    {
        $this->name = $name;
        $this->year = $year;
    }

    public function __toString(): string
    {
        return $this->name.' - '.$this->year;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): void
    {
        $this->year = $year;
    }
}
