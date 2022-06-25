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
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Sub;

/**
 * @phpstan-extends AbstractAdmin<Sub>
 */
final class SubAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list->addIdentifier('id');
        $list->add('otherField');
    }
}
