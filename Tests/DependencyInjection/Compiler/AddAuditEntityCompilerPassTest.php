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

use Sonata\DoctrineORMAdminBundle\DependencyInjection\Compiler\AddAuditEntityCompilerPass;
use Symfony\Component\DependencyInjection\Definition;

class AddAuditEntityCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function processDataProvider()
    {
        return array(
            array(true, array(
                'admin1' => array('audit' => null,  'audited' => true),
                'admin2' => array('audit' => true,  'audited' => true),
                'admin3' => array('audit' => false, 'audited' => false),
            )),
            array(false, array(
                'admin1' => array('audit' => null,  'audited' => false),
                'admin2' => array('audit' => true,  'audited' => true),
                'admin3' => array('audit' => false, 'audited' => false),
            )),
        );
    }

    /**
     * @dataProvider processDataProvider
     */
    public function testProcess($force, array $services)
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        $container
            ->expects($this->any())
            ->method('hasDefinition')
            ->will($this->returnCallback(function ($id) {
                if ('simplethings_entityaudit.config' === $id) {
                    return true;
                }
            }))
        ;

        $container
            ->expects($this->any())
            ->method('getParameter')
            ->will($this->returnCallback(function ($id) use ($force) {
                if ('sonata_doctrine_orm_admin.audit.force' === $id) {
                    return $force;
                }

                if ('simplethings.entityaudit.audited_entities' === $id) {
                    return array();
                }
            }))
        ;

        $container
            ->expects($this->any())
            ->method('findTaggedServiceIds')
            ->will($this->returnCallback(function ($id) use ($services) {
                if ('sonata.admin' === $id) {
                    $tags = array();

                    foreach ($services as $id => $service) {
                        $attributes = array('manager_type' => 'orm');

                        if (null !== $audit = $service['audit']) {
                            $attributes['audit'] = $audit;
                        }

                        $tags[$id] = array(0 => $attributes);
                    }

                    return $tags;
                }
            }))
        ;

        $container
            ->expects($this->any())
            ->method('getDefinition')
            ->will($this->returnCallback(function ($id) {
                return new Definition(null, array(null, $id));
            }))
        ;

        $expectedAuditedEntities = array();

        foreach ($services as $id => $service) {
            if ($service['audited']) {
                $expectedAuditedEntities[] = $id;
            }
        }

        $container
            ->expects($this->once())
            ->method('setParameter')
            ->with('simplethings.entityaudit.audited_entities', $expectedAuditedEntities)
        ;

        $compilerPass = new AddAuditEntityCompilerPass();
        $compilerPass->process($container);
    }
}
