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

namespace Sonata\DoctrineORMAdminBundle\Tests\App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface as ORMProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Author;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * @phpstan-extends AbstractAdmin<Author>
 */
class AuthorAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('id')
            ->addIdentifier('name')
            ->add('number_of_books', FieldDescriptionInterface::TYPE_INTEGER, [
                'accessor' => static fn (Author $author): int => $author->getBooks()->count(),
                'template' => 'author/list_number_of_books_field.html.twig',
            ])
            ->add('numberOfReaders', FieldDescriptionInterface::TYPE_INTEGER, [
                'template' => 'author/list_number_of_readers_field.html.twig',
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('name')
            ->add('address.street');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('id', TextType::class, [
                'attr' => [
                    'class' => 'author_id',
                ],
                'empty_data' => '',
            ])
            ->add('name', TextType::class, [
                'attr' => [
                    'class' => 'author_name',
                ],
                'empty_data' => '',
            ])
            ->add('address.street', TextType::class, [
                'attr' => [
                    'class' => 'author_address',
                ],
            ]);
    }

    protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
    {
        \assert($query instanceof ORMProxyQueryInterface);

        $alias = $query->getQueryBuilder()->getRootAliases()[0];

        $query
            ->getQueryBuilder()
            ->addSelect('book')
            ->addSelect('reader')
            ->leftJoin(sprintf('%s.books', $alias), 'book')
            ->leftJoin('book.readers', 'reader');

        return $query;
    }
}
