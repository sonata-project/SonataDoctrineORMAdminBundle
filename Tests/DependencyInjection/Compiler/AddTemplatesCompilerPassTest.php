<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Tests\DependencyInjection\Compiler;

use Sonata\DoctrineORMAdminBundle\DependencyInjection\Compiler\AddTemplatesCompilerPass;
use Symfony\Component\DependencyInjection\Definition;

class AddTemplatesCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultBehavior()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        $container
            ->expects($this->any())
            ->method('getParameter')
            ->will($this->returnCallback(function ($value) {
                if ($value == 'sonata.admin.configuration.admin_services') {
                    return array(
                        'my.admin' => array(
                            'templates' => array(
                                'form' => array('myform.twig.html'),
                                'filter' => array('myfilter.twig.html'),
                            ),
                        ),
                    );
                }

                if ($value == 'sonata_doctrine_orm_admin.templates') {
                    return array(
                        'form' => array('default_form.twig.html'),
                        'filter' => array('default_filter.twig.html'),
                    );
                }
            }))
        ;

        $container
            ->expects($this->any())
            ->method('findTaggedServiceIds')
            ->will($this->returnValue(array('my.admin' => array(array('manager_type' => 'orm')))))
        ;

        $definition = new Definition(null);

        $container
            ->expects($this->any())
            ->method('getDefinition')
            ->will($this->returnValue($definition))
        ;

        $definition->addMethodCall('setFilterTheme', array(array('custom_call.twig.html')));

        $compilerPass = new AddTemplatesCompilerPass();
        $compilerPass->process($container);

        $expected = array(
            array('setFilterTheme', array(array('custom_call.twig.html', 'myfilter.twig.html'))),
            array('setFormTheme', array(array('default_form.twig.html', 'myform.twig.html'))),
        );

        $this->assertEquals($expected, $definition->getMethodCalls());
    }
}
