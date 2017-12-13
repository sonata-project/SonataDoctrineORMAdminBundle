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
use Symfony\Component\DependencyInjection\Definition;

class AddTemplatesCompilerPassTest extends TestCase
{
    public function testDefaultBehavior(): void
    {
        $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        $container
            ->expects($this->any())
            ->method('getParameter')
            ->will($this->returnCallback(function ($value) {
                if ('sonata.admin.configuration.admin_services' == $value) {
                    return [
                        'my.admin' => [
                            'templates' => [
                                'form' => ['myform.twig.html'],
                                'filter' => ['myfilter.twig.html'],
                            ],
                        ],
                    ];
                }

                if ('sonata_doctrine_orm_admin.templates' == $value) {
                    return [
                        'form' => ['default_form.twig.html'],
                        'filter' => ['default_filter.twig.html'],
                    ];
                }
            }))
        ;

        $container
            ->expects($this->any())
            ->method('findTaggedServiceIds')
            ->will($this->returnValue(['my.admin' => [['manager_type' => 'orm']]]))
        ;

        $definition = new Definition(null);

        $container
            ->expects($this->any())
            ->method('getDefinition')
            ->will($this->returnValue($definition))
        ;

        $definition->addMethodCall('setFilterTheme', [['custom_call.twig.html']]);

        $compilerPass = new AddTemplatesCompilerPass();
        $compilerPass->process($container);

        $expected = [
            ['setFilterTheme', [['custom_call.twig.html', 'myfilter.twig.html']]],
            ['setFormTheme', [['default_form.twig.html', 'myform.twig.html']]],
        ];

        $this->assertEquals($expected, $definition->getMethodCalls());
    }
}
