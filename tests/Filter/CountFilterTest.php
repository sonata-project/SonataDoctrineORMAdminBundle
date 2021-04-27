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

use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\Type\Operator\NumberOperatorType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\CountFilter;

class CountFilterTest extends FilterTestCase
{
    public function testFilterEmpty(): void
    {
        $filter = new CountFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray([]));

        $this->assertSameQuery([], $proxyQuery);
        $this->assertFalse($filter->isActive());
    }

    public function testFilterInvalidOperator(): void
    {
        $filter = new CountFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray(['type' => 42]));

        $this->assertSameQuery([], $proxyQuery);
        $this->assertFalse($filter->isActive());
    }

    /**
     * @dataProvider filterDataProvider
     */
    public function testFilter(string $expected, ?int $type): void
    {
        $filter = new CountFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray(['type' => $type, 'value' => 42]));

        $this->assertSameQuery(['GROUP BY o', $expected], $proxyQuery);
        $this->assertTrue($filter->isActive());
    }

    /**
     * @phpstan-return iterable<array{string, int|null}>
     */
    public function filterDataProvider(): iterable
    {
        return [
            ['HAVING COUNT(alias.field) = :field_name_0', NumberOperatorType::TYPE_EQUAL],
            ['HAVING COUNT(alias.field) >= :field_name_0', NumberOperatorType::TYPE_GREATER_EQUAL],
            ['HAVING COUNT(alias.field) > :field_name_0', NumberOperatorType::TYPE_GREATER_THAN],
            ['HAVING COUNT(alias.field) <= :field_name_0', NumberOperatorType::TYPE_LESS_EQUAL],
            ['HAVING COUNT(alias.field) < :field_name_0', NumberOperatorType::TYPE_LESS_THAN],
            ['HAVING COUNT(alias.field) = :field_name_0', null],
        ];
    }
}
