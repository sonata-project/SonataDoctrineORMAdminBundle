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

/**
 * @internal
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
final class AddAuditEntityCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('simplethings_entityaudit.config')) {
            return;
        }

        $auditedEntities = $container->getParameter('simplethings.entityaudit.audited_entities');
        \assert(\is_array($auditedEntities));
        $force = $container->getParameter('sonata_doctrine_orm_admin.audit.force');
        \assert(\is_bool($force));

        foreach ($container->findTaggedServiceIds('sonata.admin') as $id => $attributes) {
            if ('orm' !== $attributes[0]['manager_type']) {
                continue;
            }

            if (true === $force && isset($attributes[0]['audit']) && false === $attributes[0]['audit']) {
                continue;
            }

            if (false === $force && (!isset($attributes[0]['audit']) || false === $attributes[0]['audit'])) {
                continue;
            }

            $definition = $container->getDefinition($id);
            $auditedEntities[] = $this->getModelName($container, $definition->getArgument(1));
        }

        $auditedEntities = array_unique($auditedEntities);

        $container->setParameter('simplethings.entityaudit.audited_entities', $auditedEntities);

        $auditManager = $container->getDefinition('sonata.admin.audit.manager');
        $auditManager->addMethodCall('setReader', ['sonata.admin.audit.orm.reader', $auditedEntities]);
    }

    private function getModelName(ContainerBuilder $container, string $name): string
    {
        if ('%' === $name[0]) {
            $parameter = $container->getParameter(substr($name, 1, -1));
            if (!\is_string($parameter)) {
                throw new \InvalidArgumentException(sprintf('Cannot find the model name "%s"', $name));
            }

            return $parameter;
        }

        return $name;
    }
}
