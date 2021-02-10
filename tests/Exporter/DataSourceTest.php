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
use Sonata\DoctrineORMAdminBundle\Datagrid\OrderByToSelectWalker;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Exporter\DataSource;

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

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $queryBuilder->expects($isAddOrderBy ? $this->atLeastOnce() : $this->never())->method('addOrderBy');
        $queryBuilder->method('getRootAliases')->willReturn(['a']);

        $query = new Query($em);
        $queryBuilder->method('getQuery')->willReturn($query);

        $proxyQuery = new ProxyQuery($queryBuilder);
        if (null !== $sortBy) {
            $proxyQuery->setSortBy([], ['fieldName' => $sortBy]);
        }
        if (null !== $sortOrder) {
            $proxyQuery->setSortOrder($sortOrder);
        }

        $this->dataSource->createIterator($proxyQuery, []);

        if ($isAddOrderBy) {
            $this->assertArrayHasKey($key = 'doctrine.customTreeWalkers', $hints = $query->getHints());
            $this->assertContains(OrderByToSelectWalker::class, $hints[$key]);
        }
    }
}
