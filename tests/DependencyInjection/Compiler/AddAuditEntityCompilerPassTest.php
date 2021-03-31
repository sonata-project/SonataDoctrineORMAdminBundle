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
use Sonata\DoctrineORMAdminBundle\DependencyInjection\Compiler\AddAuditEntityCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class AddAuditEntityCompilerPassTest extends TestCase
{
    public function processDataProvider()
    {
        return [
            [true, [
                'admin1' => ['audit' => null,  'audited' => true],
                'admin2' => ['audit' => true,  'audited' => true],
                'admin3' => ['audit' => false, 'audited' => false],
            ]],
            [false, [
                'admin1' => ['audit' => null,  'audited' => false],
                'admin2' => ['audit' => true,  'audited' => true],
                'admin3' => ['audit' => false, 'audited' => false],
            ]],
        ];
    }

    /**
     * @dataProvider processDataProvider
     */
    public function testProcess($force, array $services): void
    {
        $container = $this->createMock(ContainerBuilder::class);

        $container
            ->expects($this->any())
            ->method('hasDefinition')
            ->willReturnCallback(static function ($id) {
                if ('simplethings_entityaudit.config' === $id) {
                    return true;
                }
            });

        $container
            ->expects($this->any())
            ->method('getParameter')
            ->willReturnCallback(static function ($id) use ($force) {
                if ('sonata_doctrine_orm_admin.audit.force' === $id) {
                    return $force;
                }

                if ('simplethings.entityaudit.audited_entities' === $id) {
                    return [];
                }
            });

        $container
            ->expects($this->any())
            ->method('findTaggedServiceIds')
            ->willReturnCallback(static function ($id) use ($services) {
                if ('sonata.admin' === $id) {
                    $tags = [];

                    foreach ($services as $id => $service) {
                        $attributes = ['manager_type' => 'orm'];

                        if (null !== $audit = $service['audit']) {
                            $attributes['audit'] = $audit;
                        }

                        $tags[$id] = [0 => $attributes];
                    }

                    return $tags;
                }
            });

        $container
            ->expects($this->any())
            ->method('getDefinition')
            ->willReturnCallback(static function ($id) {
                return new Definition(null, [null, $id]);
            });

        $expectedAuditedEntities = [];

        foreach ($services as $id => $service) {
            if ($service['audited']) {
                $expectedAuditedEntities[] = $id;
            }
        }

        $container
            ->expects($this->once())
            ->method('setParameter')
            ->with('simplethings.entityaudit.audited_entities', $expectedAuditedEntities);

        $compilerPass = new AddAuditEntityCompilerPass();
        $compilerPass->process($container);
    }
}
