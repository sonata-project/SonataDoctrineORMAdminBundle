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
use Sonata\DoctrineORMAdminBundle\Filter\ClassFilter;
use Sonata\Form\Type\EqualType;

class ClassFilterTest extends TestCase
{
    public function testRenderSettings(): void
    {
        $filter = new ClassFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);
        $options = $filter->getRenderSettings()[1];

        $this->assertSame(EqualType::class, $options['operator_type']);
        $this->assertSame([], $options['operator_options']);
    }

    public function testFilterEmpty(): void
    {
        $filter = new ClassFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', null);
        $filter->filter($builder, 'alias', 'field', 'asds');
        $filter->filter($builder, 'alias', 'field', ['value' => '']);

        $this->assertSame([], $builder->query);
        $this->assertFalse($filter->isActive());
    }

    public function testFilterInvalidOperator(): void
    {
        $filter = new ClassFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', ['type' => 'foo']);

        $this->assertSame([], $builder->query);
        $this->assertFalse($filter->isActive());
    }

    public function testFilter(): void
    {
        $filter = new ClassFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', ['type' => EqualType::TYPE_IS_EQUAL, 'value' => 'type']);
        $filter->filter($builder, 'alias', 'field', ['type' => EqualType::TYPE_IS_NOT_EQUAL, 'value' => 'type']);
        $filter->filter($builder, 'alias', 'field', ['value' => 'type']);

        $expected = [
            'alias INSTANCE OF type',
            'alias NOT INSTANCE OF type',
            'alias INSTANCE OF type',
        ];

        $this->assertSame($expected, $builder->query);
        $this->assertTrue($filter->isActive());
    }
}
