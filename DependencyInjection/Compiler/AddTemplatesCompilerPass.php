<?php

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
use Symfony\Component\DependencyInjection\Definition;

/**
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class AddTemplatesCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $overwrite = $container->getParameter('sonata.admin.configuration.admin_services');
        $templates = $container->getParameter('sonata_doctrine_orm_admin.templates');

        foreach ($container->findTaggedServiceIds('sonata.admin') as $id => $attributes) {
            if (!isset($attributes[0]['manager_type']) || $attributes[0]['manager_type'] != 'orm') {
                continue;
            }

            $definition = $container->getDefinition($id);

            if (!$definition->hasMethodCall('setFormTheme')) {
                $definition->addMethodCall('setFormTheme', array($templates['form']));
            }

            if (isset($overwrite[$id]['templates']['form'])) {
                $this->mergeMethodCall($definition, 'setFormTheme', $overwrite[$id]['templates']['form']);
            }

            if (!$definition->hasMethodCall('setFilterTheme')) {
                $definition->addMethodCall('setFilterTheme', array($templates['filter']));
            }

            if (isset($overwrite[$id]['templates']['filter'])) {
                $this->mergeMethodCall($definition, 'setFilterTheme', $overwrite[$id]['templates']['filter']);
            }
        }
    }

    /**
     * @param Definition $definition
     * @param string     $name
     * @param mixed      $value
     */
    public function mergeMethodCall(Definition $definition, $name, $value)
    {
        $methodCalls = $definition->getMethodCalls();

        foreach ($methodCalls as &$calls) {
            foreach ($calls as &$call) {
                if (is_string($call)) {
                    if ($call !== $name) {
                        continue 2;
                    }

                    continue 1;
                }

                $call = array(array_merge($call[0], $value));
            }
        }

        $definition->setMethodCalls($methodCalls);
    }
}
