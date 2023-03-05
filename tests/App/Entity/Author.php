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
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** @ORM\Entity */
#[ORM\Entity]
class Author implements \Stringable
{
    /**
     * @ORM\OneToMany(targetEntity=Book::class, mappedBy="author")
     *
     * @var Collection<array-key, Book>
     */
    #[ORM\OneToMany(targetEntity: Book::class, mappedBy: 'author')]
    private Collection $books;

    /**
     * @ORM\Embedded(class="Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Address")
     */
    #[ORM\Embedded(class: Address::class)]
    private Address $address;

    public function __construct(
        /**
         * @ORM\Id
         * @ORM\Column(type="string")
         * @ORM\GeneratedValue(strategy="NONE")
         */
        #[ORM\Id]
        #[ORM\Column(type: Types::STRING)]
        #[ORM\GeneratedValue(strategy: 'NONE')]
        private string $id = '',
        /**
         * @ORM\Column(type="string")
         */
        #[ORM\Column(type: Types::STRING)]
        private string $name = ''
    ) {
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

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): void
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
        return array_sum(
            $this->books->map(
                static fn (Book $book): int => $book->getReaders()->count()
            )->toArray()
        );
    }
}
