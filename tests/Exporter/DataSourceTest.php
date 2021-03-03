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

namespace Sonata\DoctrineORMAdminBundle\Tests\Exporter;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface as BaseProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Datagrid\OrderByToSelectWalker;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Exporter\DataSource;
use Sonata\Exporter\Source\DoctrineORMQuerySourceIterator;

final class DataSourceTest extends TestCase
{
    /**
     * @var DataSource
     */
    private $dataSource;

    protected function setup(): void
    {
        $this->dataSource = new DataSource();
    }

    /**
     * @phpstan-return array<array{string|null, string|null, bool}>
     */
    public function getSortableInDataSourceIteratorDataProvider(): array
    {
        return [
            [null, null, false],
            [null, 'ASC', false],
            ['field', 'ASC', true],
            ['field', null, true],
        ];
    }

    /**
     * NEXT_MAJOR: Remove this test.
     *
     * @group legacy
     *
     * @dataProvider getSortableInDataSourceIteratorDataProvider
     */
    public function testSortableInDataSourceIterator(
        ?string $sortBy,
        ?string $sortOrder,
        bool $isAddOrderBy
    ): void {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDefaultQueryHints')->willReturn([]);

        $em = $this->createStub(EntityManager::class);
        $em->method('getConfiguration')->willReturn($configuration);

        $query = new Query($em);
        $proxyQuery = $this->getMockBuilder(BaseProxyQueryInterface::class)
            ->addMethods(['select', 'getRootAliases', 'addOrderBy', 'getQuery'])
            ->getMockForAbstractClass();

        $proxyQuery->method('getRootAliases')->willReturn(['a']);
        $proxyQuery->method('getSortOrder')->willReturn($sortOrder);
        $proxyQuery->method('getSortBy')->willReturn($sortBy);
        $proxyQuery->expects($isAddOrderBy ? $this->atLeastOnce() : $this->never())->method('addOrderBy');
        $proxyQuery->method('getQuery')->willReturn($query);

        $this->dataSource->createIterator($proxyQuery, []);

        if ($isAddOrderBy) {
            $this->assertArrayHasKey($key = 'doctrine.customTreeWalkers', $hints = $query->getHints());
            $this->assertContains(OrderByToSelectWalker::class, $hints[$key]);
        }
    }

    public function testCreateIterator(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDefaultQueryHints')->willReturn([]);

        $em = $this->createStub(EntityManager::class);
        $em->method('getConfiguration')->willReturn($configuration);

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->setConstructorArgs([$em])
            ->getMock();
        $query = new Query($em);

        $queryBuilder->expects($this->once())->method('distinct');

        $proxyQuery = $this->createMock(ProxyQueryInterface::class);

        $proxyQuery->method('getQueryBuilder')->willReturn($queryBuilder);
        $proxyQuery->method('getDoctrineQuery')->willReturn($query);
        $proxyQuery->expects($this->once())->method('setFirstResult')->with(null);
        $proxyQuery->expects($this->once())->method('setMaxResults')->with(null);

        $iterator = $this->dataSource->createIterator($proxyQuery, []);
        $this->assertInstanceOf(DoctrineORMQuerySourceIterator::class, $iterator);
    }
}
