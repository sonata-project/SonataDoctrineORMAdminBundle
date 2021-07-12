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
use Sonata\DoctrineORMAdminBundle\Filter\NumberFilter;

final class NumberFilterTest extends FilterTestCase
{
    public function testFilterEmpty(): void
    {
        $filter = new NumberFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray([]));

        $this->assertSameQuery([], $proxyQuery);
        $this->assertFalse($filter->isActive());
    }

    public function testFilterInvalidOperator(): void
    {
        $filter = new NumberFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray(['type' => 42]));

        $this->assertSameQuery([], $proxyQuery);
        $this->assertFalse($filter->isActive());
    }

    public function testFilter(): void
    {
        $filter = new NumberFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray(['type' => NumberOperatorType::TYPE_EQUAL, 'value' => 42]));
        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray(['type' => NumberOperatorType::TYPE_GREATER_EQUAL, 'value' => 42]));
        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray(['type' => NumberOperatorType::TYPE_GREATER_THAN, 'value' => 42]));
        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray(['type' => NumberOperatorType::TYPE_LESS_EQUAL, 'value' => 42]));
        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray(['type' => NumberOperatorType::TYPE_LESS_THAN, 'value' => 42]));
        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray(['value' => 42]));

        $expected = [
            'WHERE alias.field = :field_name_0',
            'WHERE alias.field >= :field_name_1',
            'WHERE alias.field > :field_name_2',
            'WHERE alias.field <= :field_name_3',
            'WHERE alias.field < :field_name_4',
            'WHERE alias.field = :field_name_5',
        ];

        $this->assertSameQuery($expected, $proxyQuery);
        $this->assertTrue($filter->isActive());
    }
}
