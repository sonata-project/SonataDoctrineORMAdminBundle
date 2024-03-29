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

namespace Sonata\DoctrineORMAdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
final class AddGuesserCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // ListBuilder
        if ($container->hasDefinition('sonata.admin.guesser.orm_list_chain')) {
            $definition = $container->getDefinition('sonata.admin.guesser.orm_list_chain');
            $services = [];
            foreach ($container->findTaggedServiceIds('sonata.admin.guesser.orm_list') as $id => $attributes) {
                $services[] = new Reference($id);
            }

            $definition->replaceArgument(0, $services);
        }

        // DatagridBuilder
        if ($container->hasDefinition('sonata.admin.guesser.orm_datagrid_chain')) {
            $definition = $container->getDefinition('sonata.admin.guesser.orm_datagrid_chain');
            $services = [];
            foreach ($container->findTaggedServiceIds('sonata.admin.guesser.orm_datagrid') as $id => $attributes) {
                $services[] = new Reference($id);
            }

            $definition->replaceArgument(0, $services);
        }

        // ShowBuilder
        if ($container->hasDefinition('sonata.admin.guesser.orm_show_chain')) {
            $definition = $container->getDefinition('sonata.admin.guesser.orm_show_chain');
            $services = [];
            foreach ($container->findTaggedServiceIds('sonata.admin.guesser.orm_show') as $id => $attributes) {
                $services[] = new Reference($id);
            }

            $definition->replaceArgument(0, $services);
        }
    }
}
