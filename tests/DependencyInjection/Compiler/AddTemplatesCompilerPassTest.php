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
use Sonata\DoctrineORMAdminBundle\DependencyInjection\Compiler\AddTemplatesCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class AddTemplatesCompilerPassTest extends TestCase
{
    public function testDefaultBehavior(): void
    {
        $container = $this->createMock(ContainerBuilder::class);

        $container
            ->expects(static::once())
            ->method('findTaggedServiceIds')
            ->willReturn(['my.admin' => [['manager_type' => 'orm']]]);

        $definition = new Definition(null);

        $container
            ->expects(static::once())
            ->method('getDefinition')
            ->willReturn($definition);

        $definition->addMethodCall('setFilterTheme', [['custom_call.twig.html']]);

        $compilerPass = new AddTemplatesCompilerPass();
        $compilerPass->process($container);

        $expected = [
            ['setFilterTheme', [['@SonataDoctrineORMAdmin/Form/filter_admin_fields.html.twig', 'custom_call.twig.html']]],
            ['setFormTheme', [['@SonataDoctrineORMAdmin/Form/form_admin_fields.html.twig']]],
        ];

        static::assertSame($expected, $definition->getMethodCalls());
    }
}
