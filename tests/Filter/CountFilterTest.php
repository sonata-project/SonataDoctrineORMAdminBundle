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
use Sonata\DoctrineORMAdminBundle\Filter\CountFilter;

class CountFilterTest extends FilterTestCase
{
    public function testFilterEmpty(): void
    {
        $filter = new CountFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($builder, 'alias', 'field', []);

        $this->assertSame([], $builder->query);
        $this->assertFalse($filter->isActive());
    }

    public function testFilterInvalidOperator(): void
    {
        $filter = new CountFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($builder, 'alias', 'field', ['type' => 'foo']);

        $this->assertSame([], $builder->query);
        $this->assertFalse($filter->isActive());
    }

    /**
     * @dataProvider filterDataProvider
     */
    public function testFilter(string $expected, ?int $type): void
    {
        $filter = new CountFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($builder, 'alias', 'field', ['type' => $type, 'value' => 42]);

        $this->assertSame(['GROUP BY o', $expected], $builder->query);
        $this->assertTrue($filter->isActive());
    }

    public function filterDataProvider(): array
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
