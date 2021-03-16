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

namespace Sonata\DoctrineORMAdminBundle\Tests\App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Book;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Reader;

final class BookFixtures extends Fixture implements DependentFixtureInterface
{
    public const BOOK = 'book';

    public function load(ObjectManager $manager): void
    {
        $book = new Book('book_id', 'Don Quixote', $this->getReference(AuthorFixtures::AUTHOR));
        $book->addCategory($this->getReference(CategoryFixtures::CATEGORY));

        $manager->persist($book);

        $book1 = new Book('book_1', 'Book 1', $this->getReference(AuthorFixtures::AUTHOR_WITH_TWO_BOOKS));
        $book1->addCategory($this->getReference(CategoryFixtures::CATEGORY));

        $this->addReaders($book1, 100);

        $book2 = new Book('book_2', 'Book 2', $this->getReference(AuthorFixtures::AUTHOR_WITH_TWO_BOOKS));
        $book2->addCategory($this->getReference(CategoryFixtures::CATEGORY));

        $this->addReaders($book2, 100);

        $manager->persist($book1);
        $manager->persist($book2);
        $manager->flush();

        $this->addReference(self::BOOK, $book);
    }

    /**
     * @phpstan-return class-string[]
     */
    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
            AuthorFixtures::class,
        ];
    }

    private function addReaders(Book $book, int $readers): void
    {
        for ($i = 0; $i < $readers; ++$i) {
            $book->addReader(new Reader());
        }
    }
}
