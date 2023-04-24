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
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Car;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\UuidEntity;
use Symfony\Component\Uid\Uuid;

/**
 * @phpstan-extends AbstractAdmin<UuidEntity>
 */
final class UuidEntityAdmin extends AbstractAdmin
{
    protected function createNewInstance(): UuidEntity
    {
        return new UuidEntity(Uuid::v4());
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('name')
            ->add('child', null, [
                'field_options' => ['multiple' => true],
            ])
            ->add('car', null, [
                'field_options' => ['choice_value' => static fn (?Car $choice): ?string => null !== $choice ? $choice->getName().'~'.$choice->getYear() : null,
                ],
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('name')
            ->add('child')
            ->add('car');
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('name')
            ->add('child')
            ->add('car');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('name')
            ->add('child')
            ->add('car');
    }
}
