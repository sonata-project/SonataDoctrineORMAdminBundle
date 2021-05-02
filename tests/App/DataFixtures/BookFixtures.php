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
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Author;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Book;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Category;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Reader;

final class BookFixtures extends Fixture implements DependentFixtureInterface
{
    public const BOOK = 'book';

    public function load(ObjectManager $manager): void
    {
        $author = $this->getReference(AuthorFixtures::AUTHOR);
        \assert($author instanceof Author);
        $category = $this->getReference(CategoryFixtures::CATEGORY);
        \assert($category instanceof Category);

        $book = new Book('book_id', 'Don Quixote', $author);
        $book->addCategory($category);

        $manager->persist($book);

        $authorWithTwoBooks = $this->getReference(AuthorFixtures::AUTHOR_WITH_TWO_BOOKS);
        \assert($authorWithTwoBooks instanceof Author);

        $book1 = new Book('book_1', 'Book 1', $authorWithTwoBooks);
        $book1->addCategory($category);

        $this->addReaders($book1, 100);

        $book2 = new Book('book_2', 'Book 2', $authorWithTwoBooks);
        $book2->addCategory($category);

        $this->addReaders($book2, 100);

        $manager->persist($book1);
        $manager->persist($book2);
        $manager->flush();

        $this->addReference(self::BOOK, $book);
    }

    /**
     * @phpstan-return array<class-string<\Doctrine\Common\DataFixtures\FixtureInterface>>
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
