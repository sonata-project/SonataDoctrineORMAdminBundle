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

namespace Sonata\DoctrineORMAdminBundle\Tests\Util;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use PHPUnit\Framework\TestCase;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Author;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Item;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\TestEntityManagerFactory;
use Sonata\DoctrineORMAdminBundle\Util\SmartPaginatorFactory;

final class SmartPaginatorFactoryTest extends TestCase
{
    /**
     * @dataProvider getQueriesForFetchJoinedCollection
     */
    public function testFetchJoinedCollection(QueryBuilder $queryBuilder, bool $expected): void
    {
        $proxyQuery = $this->createStub(ProxyQueryInterface::class);
        $proxyQuery
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $proxyQuery
            ->method('getDoctrineQuery')
            ->willReturn($queryBuilder->getQuery());

        $paginator = SmartPaginatorFactory::create($proxyQuery);

        $this->assertSame($expected, $paginator->getFetchJoinCollection());
    }

    /**
     * @phpstan-return iterable<array-key, array{QueryBuilder, bool}>
     */
    public function getQueriesForFetchJoinedCollection(): iterable
    {
        yield 'Without joins' => [
            TestEntityManagerFactory::create()
                ->createQueryBuilder()
                ->from(Author::class, 'author'),
            false,
        ];

        yield 'With joins and simple identifier' => [
            TestEntityManagerFactory::create()
                ->createQueryBuilder()
                ->from(Author::class, 'author')
                ->leftJoin('author.books', 'book'),
            true,
        ];

        yield 'With joins and composite identifier' => [
            TestEntityManagerFactory::create()
                ->createQueryBuilder()
                ->from(Item::class, 'item')
                ->leftJoin('item.product', 'product'),
            false,
        ];
    }

    /**
     * @dataProvider getQueriesForOutputWalker
     *
     * @param bool|null $expected
     */
    public function testUseOutputWalker(QueryBuilder $queryBuilder, $expected): void
    {
        $proxyQuery = $this->createStub(ProxyQueryInterface::class);
        $proxyQuery
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $proxyQuery
            ->method('getDoctrineQuery')
            ->willReturn($queryBuilder->getQuery());

        $paginator = SmartPaginatorFactory::create($proxyQuery);

        $this->assertSame($expected, $paginator->getUseOutputWalkers());
    }

    /**
     * @phpstan-return iterable<array-key, array{QueryBuilder, bool|null}>
     */
    public function getQueriesForOutputWalker(): iterable
    {
        yield 'Simple query without joins' => [
            TestEntityManagerFactory::create()
                ->createQueryBuilder()
                ->from(Author::class, 'author'),
            false,
        ];

        yield 'Simple query with having' => [
            TestEntityManagerFactory::create()
                ->createQueryBuilder()
                ->from(Author::class, 'author')
                ->groupBy('author.name')
                ->having('COUNT(author.id) > 0'),
            null,
        ];

        yield 'With joins and simple identifier' => [
            TestEntityManagerFactory::create()
                ->createQueryBuilder()
                ->from(Author::class, 'author')
                ->leftJoin('author.books', 'book'),
            null,
        ];

        yield 'With joins and composite identifier' => [
            TestEntityManagerFactory::create()
                ->createQueryBuilder()
                ->from(Item::class, 'item')
                ->leftJoin('item.product', 'product'),
            null,
        ];
    }

    /**
     * @dataProvider getQueriesForCountWalkerDistinct
     */
    public function testCountWalkerDistinct(QueryBuilder $queryBuilder, bool $hasHint, bool $expected): void
    {
        $proxyQuery = $this->createStub(ProxyQueryInterface::class);
        $proxyQuery
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $query = $queryBuilder->getQuery();

        $proxyQuery
            ->method('getDoctrineQuery')
            ->willReturn($query);

        SmartPaginatorFactory::create($proxyQuery);

        $this->assertSame($hasHint, $query->hasHint(CountWalker::HINT_DISTINCT));
        $this->assertSame($expected, $query->getHint(CountWalker::HINT_DISTINCT));
    }

    /**
     * @phpstan-return iterable<array-key, array{QueryBuilder, bool, bool}>
     */
    public function getQueriesForCountWalkerDistinct(): iterable
    {
        yield 'Simple query without joins' => [
            TestEntityManagerFactory::create()
                ->createQueryBuilder()
                ->from(Author::class, 'author'),
            true,
            false,
        ];

        yield 'With joins and simple identifier' => [
            TestEntityManagerFactory::create()
                ->createQueryBuilder()
                ->from(Author::class, 'author')
                ->leftJoin('author.books', 'book'),
            false,
            false,
        ];
    }
}
