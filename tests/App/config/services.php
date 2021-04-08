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

use Sonata\DoctrineORMAdminBundle\Tests\App\Admin\AuthorAdmin;
use Sonata\DoctrineORMAdminBundle\Tests\App\Admin\BookAdmin;
use Sonata\DoctrineORMAdminBundle\Tests\App\Admin\CarAdmin;
use Sonata\DoctrineORMAdminBundle\Tests\App\Admin\CategoryAdmin;
use Sonata\DoctrineORMAdminBundle\Tests\App\Admin\ItemAdmin;
use Sonata\DoctrineORMAdminBundle\Tests\App\Admin\SubAdmin;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Author;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Book;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Car;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Category;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Item;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Sub;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->load('Sonata\\DoctrineORMAdminBundle\\Tests\\App\\DataFixtures\\', dirname(__DIR__).'/DataFixtures')

        ->set(CategoryAdmin::class)
            ->tag('sonata.admin', [
                'manager_type' => 'orm',
                'label' => 'Category',
            ])
            ->args([
                '',
                Category::class,
                null,
            ])

        ->set(BookAdmin::class)
            ->tag('sonata.admin', [
                'manager_type' => 'orm',
                'label' => 'Book',
            ])
            ->args([
                '',
                Book::class,
                null,
            ])

        ->set(AuthorAdmin::class)
            ->tag('sonata.admin', [
                'manager_type' => 'orm',
                'label' => 'Author',
            ])
            ->args([
                '',
                Author::class,
                null,
            ])
            ->call('setTemplate', ['outer_list_rows_list', 'author/list_outer_list_rows_list.html.twig'])

        ->set(CarAdmin::class)
            ->tag('sonata.admin', [
                'manager_type' => 'orm',
                'label' => 'Car',
            ])
            ->args([
                '',
                Car::class,
                null,
            ])

        ->set(ItemAdmin::class)
            ->tag('sonata.admin', [
                'manager_type' => 'orm',
                'label' => 'Command item',
            ])
            ->args([
                '',
                Item::class,
                null,
            ])

        ->set(SubAdmin::class)
            ->tag('sonata.admin', [
                'manager_type' => 'orm',
                'label' => 'Inheritance',
            ])
            ->args([
                '',
                Sub::class,
                null,
            ]);
};
