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
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\Product;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\SimpleEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\UuidEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\VersionedEntity;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class AddAuditEntityCompilerPassTest extends TestCase
{
    /**
     * @phpstan-return iterable<array-key, array{bool, array<string, array{audit?: bool|null, class: class-string}>, class-string[]}>
     */
    public function processDataProvider(): iterable
    {
        return [
            [
                true,
                [
                    'admin1' => ['audit' => null,  'class' => Product::class],
                    'admin2' => ['audit' => true,  'class' => SimpleEntity::class],
                    'admin3' => ['audit' => false, 'class' => UuidEntity::class],
                    'admin4' => ['class' => VersionedEntity::class],
                ],
                [
                    Product::class,
                    SimpleEntity::class,
                    VersionedEntity::class,
                ],
            ],
            [
                false,
                [
                    'admin1' => ['audit' => null,  'class' => Product::class],
                    'admin2' => ['audit' => true,  'class' => SimpleEntity::class],
                    'admin3' => ['audit' => false, 'class' => UuidEntity::class],
                    'admin4' => ['class' => VersionedEntity::class],
                ],
                [
                    SimpleEntity::class,
                ],
            ],
        ];
    }

    /**
     * @phpstan-param array<string, array{audit?: bool|null, class: class-string}> $services
     * @phpstan-param class-string[] $expectedAuditedEntities
     *
     * @dataProvider processDataProvider
     */
    public function testProcess(bool $force, array $services, array $expectedAuditedEntities): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('simplethings_entityaudit.config', new Definition());
        $container->setDefinition('sonata.admin.audit.manager', new Definition());

        $container->setParameter('sonata_doctrine_orm_admin.audit.force', $force);
        $container->setParameter('simplethings.entityaudit.audited_entities', []);

        foreach ($services as $serviceId => $service) {
            $definition = new Definition();

            $attributes = [
                'manager_type' => 'orm',
                'model_class' => $service['class'],
            ];

            if (isset($service['audit'])) {
                $attributes['audit'] = $service['audit'];
            }

            $definition->addTag('sonata.admin', $attributes);

            $container->setDefinition($serviceId, $definition);
        }

        $compilerPass = new AddAuditEntityCompilerPass();
        $compilerPass->process($container);

        static::assertSame($expectedAuditedEntities, $container->getParameter('simplethings.entityaudit.audited_entities'));
    }
}
