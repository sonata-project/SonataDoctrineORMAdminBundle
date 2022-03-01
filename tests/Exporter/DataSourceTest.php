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
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Exporter\DataSource;
use Sonata\Exporter\Source\DoctrineORMQuerySourceIterator;

final class DataSourceTest extends TestCase
{
    /**
     * @var DataSource
     */
    private $dataSource;

    protected function setUp(): void
    {
        $this->dataSource = new DataSource();
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

        $queryBuilder->expects(static::once())->method('distinct');
        $queryBuilder->expects(static::once())->method('getRootAliases')->willReturn(['o', 'a', 'e']);
        $queryBuilder->expects(static::once())->method('select')->with('o');

        $proxyQuery = $this->createMock(ProxyQueryInterface::class);

        $proxyQuery->method('getQueryBuilder')->willReturn($queryBuilder);
        $proxyQuery->method('getDoctrineQuery')->willReturn($query);
        $proxyQuery->expects(static::once())->method('setFirstResult')->with(null);
        $proxyQuery->expects(static::once())->method('setMaxResults')->with(null);

        $iterator = $this->dataSource->createIterator($proxyQuery, []);
        static::assertInstanceOf(DoctrineORMQuerySourceIterator::class, $iterator);
    }

    public function testCreateIteratorWithSortBy(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDefaultQueryHints')->willReturn([]);

        $em = $this->createStub(EntityManager::class);
        $em->method('getConfiguration')->willReturn($configuration);

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->setConstructorArgs([$em])
            ->getMock();

        $query = new Query($em);

        $queryBuilder->expects(static::once())->method('getRootAliases')->willReturn(['o', 'a', 'e']);
        $queryBuilder->expects(static::once())->method('distinct');
        $queryBuilder->expects(static::once())->method('select')->with('o');
        $queryBuilder->expects(static::once())->method('addSelect')->with('a');

        $proxyQuery = $this->createMock(ProxyQueryInterface::class);
        $proxyQuery->method('getSortBy')->willReturn('a.column');

        $proxyQuery->method('getQueryBuilder')->willReturn($queryBuilder);
        $proxyQuery->method('getDoctrineQuery')->willReturn($query);
        $proxyQuery->expects(static::once())->method('setFirstResult')->with(null);
        $proxyQuery->expects(static::once())->method('setMaxResults')->with(null);

        $iterator = $this->dataSource->createIterator($proxyQuery, []);

        static::assertInstanceOf(DoctrineORMQuerySourceIterator::class, $iterator);
    }
}
