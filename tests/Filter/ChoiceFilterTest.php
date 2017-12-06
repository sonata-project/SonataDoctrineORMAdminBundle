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

use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Form\Type\Filter\ChoiceType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\ChoiceFilter;

class ChoiceFilterTest extends TestCase
{
    public function testFilterEmpty()
    {
        $filter = new ChoiceFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', null);
        $filter->filter($builder, 'alias', 'field', 'all');
        $filter->filter($builder, 'alias', 'field', []);

        $this->assertEquals([], $builder->query);
        $this->assertFalse($filter->isActive());
    }

    public function testFilterArray()
    {
        $filter = new ChoiceFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', ['type' => ChoiceType::TYPE_CONTAINS, 'value' => ['1', '2']]);

        $this->assertEquals(['in_alias.field', 'in_alias.field IN :field_name_0'], $builder->query);
        $this->assertEquals(['field_name_0' => ['1', '2']], $builder->parameters);
        $this->assertTrue($filter->isActive());
    }

    public function testFilterScalar()
    {
        $filter = new ChoiceFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', ['type' => ChoiceType::TYPE_CONTAINS, 'value' => '1']);

        $this->assertEquals(['alias.field = :field_name_0'], $builder->query);
        $this->assertEquals(['field_name_0' => '1'], $builder->parameters);
        $this->assertTrue($filter->isActive());
    }

    public function testFilterZero()
    {
        $filter = new ChoiceFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', ['type' => ChoiceType::TYPE_CONTAINS, 'value' => 0]);

        $this->assertEquals(['alias.field = :field_name_0'], $builder->query);
        $this->assertEquals(['field_name_0' => 0], $builder->parameters);
        $this->assertTrue($filter->isActive());
    }
}
