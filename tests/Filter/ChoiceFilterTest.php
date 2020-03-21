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
use Sonata\AdminBundle\Form\Type\Operator\EqualOperatorType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\ChoiceFilter;

class ChoiceFilterTest extends TestCase
{
    public function testRenderSettings(): void
    {
        $filter = new ChoiceFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);
        $options = $filter->getRenderSettings()[1];

        $this->assertSame(EqualOperatorType::class, $options['operator_type']);
        $this->assertSame([], $options['operator_options']);
    }

    public function testFilterEmpty(): void
    {
        $filter = new ChoiceFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', null);
        $filter->filter($builder, 'alias', 'field', 'all');
        $filter->filter($builder, 'alias', 'field', []);

        $this->assertSame([], $builder->query);
        $this->assertFalse($filter->isActive());
    }

    public function testFilterArray(): void
    {
        $filter = new ChoiceFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', ['type' => EqualOperatorType::TYPE_EQUAL, 'value' => ['1', '2']]);

        $this->assertSame(['alias.field IN :field_name_0'], $builder->query);
        $this->assertSame(['field_name_0' => ['1', '2']], $builder->parameters);
        $this->assertTrue($filter->isActive());
    }

    public function testFilterArrayWithNullValue(): void
    {
        $filter = new ChoiceFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', ['type' => EqualOperatorType::TYPE_EQUAL, 'value' => ['1', null]]);

        $this->assertSame(['alias.field IN :field_name_0 OR alias.field IS NULL'], $builder->query);
        $this->assertSame(['field_name_0' => ['1']], $builder->parameters);
        $this->assertTrue($filter->isActive());

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', ['type' => EqualOperatorType::TYPE_NOT_EQUAL, 'value' => ['1', null]]);

        $this->assertSame(['alias.field IS NOT NULL AND alias.field NOT IN :field_name_0'], $builder->query);
        $this->assertSame(['field_name_0' => ['1']], $builder->parameters);
        $this->assertTrue($filter->isActive());
    }

    public function testFilterScalar(): void
    {
        $filter = new ChoiceFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', ['type' => EqualOperatorType::TYPE_EQUAL, 'value' => '1']);

        $this->assertSame(['alias.field = :field_name_0'], $builder->query);
        $this->assertSame(['field_name_0' => '1'], $builder->parameters);
        $this->assertTrue($filter->isActive());
    }

    public function testFilterNull(): void
    {
        $filter = new ChoiceFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', ['type' => EqualOperatorType::TYPE_EQUAL, 'value' => null]);

        $this->assertSame(['alias.field IS NULL'], $builder->query);
        $this->assertSame([], $builder->parameters);
        $this->assertTrue($filter->isActive());

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', ['type' => EqualOperatorType::TYPE_NOT_EQUAL, 'value' => null]);

        $this->assertSame(['alias.field IS NOT NULL'], $builder->query);
        $this->assertSame([], $builder->parameters);
        $this->assertTrue($filter->isActive());
    }

    public function testFilterZero(): void
    {
        $filter = new ChoiceFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', ['type' => EqualOperatorType::TYPE_EQUAL, 'value' => 0]);

        $this->assertSame(['alias.field = :field_name_0'], $builder->query);
        $this->assertSame(['field_name_0' => 0], $builder->parameters);
        $this->assertTrue($filter->isActive());
    }
}
