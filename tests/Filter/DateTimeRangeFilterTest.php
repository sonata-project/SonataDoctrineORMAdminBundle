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

use PHPUnit\Framework\TestCase;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\DateTimeRangeFilter;
use Sonata\Form\Type\DateTimeRangeType;

/**
 * @author Marko Kunic <kunicmarko20@gmail.com>
 */
class DateTimeRangeFilterTest extends TestCase
{
    public function testEmpty(): void
    {
        $filter = new DateTimeRangeFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', null);
        $filter->filter($builder, 'alias', 'field', '');

        $this->assertSame([], $builder->query);
        $this->assertFalse($filter->isActive());
    }

    public function testGetType(): void
    {
        $this->assertSame(DateTimeRangeType::class, (new DateTimeRangeFilter())->getFieldType());
    }
}
