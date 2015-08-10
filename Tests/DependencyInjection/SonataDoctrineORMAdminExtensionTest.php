<?php

use Sonata\DoctrineORMAdminBundle\DependencyInjection\SonataDoctrineORMAdminExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SonataDoctrineORMAdminExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerBuilder
     */
    protected $configuration;

    public function testEntityManagerSetFactory()
    {
        $this->configuration = new ContainerBuilder();
        $this->configuration->setParameter('kernel.bundles', array());
        $loader = new SonataDoctrineORMAdminExtension();
        $loader->load(array(), $this->configuration);

        $definition = $this->configuration->getDefinition('sonata.admin.entity_manager');
        $doctrineServiceId = 'doctrine';
        $doctrineFactoryMethod = 'getEntityManager';

        if (method_exists($definition, 'getFactory')) {
            $this->assertEquals(array(new Reference($doctrineServiceId), $doctrineFactoryMethod), $definition->getFactory());
        } else {
            $this->assertEquals($doctrineServiceId, $definition->getFactoryService());
            $this->assertEquals($doctrineFactoryMethod, $definition->getFactoryMethod());
        }
    }

    protected function tearDown()
    {
        unset($this->configuration);
    }
}
