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
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;

class CallbackFilterTest extends TestCase
{
    public function testFilterClosure(): void
    {
        $builder = new ProxyQuery(new QueryBuilder());

        $filter = new CallbackFilter();
        $filter->initialize('field_name', [
            'callback' => function ($builder, $alias, $field, $value) {
                $builder->andWhere(sprintf('CUSTOM QUERY %s.%s', $alias, $field));
                $builder->setParameter('value', $value);

                return true;
            },
        ]);

        $filter->filter($builder, 'alias', 'field', 'myValue');

        $this->assertSame(['CUSTOM QUERY alias.field'], $builder->query);
        $this->assertSame(['value' => 'myValue'], $builder->parameters);
        $this->assertTrue($filter->isActive());
    }

    public function testFilterMethod(): void
    {
        $builder = new ProxyQuery(new QueryBuilder());

        $filter = new CallbackFilter();
        $filter->initialize('field_name', [
            'callback' => [$this, 'customCallback'],
        ]);

        $filter->filter($builder, 'alias', 'field', 'myValue');

        $this->assertSame(['CUSTOM QUERY alias.field'], $builder->query);
        $this->assertSame(['value' => 'myValue'], $builder->parameters);
        $this->assertTrue($filter->isActive());
    }

    public function customCallback($builder, $alias, $field, $value)
    {
        $builder->andWhere(sprintf('CUSTOM QUERY %s.%s', $alias, $field));
        $builder->setParameter('value', $value);

        return true;
    }

    public function testFilterException(): void
    {
        $this->expectException(\RuntimeException::class);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter = new CallbackFilter();
        $filter->initialize('field_name', []);

        $filter->filter($builder, 'alias', 'field', 'myValue');
    }

    public function testApplyMethod(): void
    {
        $builder = new ProxyQuery(new QueryBuilder());

        $filter = new CallbackFilter();
        $filter->initialize('field_name_test', [
            'callback' => function ($builder, $alias, $field, $value) {
                $builder->andWhere(sprintf('CUSTOM QUERY %s.%s', $alias, $field));
                $builder->setParameter('value', $value['value']);

                return true;
            },
            'field_name' => 'field_name_test',
        ]);

        $filter->apply($builder, ['value' => 'myValue']);

        $this->assertSame(['CUSTOM QUERY o.field_name_test'], $builder->query);
        $this->assertSame(['value' => 'myValue'], $builder->parameters);
        $this->assertTrue($filter->isActive());
    }
}
