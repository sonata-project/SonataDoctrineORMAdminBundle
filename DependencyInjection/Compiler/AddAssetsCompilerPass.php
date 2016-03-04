<?php

namespace Sonata\DoctrineORMAdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author ju1ius
 */
class AddAssetsCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $pool = $container->getDefinition('sonata.admin.pool');
        $options = $pool->getArgument(3);
        $options = array_merge_recursive($options, [
            'javascripts' => [
                '/bundles/sonatadoctrineormadmin/spinner.js',
                '/bundles/sonatadoctrineormadmin/edit-associations.js'
            ],
            'stylesheets' => [
                '/bundles/sonatadoctrineormadmin/styles.css'
            ]
        ]);
        $pool->replaceArgument(3, $options);
    }
}