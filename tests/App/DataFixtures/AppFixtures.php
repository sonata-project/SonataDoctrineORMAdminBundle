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
use Doctrine\Persistence\ObjectManager;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Address;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Author;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Book;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Category;

final class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $dystopianCategory = new Category('category_dystopian', 'Dystopian');
        $novelCategory = new Category('category_novel', 'Novel');

        $author = new Author('Miguel de Cervantes');
        $author->setAddress(new Address('Somewhere in La Mancha, in a place whose name I do not care to remember'));
        $book = new Book('book_id', 'Don Quixote', $author);
        $book->addCategory($novelCategory);

        $manager->persist($author);
        $manager->persist($novelCategory);
        $manager->persist($dystopianCategory);
        $manager->persist($book);
        $manager->flush();
    }
}
