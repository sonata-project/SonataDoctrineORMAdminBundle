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

use Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass;
use Sonata\DoctrineORMAdminBundle\Tests\App\Admin\AuthorAdmin;
use Sonata\DoctrineORMAdminBundle\Tests\App\Admin\BookAdmin;
use Sonata\DoctrineORMAdminBundle\Tests\App\Admin\CategoryAdmin;
use Sonata\DoctrineORMAdminBundle\Tests\App\DataFixtures\AppFixtures;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Author;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Book;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Category;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()

        ->set(CategoryAdmin::class)
            ->public()
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
            ->public()
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
            ->public()
            ->tag('sonata.admin', [
                'manager_type' => 'orm',
                'label' => 'Author',
            ])
            ->args([
                '',
                Author::class,
                null,
            ])

        ->set(AppFixtures::class)
            ->tag(FixturesCompilerPass::FIXTURE_TAG)
    ;
};
