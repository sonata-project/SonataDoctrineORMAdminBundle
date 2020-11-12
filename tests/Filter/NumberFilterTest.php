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

use Sonata\AdminBundle\Form\Type\Operator\NumberOperatorType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\NumberFilter;

class NumberFilterTest extends FilterTestCase
{
    public function testFilterEmpty(): void
    {
        $filter = new NumberFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($builder, 'alias', 'field', null);
        $filter->filter($builder, 'alias', 'field', 'asds');

        $this->assertSame([], $builder->query);
        $this->assertFalse($filter->isActive());
    }

    public function testFilterInvalidOperator(): void
    {
        $filter = new NumberFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($builder, 'alias', 'field', ['type' => 'foo']);

        $this->assertSame([], $builder->query);
        $this->assertFalse($filter->isActive());
    }

    public function testFilter(): void
    {
        $filter = new NumberFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($builder, 'alias', 'field', ['type' => NumberOperatorType::TYPE_EQUAL, 'value' => 42]);
        $filter->filter($builder, 'alias', 'field', ['type' => NumberOperatorType::TYPE_GREATER_EQUAL, 'value' => 42]);
        $filter->filter($builder, 'alias', 'field', ['type' => NumberOperatorType::TYPE_GREATER_THAN, 'value' => 42]);
        $filter->filter($builder, 'alias', 'field', ['type' => NumberOperatorType::TYPE_LESS_EQUAL, 'value' => 42]);
        $filter->filter($builder, 'alias', 'field', ['type' => NumberOperatorType::TYPE_LESS_THAN, 'value' => 42]);
        $filter->filter($builder, 'alias', 'field', ['value' => 42]);

        $expected = [
            'alias.field = :field_name_0',
            'alias.field >= :field_name_1',
            'alias.field > :field_name_2',
            'alias.field <= :field_name_3',
            'alias.field < :field_name_4',
            'alias.field = :field_name_5',
        ];

        $this->assertSame($expected, $builder->query);
        $this->assertTrue($filter->isActive());
    }
}
