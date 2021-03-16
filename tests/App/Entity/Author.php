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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/** @ORM\Entity */
class Author
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     * @ORM\GeneratedValue(strategy="NONE")
     *
     * @var string
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity=Book::class, mappedBy="author")
     *
     * @var Collection<array-key, Book>
     */
    private $books;

    /**
     * @ORM\Embedded(class="Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Address")
     *
     * @var Address
     */
    private $address;

    public function __construct(string $id = '', string $name = '')
    {
        $this->id = $id;
        $this->name = $name;
        $this->address = new Address();
        $this->books = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(?Address $address): void
    {
        $this->address = $address;
    }

    /**
     * @return Collection<array-key, Book>
     */
    public function getBooks(): Collection
    {
        return $this->books;
    }

    public function getNumberOfReaders(): int
    {
        return array_sum($this->books->map(static function (Book $book) {
            return $book->getReaders()->count();
        })->toArray());
    }
}
