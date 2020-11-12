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

use Sonata\AdminBundle\Form\Type\Operator\ContainsOperatorType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\StringListFilter;

class StringListFilterTest extends FilterTestCase
{
    public function testItStaysDisabledWhenFilteringWithAnEmptyValue(): void
    {
        $filter = new StringListFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($builder, 'alias', 'field', null);
        $filter->filter($builder, 'alias', 'field', '');

        $this->assertSame([], $builder->query);
        $this->assertFalse($filter->isActive());
    }

    public function testFilteringWithNullReturnsArraysThatContainNull(): void
    {
        $filter = new StringListFilter();
        $filter->initialize('field_name');

        $builder = new ProxyQuery($this->createQueryBuilderStub());
        $this->assertSame([], $builder->query);

        $filter->filter($builder, 'alias', 'field', ['value' => null, 'type' => null]);
        $this->assertSame(['alias.field LIKE :field_name_0'], $builder->query);
        $this->assertSame(['field_name_0' => '%N;%'], $builder->queryParameters);
        $this->assertTrue($filter->isActive());
    }

    /**
     * @dataProvider containsDataProvider
     */
    public function testContains(?int $type): void
    {
        $filter = new StringListFilter();
        $filter->initialize('field_name');

        $builder = new ProxyQuery($this->createQueryBuilderStub());
        $this->assertSame([], $builder->query);

        $filter->filter($builder, 'alias', 'field', ['value' => 'asd', 'type' => $type]);
        $this->assertSame(['alias.field LIKE :field_name_0'], $builder->query);
        $this->assertSame(['field_name_0' => '%s:3:"asd";%'], $builder->queryParameters);
        $this->assertTrue($filter->isActive());
    }

    public function containsDataProvider(): iterable
    {
        yield 'explicit contains' => [ContainsOperatorType::TYPE_CONTAINS];
        yield 'implicit contains' => [null];
    }

    public function testNotContains(): void
    {
        $filter = new StringListFilter();
        $filter->initialize('field_name');

        $builder = new ProxyQuery($this->createQueryBuilderStub());
        $this->assertSame([], $builder->query);

        $filter->filter($builder, 'alias', 'field', ['value' => 'asd', 'type' => ContainsOperatorType::TYPE_NOT_CONTAINS]);
        $this->assertSame(['alias.field NOT LIKE :field_name_0'], $builder->query);
        $this->assertSame(['field_name_0' => '%s:3:"asd";%'], $builder->queryParameters);
        $this->assertTrue($filter->isActive());
    }

    public function testEquals(): void
    {
        $filter = new StringListFilter();
        $filter->initialize('field_name');

        $builder = new ProxyQuery($this->createQueryBuilderStub());
        $this->assertSame([], $builder->query);

        $filter->filter($builder, 'alias', 'field', ['value' => 'asd', 'type' => ContainsOperatorType::TYPE_EQUAL]);
        $this->assertSame(['alias.field LIKE :field_name_0 AND alias.field LIKE \'a:1:%\''], $builder->query);
        $this->assertSame(['field_name_0' => '%s:3:"asd";%'], $builder->queryParameters);
        $this->assertTrue($filter->isActive());
    }

    /**
     * @param array<string>         $value
     * @param array<string>         $query
     * @param array<string, string> $parameters
     *
     * @dataProvider multipleValuesDataProvider
     */
    public function testMultipleValues(array $value, ?int $type, array $query, array $parameters): void
    {
        $filter = new StringListFilter();
        $filter->initialize('field_name');

        $builder = new ProxyQuery($this->createQueryBuilderStub());
        $this->assertSame([], $builder->query);

        $filter->filter($builder, 'alias', 'field', ['value' => $value, 'type' => $type]);
        $this->assertSame($query, $builder->query);
        $this->assertSame($parameters, $builder->queryParameters);
        $this->assertTrue($filter->isActive());
    }

    public function multipleValuesDataProvider(): iterable
    {
        yield 'equal choice' => [
            ['asd', 'qwe'],
            ContainsOperatorType::TYPE_EQUAL,
            ["alias.field LIKE :field_name_0 AND alias.field LIKE :field_name_1 AND alias.field LIKE 'a:2:%'"],
            [
                'field_name_0' => '%s:3:"asd";%',
                'field_name_1' => '%s:3:"qwe";%',
            ],
        ];

        yield 'contains choice' => [
            ['asd', 'qwe'],
            ContainsOperatorType::TYPE_CONTAINS,
            ['alias.field LIKE :field_name_0 AND alias.field LIKE :field_name_1'],
            [
                'field_name_0' => '%s:3:"asd";%',
                'field_name_1' => '%s:3:"qwe";%',
            ],
        ];

        yield 'not contains choice' => [
            ['asd', 'qwe'],
            ContainsOperatorType::TYPE_NOT_CONTAINS,
            ['alias.field NOT LIKE :field_name_0 AND alias.field NOT LIKE :field_name_1'],
            [
                'field_name_0' => '%s:3:"asd";%',
                'field_name_1' => '%s:3:"qwe";%',
            ],
        ];
    }
}
