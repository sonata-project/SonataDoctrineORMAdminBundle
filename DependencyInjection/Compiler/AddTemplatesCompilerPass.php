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
use Symfony\Component\DependencyInjection\ContainerInterface;

/*
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class AddTemplatesCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $settings = $this->fixSettings($container);
        foreach ($container->findTaggedServiceIds('sonata.admin') as $id => $attributes) {

            if (!isset($attributes[0]['manager_type']) || $attributes[0]['manager_type'] != 'orm') {
                continue;
            }


            $definition = $container->getDefinition($id);

            if (!$definition->hasMethodCall('setFormTheme')) {
                $definition->addMethodCall('setFormTheme', array($settings['templates']['form']));
            }

            if (!$definition->hasMethodCall('setFilterTheme')) {
                $definition->addMethodCall('setFilterTheme', array($settings['templates']['filter']));
            }
        }
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @return bool
     */
    public function fixSettings(ContainerBuilder $container)
    {
        $pool = $container->getDefinition('sonata.admin.manager.orm');

        // not very clean but don't know how to do that for now
        $settings = false;
        $methods  = $pool->getMethodCalls();
        foreach ($methods as $pos => $calls) {
            if ($calls[0] == '__hack_doctrine_orm__') {
                $settings = $calls[1];
                break;
            }
        }

        if ($settings) {
            unset($methods[$pos]);
        }

        $pool->setMethodCalls($methods);

        return $settings;
    }
}