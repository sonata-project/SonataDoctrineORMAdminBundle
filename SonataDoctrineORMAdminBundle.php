<?php
/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Sonata\DoctrineORMAdminBundle\DependencyInjection\Compiler\AddGuesserCompilerPass;
use Sonata\DoctrineORMAdminBundle\DependencyInjection\Compiler\AddTemplatesCompilerPass;
use Sonata\DoctrineORMAdminBundle\DependencyInjection\Compiler\AddAuditEntityCompilerPass;

class SonataDoctrineORMAdminBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AddGuesserCompilerPass());
        $container->addCompilerPass(new AddTemplatesCompilerPass());
        $container->addCompilerPass(new AddAuditEntityCompilerPass());
    }
}
