<?php

namespace Sonata\DoctrineORMAdminBundle\Command;

use Sensio\Bundle\GeneratorBundle\Command\GenerateDoctrineCommand;
use Sensio\Bundle\GeneratorBundle\Command\Validators;
use Sonata\DoctrineORMAdminBundle\Generator\DoctrineORMAdminGenerator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GenerateDoctrineORMAdminCommand
 * @package Sonata\DoctrineORMAdminBundle\Command
 */
class GenerateDoctrineORMAdminCommand extends GenerateDoctrineCommand
{
    protected function configure()
    {
        $this
            ->setName('doctrine:generate:admin')
            ->setDescription('Generates Admin class for specified ORM entity.')
            ->addArgument('entity', InputArgument::REQUIRED, '', null)
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entity = $input->getArgument('entity');
        Validators::validateEntityName($entity);
        list($bundle, $entity) = explode(':', $entity);

        $entityClass = $this->getContainer()->get('doctrine')->getAliasNamespace($bundle).'\\'.$entity;
        $metadata = $this->getEntityMetadata($entityClass);
        $bundle   = $this->getApplication()->getKernel()->getBundle($bundle);

        /** @var DoctrineORMAdminGenerator $generator */
        $generator = $this->getGenerator($bundle);
        $generator->generate($bundle, $entity, $metadata[0]);

        $output->writeln(sprintf(
            'The new %s.php class file has been created under %s.',
            $generator->getClassName(),
            $generator->getClassPath()
        ));
    }

    /**
     * @return DoctrineORMAdminGenerator
     */
    protected function createGenerator()
    {
        $generator = new DoctrineORMAdminGenerator($this->getContainer()->get('filesystem'));
        $generator->setSkeletonDirs(array(__DIR__.'/../Resources/skeleton'));

        return $generator;
    }
}
