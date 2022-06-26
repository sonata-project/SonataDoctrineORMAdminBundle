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

namespace Sonata\DoctrineORMAdminBundle\Tests\Filter;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;

abstract class FilterTestCase extends TestCase
{
    /**
     * @param string[] $expected
     */
    final protected function assertSameQuery(array $expected, ProxyQuery $proxyQuery): void
    {
        $queryBuilder = $proxyQuery->getQueryBuilder();
        if (!$queryBuilder instanceof TestQueryBuilder) {
            throw new \InvalidArgumentException('The query builder should be build with "createQueryBuilderStub()".');
        }

        static::assertSame($expected, $queryBuilder->query);
    }

    /**
     * @param mixed[] $expected
     */
    final protected function assertSameQueryParameters(array $expected, ProxyQuery $proxyQuery): void
    {
        $queryBuilder = $proxyQuery->getQueryBuilder();
        if (!$queryBuilder instanceof TestQueryBuilder) {
            throw new \InvalidArgumentException('The query builder should be build with "createQueryBuilderStub()".');
        }

        static::assertSame($expected, $queryBuilder->queryParameters);
    }

    final protected function createQueryBuilderStub(): TestQueryBuilder
    {
        $queryBuilder = $this->createStub(TestQueryBuilder::class);

        $queryBuilder->method('setParameter')->willReturnCallback(
            /**
             * @param mixed $value
             */
            static function (string $name, $value) use ($queryBuilder): void {
                $queryBuilder->queryParameters[$name] = $value;
            }
        );

        $queryBuilder->method('andWhere')->willReturnCallback(
            /**
             * @param mixed $query
             */
            static function ($query) use ($queryBuilder): void {
                $queryBuilder->query[] = sprintf('WHERE %s', $query);
            }
        );

        $queryBuilder->method('andHaving')->willReturnCallback(
            /**
             * @param mixed $query
             */
            static function ($query) use ($queryBuilder): void {
                $queryBuilder->query[] = sprintf('HAVING %s', $query);
            }
        );

        $queryBuilder->method('addGroupBy')->willReturnCallback(
            static function (string $groupBy) use ($queryBuilder): void {
                $queryBuilder->query[] = sprintf('GROUP BY %s', $groupBy);
            }
        );

        $queryBuilder->method('expr')->willReturnCallback(
            fn (): Expr => $this->createExprStub()
        );

        $queryBuilder->method('getRootAliases')->willReturnCallback(
            static fn (): array => ['o']
        );

        $queryBuilder->method('getDQLPart')->willReturnCallback(
            static fn (): array => []
        );

        $queryBuilder->method('leftJoin')->willReturnCallback(
            static function (string $parameter, string $alias) use ($queryBuilder): void {
                $queryBuilder->query[] = sprintf('LEFT JOIN %s AS %s', $parameter, $alias);
            }
        );

        return $queryBuilder;
    }

    private function createExprStub(): Expr
    {
        $expr = $this->createStub(Expr::class);

        $expr->method('orX')->willReturnCallback(
            static fn (): Orx => new Orx(\func_get_args())
        );

        $expr->method('andX')->willReturnCallback(
            static fn (): Andx => new Andx(\func_get_args())
        );

        $expr->method('in')->willReturnCallback(
            /**
             * @param mixed $parameter
             */
            static function (string $alias, $parameter): string {
                if (\is_array($parameter)) {
                    return sprintf('%s IN ("%s")', $alias, implode(', ', $parameter));
                }

                return sprintf('%s IN %s', $alias, $parameter);
            }
        );

        $expr->method('notIn')->willReturnCallback(
            /**
             * @param mixed $parameter
             */
            static function (string $alias, $parameter): string {
                if (\is_array($parameter)) {
                    return sprintf('%s NOT IN ("%s")', $alias, implode(', ', $parameter));
                }

                return sprintf('%s NOT IN %s', $alias, $parameter);
            }
        );

        $expr->method('isNull')->willReturnCallback(
            static fn (string $queryPart): string => $queryPart.' IS NULL'
        );

        $expr->method('isNotNull')->willReturnCallback(
            static fn (string $queryPart): string => $queryPart.' IS NOT NULL'
        );

        return $expr;
    }
}

class TestQueryBuilder extends QueryBuilder
{
    /** @var string[] */
    public $query = [];

    /** @var mixed[] */
    public $queryParameters = [];
}
