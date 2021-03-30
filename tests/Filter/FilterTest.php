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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Filter\Filter;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter;

final class FilterTest extends FilterTestCase
{
    /**
     * @var Filter
     */
    private $filter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filter = $this->createFilter();
    }

    public function testFieldDescription(): void
    {
        $this->assertSame(['option1' => 2], $this->filter->getDefaultOptions());
        $this->assertNull($this->filter->getOption('1'));

        $this->filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $this->assertSame(2, $this->filter->getOption('option1'));
        $this->assertNull($this->filter->getOption('foo'));
        $this->assertSame('bar', $this->filter->getOption('foo', 'bar'));

        $this->assertSame('field_name', $this->filter->getName());
        $this->assertSame(['class' => 'FooBar'], $this->filter->getFieldOptions());
    }

    public function testExceptionOnEmptyFieldName(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->filter->getFieldName();
    }

    public function testIsActive(): void
    {
        $this->assertFalse($this->filter->isActive());
    }

    /**
     * @dataProvider orExpressionProvider
     */
    public function testOrExpression(string $expected, array $filterOptions = []): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getExpressionBuilder')->willReturn(new Expr());
        $queryBuilder = new TestQueryBuilder($entityManager);

        $queryBuilder->select('e')->from('MyEntity', 'e');

        // Some custom conditions set previous to the filters.
        $queryBuilder
            ->where($queryBuilder->expr()->eq(1, 2))
            ->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq(':parameter_1', 4),
                    $queryBuilder->expr()->eq(5, 6)
                )
            )
            ->setParameter('parameter_1', 3);

        $proxyQuery = new ProxyQuery($queryBuilder);
        $this->assertSameQuery([], $proxyQuery);

        $filter1 = new StringFilter();
        $filter1->setCondition(Filter::CONDITION_OR);
        $filter1->initialize('field_name', $filterOptions);

        $filter1->filter($proxyQuery, 'e', 'project', ['value' => 'sonata-project']);

        $filter2 = new StringFilter();
        $filter2->setCondition(Filter::CONDITION_OR);
        $filter2->initialize('field_name', $filterOptions);

        $filter2->filter($proxyQuery, 'e', 'version', ['value' => '3.x']);

        // More custom conditions set after the filters.
        $queryBuilder->andWhere($queryBuilder->expr()->eq(7, 8));

        $this->assertSame($expected, $queryBuilder->getDQL());
    }

    public function orExpressionProvider(): iterable
    {
        yield 'Using "admin_code" option' => [
            'SELECT e FROM MyEntity e WHERE 1 = 2 AND (:parameter_1 = 4 OR 5 = 6)'
            .' AND (e.project LIKE :field_name_0 OR e.version LIKE :field_name_1) AND 7 = 8',
            [
                'allow_empty' => false,
                'admin_code' => 'my_admin',
            ],
        ];

        yield 'Missing "admin_code" option, fallback to DQL marker' => [
            'SELECT e FROM MyEntity e WHERE 1 = 2 AND (:parameter_1 = 4 OR 5 = 6)'
            .' AND (:sonata_admin_datagrid_filter_query_marker IS NULL'
            .' OR e.project LIKE :field_name_0 OR e.version LIKE :field_name_1) AND 7 = 8',
            [
                'allow_empty' => false,
            ],
        ];
    }

    private function createFilter(): Filter
    {
        return new class() extends Filter {
            public function filter(ProxyQueryInterface $queryBuilder, string $alias, string $field, array $data): void
            {
                // TODO: Implement filter() method.
                throw new \BadMethodCallException(sprintf(
                    'Method "%s()" is not implemented.',
                    __METHOD__
                ));
            }

            public function getDefaultOptions(): array
            {
                return ['option1' => 2];
            }

            public function getRenderSettings(): array
            {
                return ['sonata_type_filter_default', [
                    'type' => $this->getFieldType(),
                    'options' => $this->getFieldOptions(),
                ]];
            }
        };
    }
}
