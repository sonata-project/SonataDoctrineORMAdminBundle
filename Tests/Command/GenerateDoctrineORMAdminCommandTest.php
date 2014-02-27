<?php


namespace Sonata\DoctrineORMAdminBundle\Tests\Command;


use Sensio\Bundle\GeneratorBundle\Tests\Command\GenerateCommandTest;
use Symfony\Component\Console\Tester\CommandTester;

class GenerateDoctrineORMAdminCommandTest extends GenerateCommandTest
{
    private $metadataMock;

    public function testCommand()
    {
        $metadata = $this->getMetadataMock();
        $entity = 'Dummy';
        $entity_alias = 'SonataDoctrineORMAdminBundle:'.$entity;

        $generator = $this->getGenerator();
        $generator
          ->expects($this->once())
          ->method('generate')
          ->with($this->getBundle(), $entity, $metadata)
        ;
        $tester = new CommandTester($this->getCommand($generator));
        $tester->execute(array('entity' => $entity_alias));
    }

    protected function getMetadataMock()
    {
        if (null === $this->metadataMock) {
            $this->metadataMock = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataInfo')->disableOriginalConstructor()->getMock();
        }

        return $this->metadataMock;
    }

    protected function getCommand($generator)
    {
        $command = $this->getMockBuilder('Sonata\DoctrineORMAdminBundle\Command\GenerateDoctrineORMAdminCommand')
            ->setMethods(array('getEntityMetadata', 'getApplication'))
            ->getMock()
        ;
        $command->expects($this->once())
            ->method('getEntityMetadata')
            ->with($this->equalTo('Sonata\DoctrineORMAdminBundle\Tests\Command\Entity\Dummy'))
            ->will($this->returnValue(array($this->getMetadataMock())))
        ;

        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');
        $kernel->expects($this->once())
            ->method('getBundle')
            ->with($this->equalTo('SonataDoctrineORMAdminBundle'))
            ->will($this->returnValue($bundle))
        ;
        $app = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Console\Application')
            ->disableOriginalConstructor()
            ->setMethods(array('getKernel', 'getDefinition'))
            ->getMock()
        ;
        $app->expects($this->once())
            ->method('getKernel')
            ->will($this->returnValue($kernel))
        ;
        $definition = $this->getMock('Symfony\Component\Console\Input\InputDefinition');
        $definition
            ->expects($this->once())
            ->method('hasArgument')
            ->will($this->returnValue(false))
        ;
        $app->expects($this->once())
            ->method('getDefinition')
            ->will($this->returnValue($definition))
        ;
        $command->expects($this->exactly(2))
            ->method('getApplication')
            ->will($this->returnValue($app))
        ;
        $command->setContainer($this->getContainer());
        $command->setHelperSet($this->getHelperSet(''));
        $command->setGenerator($generator);

        return $command;
    }

    protected function getGenerator()
    {
        return $this
            ->getMockBuilder('Sonata\DoctrineORMAdminBundle\Generator\DoctrineORMAdminGenerator')
            ->disableOriginalConstructor()
            ->setMethods(array('generate'))
            ->getMock()
        ;
    }

    protected function getContainer()
    {
        $container = parent::getContainer();

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(array('getAliasNamespace'))
            ->getMock()
        ;
        $doctrine
            ->expects($this->once())
            ->method('getAliasNamespace')
            ->with($this->equalTo('SonataDoctrineORMAdminBundle'))
            ->will($this->returnValue('Sonata\DoctrineORMAdminBundle\Tests\Command\Entity'))
        ;

        $container->set('doctrine', $doctrine);

        return $container;
    }
}
 