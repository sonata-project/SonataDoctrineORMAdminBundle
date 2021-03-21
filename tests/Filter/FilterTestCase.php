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
    final protected function assertSameQuery(array $expected, ProxyQuery $proxyQuery): void
    {
        $queryBuilder = $proxyQuery->getQueryBuilder();
        if (!$queryBuilder instanceof TestQueryBuilder) {
            throw new \InvalidArgumentException('The query builder should be build with "createQueryBuilderStub()".');
        }

        $this->assertSame($expected, $queryBuilder->query);
    }

    final protected function assertSameQueryParameters(array $expected, ProxyQuery $proxyQuery): void
    {
        $queryBuilder = $proxyQuery->getQueryBuilder();
        if (!$queryBuilder instanceof TestQueryBuilder) {
            throw new \InvalidArgumentException('The query builder should be build with "createQueryBuilderStub()".');
        }

        $this->assertSame($expected, $queryBuilder->queryParameters);
    }

    final protected function createQueryBuilderStub(): TestQueryBuilder
    {
        $testCase = $this;
        $queryBuilder = $this->createStub(TestQueryBuilder::class);

        $queryBuilder->method('setParameter')->willReturnCallback(
            static function (string $name, $value) use ($queryBuilder): void {
                $queryBuilder->queryParameters[$name] = $value;
            }
        );

        $queryBuilder->method('andWhere')->willReturnCallback(
            static function ($query) use ($queryBuilder): void {
                $queryBuilder->query[] = sprintf('WHERE %s', $query);
            }
        );

        $queryBuilder->method('andHaving')->willReturnCallback(
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
            static function () use ($testCase): Expr {
                return $testCase->createExprStub();
            }
        );

        $queryBuilder->method('getRootAliases')->willReturnCallback(
            static function (): array {
                return ['o'];
            }
        );

        $queryBuilder->method('getDQLPart')->willReturnCallback(
            static function (): array {
                return [];
            }
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
            static function ($x = null): Orx {
                return new Orx(\func_get_args());
            }
        );

        $expr->method('andX')->willReturnCallback(
            static function ($x = null): Andx {
                return new Andx(\func_get_args());
            }
        );

        $expr->method('in')->willReturnCallback(
            static function (string $alias, $parameter): string {
                if (\is_array($parameter)) {
                    return sprintf('%s IN ("%s")', $alias, implode(', ', $parameter));
                }

                return sprintf('%s IN %s', $alias, $parameter);
            }
        );

        $expr->method('notIn')->willReturnCallback(
            static function (string $alias, $parameter): string {
                if (\is_array($parameter)) {
                    return sprintf('%s NOT IN ("%s")', $alias, implode(', ', $parameter));
                }

                return sprintf('%s NOT IN %s', $alias, $parameter);
            }
        );

        $expr->method('isNull')->willReturnCallback(
            static function (string $queryPart): string {
                return $queryPart.' IS NULL';
            }
        );

        $expr->method('isNotNull')->willReturnCallback(
            static function (string $queryPart): string {
                return $queryPart.' IS NOT NULL';
            }
        );

        return $expr;
    }
}

class TestQueryBuilder extends QueryBuilder
{
    /** @var string[] */
    public $query = [];

    /** @var string[] */
    public $queryParameters = [];
}
