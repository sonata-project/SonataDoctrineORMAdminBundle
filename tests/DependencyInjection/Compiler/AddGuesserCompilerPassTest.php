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

final class AddGuesserCompilerPassTest extends TestCase
{
    public function testProcess(): void
    {
        $containerBuilder = $this->createMock(ContainerBuilder::class);
        $definition = $this->createMock(Definition::class);
        $definition->expects($this->exactly(3))->method('replaceArgument')->with(0, [new Reference('some.id')]);

        $containerBuilder->method('getDefinition')->withConsecutive(
            ['sonata.admin.guesser.orm_list_chain'],
            ['sonata.admin.guesser.orm_datagrid_chain'],
            ['sonata.admin.guesser.orm_show_chain']
        )->willReturn($definition);

        $containerBuilder->method('findTaggedServiceIds')->withConsecutive(
            ['sonata.admin.guesser.orm_list'],
            ['sonata.admin.guesser.orm_datagrid'],
            ['sonata.admin.guesser.orm_show']
        )->willReturn(['some.id' => 'attr']);

        (new AddGuesserCompilerPass())->process($containerBuilder);
    }
}
