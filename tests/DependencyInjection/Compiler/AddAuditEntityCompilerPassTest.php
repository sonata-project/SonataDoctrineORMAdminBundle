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

final class AddAuditEntityCompilerPassTest extends TestCase
{
    /**
     * @phpstan-return iterable<array-key, array{bool, array<string, array{audit: bool|null, audited: bool}>}>
     */
    public function processDataProvider(): iterable
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
     * @phpstan-param array<string, array{audit: bool|null, audited: bool}> $services
     *
     * @dataProvider processDataProvider
     */
    public function testProcess(bool $force, array $services): void
    {
        $container = $this->createMock(ContainerBuilder::class);

        $container
            ->expects(static::any())
            ->method('hasDefinition')
            ->willReturnCallback(static function (string $id): bool {
                return 'simplethings_entityaudit.config' === $id;
            });

        $container
            ->expects(static::any())
            ->method('getParameter')
            ->willReturnCallback(static function (string $id) use ($force) {
                if ('sonata_doctrine_orm_admin.audit.force' === $id) {
                    return $force;
                }

                if ('simplethings.entityaudit.audited_entities' === $id) {
                    return [];
                }

                return null;
            });

        $container
            ->expects(static::any())
            ->method('findTaggedServiceIds')
            ->willReturnCallback(static function (string $id) use ($services): array {
                if ('sonata.admin' !== $id) {
                    return [];
                }

                $tags = [];
                foreach ($services as $serviceId => $service) {
                    $attributes = ['manager_type' => 'orm'];

                    $audit = $service['audit'];
                    if (null !== $audit) {
                        $attributes['audit'] = $audit;
                    }

                    $tags[$serviceId] = [0 => $attributes];
                }

                return $tags;
            });

        $container
            ->expects(static::any())
            ->method('getDefinition')
            ->willReturnCallback(static function (string $id): Definition {
                return new Definition(null, [null, $id]);
            });

        $expectedAuditedEntities = [];

        foreach ($services as $id => $service) {
            if ($service['audited']) {
                $expectedAuditedEntities[] = $id;
            }
        }

        $container
            ->expects(static::once())
            ->method('setParameter')
            ->with('simplethings.entityaudit.audited_entities', $expectedAuditedEntities);

        $compilerPass = new AddAuditEntityCompilerPass();
        $compilerPass->process($container);
    }
}
