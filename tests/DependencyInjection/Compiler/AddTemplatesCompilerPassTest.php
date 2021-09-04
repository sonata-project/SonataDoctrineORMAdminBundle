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

class AddTemplatesCompilerPassTest extends TestCase
{
    public function testDefaultBehavior(): void
    {
        $container = $this->createMock(ContainerBuilder::class);

        // NEXT_MAJOR: Remove this.
        $container
            ->expects(static::once())
            ->method('getParameter')
            ->with('sonata_doctrine_orm_admin.templates')
            ->willReturn([
                'form' => ['default_form.twig.html'],
                'filter' => ['default_filter.twig.html'],
            ]);

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
            // NEXT_MAJOR: Uncomment the following line instead.
            ['setFilterTheme', [['custom_call.twig.html', 'default_filter.twig.html']]],
//            ['setFilterTheme', [['custom_call.twig.html', '@SonataDoctrineORMAdmin/Form/filter_admin_fields.html.twig']]],
            // NEXT_MAJOR: Uncomment the following line instead.
            ['setFormTheme', [['default_form.twig.html']]],
//            ['setFormTheme', [['@SonataDoctrineORMAdmin/Form/form_admin_fields.html.twig']]],
        ];

        static::assertSame($expected, $definition->getMethodCalls());
    }
}
