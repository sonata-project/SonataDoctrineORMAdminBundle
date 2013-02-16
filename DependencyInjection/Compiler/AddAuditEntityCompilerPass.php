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

/*
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class AddAuditEntityCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('simplethings_entityaudit.config')) {
            return;
        }

        $auditedEntities = $container->getParameter('simplethings.entityaudit.audited_entities');

        foreach ($container->findTaggedServiceIds('sonata.admin') as $id => $attributes) {

            if ($attributes[0]['manager_type'] != 'orm') {
                continue;
            }

            if (isset($attributes[0]['audit']) && $attributes[0]['audit'] == false) {
                continue;
            }

            $definition = $container->getDefinition($id);
            $auditedEntities[] = $this->getModelName($container, $definition->getArgument(1));
        }

        $auditedEntities = array_unique($auditedEntities);

        $container->setParameter('simplethings.entityaudit.audited_entities', $auditedEntities);
        $container->getDefinition('sonata.admin.audit.manager')->addMethodCall('setReader', array('sonata.admin.audit.orm.reader', $auditedEntities));
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param string                                                  $name
     *
     * @return string
     */
    private function getModelName(ContainerBuilder $container, $name)
    {
        if ($name[0] == '%') {
            return $container->getParameter(substr($name, 1, -1));
        }

        return $name;
    }
}
