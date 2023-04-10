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

use Sonata\AdminBundle\Datagrid\Pager;
use Sonata\DoctrineORMAdminBundle\Tests\App\Admin\AuthorAdmin;
use Sonata\DoctrineORMAdminBundle\Tests\App\Admin\AuthorWithSimplePagerAdmin;
use Sonata\DoctrineORMAdminBundle\Tests\App\Admin\BookAdmin;
use Sonata\DoctrineORMAdminBundle\Tests\App\Admin\BookWithAuthorAutocompleteAdmin;
use Sonata\DoctrineORMAdminBundle\Tests\App\Admin\CarAdmin;
use Sonata\DoctrineORMAdminBundle\Tests\App\Admin\CategoryAdmin;
use Sonata\DoctrineORMAdminBundle\Tests\App\Admin\ChildAdmin;
use Sonata\DoctrineORMAdminBundle\Tests\App\Admin\ItemAdmin;
use Sonata\DoctrineORMAdminBundle\Tests\App\Admin\MotherAdmin;
use Sonata\DoctrineORMAdminBundle\Tests\App\Admin\SubAdmin;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Author;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Book;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Car;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Category;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Child;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Item;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Mother;
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
                'model_class' => Category::class,
                'label' => 'Category',
            ])

        ->set(BookAdmin::class)
            ->tag('sonata.admin', [
                'manager_type' => 'orm',
                'model_class' => Book::class,
                'label' => 'Book',
                'default' => true,
            ])

        ->set(BookWithAuthorAutocompleteAdmin::class)
            ->tag('sonata.admin', [
                'manager_type' => 'orm',
                'model_class' => Book::class,
                'label' => 'Book with Author autocomplete',
            ])

        ->set(AuthorAdmin::class)
            ->tag('sonata.admin', [
                'manager_type' => 'orm',
                'model_class' => Author::class,
                'label' => 'Author',
                'default' => true,
            ])
            ->call('setTemplate', ['outer_list_rows_list', 'author/list_outer_list_rows_list.html.twig'])

        ->set(AuthorWithSimplePagerAdmin::class)
            ->tag('sonata.admin', [
                'manager_type' => 'orm',
                'model_class' => Author::class,
                'label' => 'Author with Simple Pager',
                'pager_type' => Pager::TYPE_SIMPLE,
            ])
            ->call('setTemplate', ['outer_list_rows_list', 'author/list_outer_list_rows_list.html.twig'])

        ->set(CarAdmin::class)
            ->tag('sonata.admin', [
                'manager_type' => 'orm',
                'model_class' => Car::class,
                'label' => 'Car',
            ])

        ->set(ItemAdmin::class)
            ->tag('sonata.admin', [
                'manager_type' => 'orm',
                'model_class' => Item::class,
                'label' => 'Command item',
            ])

        ->set(SubAdmin::class)
            ->tag('sonata.admin', [
                'manager_type' => 'orm',
                'model_class' => Sub::class,
                'label' => 'Inheritance',
            ])

        ->set(MotherAdmin::class)
            ->tag('sonata.admin', [
                'manager_type' => 'orm',
                'model_class' => Mother::class,
                'label' => 'Mother',
            ])

        ->set(ChildAdmin::class)
            ->tag('sonata.admin', [
                'manager_type' => 'orm',
                'model_class' => Child::class,
                'label' => 'Child',
            ]);
};
