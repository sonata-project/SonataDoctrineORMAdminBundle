<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Tests\Filter;

use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\DateRangeFilter;
use Sonata\DoctrineORMAdminBundle\Tests\Helpers\PHPUnit_Framework_TestCase;

/**
 * @author Patrick Landolt <patrick.landolt@artack.ch>
 */
class DateRangeFilterTest extends PHPUnit_Framework_TestCase
{
    public function testFilterEmpty()
    {
        $filter = new DateRangeFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', null);
        $filter->filter($builder, 'alias', 'field', '');
        $filter->filter($builder, 'alias', 'field', 'test');
        $filter->filter($builder, 'alias', 'field', false);

        $filter->filter($builder, 'alias', 'field', []);
        $filter->filter($builder, 'alias', 'field', [null, 'test']);
        $filter->filter($builder, 'alias', 'field', ['type' => null, 'value' => []]);
        $filter->filter($builder, 'alias', 'field', [
            'type' => null,
            'value' => ['start' => null, 'end' => null],
        ]);
        $filter->filter($builder, 'alias', 'field', [
            'type' => null,
            'value' => ['start' => '', 'end' => ''],
        ]);

        $this->assertSame([], $builder->query);
        $this->assertFalse($filter->isActive());
    }

    public function testFilterStartDateAndEndDate()
    {
        $filter = new DateRangeFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery(new QueryBuilder());

        $startDateTime = new \DateTime('2016-08-01');
        $endDateTime = new \DateTime('2016-08-31');

        $filter->filter($builder, 'alias', 'field', [
            'type' => null,
            'value' => [
                'start' => $startDateTime,
                'end' => $endDateTime,
            ],
        ]);

        $this->assertSame(['alias.field >= :field_name_0', 'alias.field <= :field_name_1'], $builder->query);
        $this->assertSame([
            'field_name_0' => $startDateTime,
            'field_name_1' => $endDateTime,
        ], $builder->parameters);
        $this->assertTrue($filter->isActive());
    }

    public function testFilterStartDate()
    {
        $filter = new DateRangeFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery(new QueryBuilder());

        $startDateTime = new \DateTime('2016-08-01');

        $filter->filter($builder, 'alias', 'field', [
            'type' => null,
            'value' => [
                'start' => $startDateTime,
                'end' => '',
            ],
        ]);

        $this->assertSame(['alias.field >= :field_name_0'], $builder->query);
        $this->assertSame(['field_name_0' => $startDateTime], $builder->parameters);
        $this->assertTrue($filter->isActive());
    }

    public function testFilterEndDate()
    {
        $filter = new DateRangeFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery(new QueryBuilder());

        $endDateTime = new \DateTime('2016-08-31');

        $filter->filter($builder, 'alias', 'field', [
            'type' => null,
            'value' => [
                'start' => '',
                'end' => $endDateTime,
            ],
        ]);

        $this->assertSame(['alias.field <= :field_name_1'], $builder->query);
        $this->assertSame(['field_name_1' => $endDateTime], $builder->parameters);
        $this->assertTrue($filter->isActive());
    }
}
