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

namespace Sonata\DoctrineORMAdminBundle\Tests\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Sonata\AdminBundle\FieldDescription\TypeGuesserChain;
use Sonata\DoctrineORMAdminBundle\DependencyInjection\Compiler\AddGuesserCompilerPass;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class AddGuesserCompilerPassTest extends AbstractCompilerPassTestCase
{
    public function testProcess(): void
    {
        $guesser = new Definition(TypeGuesserChain::class);
        $guesser->setArgument(0, new AbstractArgument());
        $this->setDefinition('sonata.admin.guesser.orm_list_chain', $guesser);
        $this->setDefinition('sonata.admin.guesser.orm_datagrid_chain', $guesser);
        $this->setDefinition('sonata.admin.guesser.orm_show_chain', $guesser);

        $tagged = new Definition();
        $tagged->addTag('sonata.admin.guesser.orm_list');
        $tagged->addTag('sonata.admin.guesser.orm_datagrid');
        $tagged->addTag('sonata.admin.guesser.orm_show');
        $this->setDefinition('random_service', $tagged);

        $this->compile();

        static::assertContainerBuilderHasServiceDefinitionWithArgument(
            'sonata.admin.guesser.orm_list_chain',
            0,
            [new Reference('random_service')]
        );

        static::assertContainerBuilderHasServiceDefinitionWithArgument(
            'sonata.admin.guesser.orm_datagrid_chain',
            0,
            [new Reference('random_service')]
        );

        static::assertContainerBuilderHasServiceDefinitionWithArgument(
            'sonata.admin.guesser.orm_show_chain',
            0,
            [new Reference('random_service')]
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new AddGuesserCompilerPass());
    }
}
