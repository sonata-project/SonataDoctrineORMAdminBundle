<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/*
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class AddGuesserCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {

        // ListBuilder
        $definition = $container->getDefinition('sonata.admin.guesser.orm_list_chain');
        $services   = array();
        foreach ($container->findTaggedServiceIds('sonata.admin.guesser.orm_list') as $id => $attributes) {
            $services[] = new Reference($id);
        }

        $definition->replaceArgument(0, $services);

        // DatagridBuilder
        $definition = $container->getDefinition('sonata.admin.guesser.orm_datagrid_chain');
        $services   = array();
        foreach ($container->findTaggedServiceIds('sonata.admin.guesser.orm_datagrid') as $id => $attributes) {
            $services[] = new Reference($id);
        }

        $definition->replaceArgument(0, $services);

        // ShowBuilder
        $definition = $container->getDefinition('sonata.admin.guesser.orm_show_chain');
        $services   = array();
        foreach ($container->findTaggedServiceIds('sonata.admin.guesser.orm_show') as $id => $attributes) {
            $services[] = new Reference($id);
        }

        $definition->replaceArgument(0, $services);
    }
}
