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

use PHPUnit\Framework\TestCase;
use Sonata\DoctrineORMAdminBundle\DependencyInjection\Compiler\AddGuesserCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AddGuesserCompilerPassTest extends TestCase
{
    public function testProcess()
    {
        $containerBuilder = $this->prophesize(ContainerBuilder::class);
        $definition = $this->prophesize(Definition::class);
        $definition->replaceArgument(0, [new Reference('some.id')])->shouldBeCalledTimes(3);

        $containerBuilder->getDefinition('sonata.admin.guesser.orm_list_chain')->willReturn($definition->reveal());
        $containerBuilder->getDefinition('sonata.admin.guesser.orm_datagrid_chain')->willReturn($definition->reveal());
        $containerBuilder->getDefinition('sonata.admin.guesser.orm_show_chain')->willReturn($definition->reveal());

        $containerBuilder->findTaggedServiceIds('sonata.admin.guesser.orm_list')->willReturn(['some.id' => 'attr']);
        $containerBuilder->findTaggedServiceIds('sonata.admin.guesser.orm_datagrid')->willReturn(['some.id' => 'attr']);
        $containerBuilder->findTaggedServiceIds('sonata.admin.guesser.orm_show')->willReturn(['some.id' => 'attr']);

        (new AddGuesserCompilerPass())->process($containerBuilder->reveal());
    }
}
