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

/**
 * @phpstan-extends AbstractAdmin<\Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Item>
 */
final class ItemAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('command')
            ->add('product')
            ->add('offeredPrice');
    }
}
