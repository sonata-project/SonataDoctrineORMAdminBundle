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

use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\BooleanFilter;
use Sonata\Form\Type\BooleanType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class BooleanFilterTest extends FilterTestCase
{
    public function testRenderSettings(): void
    {
        $filter = new BooleanFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);
        $options = $filter->getRenderSettings()[1];

        $this->assertSame(HiddenType::class, $options['operator_type']);
        $this->assertSame([], $options['operator_options']);
    }

    public function testFilterEmpty(): void
    {
        $filter = new BooleanFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($builder, 'alias', 'field', null);
        $filter->filter($builder, 'alias', 'field', '');
        $filter->filter($builder, 'alias', 'field', 'test');
        $filter->filter($builder, 'alias', 'field', false);

        $filter->filter($builder, 'alias', 'field', []);
        $filter->filter($builder, 'alias', 'field', [null, 'test']);

        $this->assertSame([], $builder->query);
        $this->assertFalse($filter->isActive());
    }

    public function testFilterNo(): void
    {
        $filter = new BooleanFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($builder, 'alias', 'field', ['type' => null, 'value' => BooleanType::TYPE_NO]);

        $this->assertSame(['WHERE alias.field = :field_name_0'], $builder->query);
        $this->assertSame(['field_name_0' => 0], $builder->queryParameters);
        $this->assertTrue($filter->isActive());
    }

    public function testFilterYes(): void
    {
        $filter = new BooleanFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($builder, 'alias', 'field', ['type' => null, 'value' => BooleanType::TYPE_YES]);

        $this->assertSame(['WHERE alias.field = :field_name_0'], $builder->query);
        $this->assertSame(['field_name_0' => 1], $builder->queryParameters);
        $this->assertTrue($filter->isActive());
    }

    public function testFilterArray(): void
    {
        $filter = new BooleanFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($builder, 'alias', 'field', ['type' => null, 'value' => [BooleanType::TYPE_NO]]);

        $this->assertSame(['WHERE alias.field IN ("0")'], $builder->query);
        $this->assertTrue($filter->isActive());
    }
}
