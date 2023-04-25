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
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Mother;
use Sonata\Form\Type\CollectionType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @phpstan-extends AbstractAdmin<Mother>
 */
final class MotherAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list->addIdentifier('id');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form->add('children', CollectionType::class, [
            'by_reference' => false,
            'constraints' => [
                new Assert\Valid(),
            ],
        ], [
            'edit' => 'inline',
            'inline' => 'table',
        ]);
    }
}
