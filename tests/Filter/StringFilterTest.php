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
use Sonata\AdminBundle\Form\Type\Operator\StringOperatorType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter;

class StringFilterTest extends TestCase
{
    public function testEmpty(): void
    {
        $filter = new StringFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', null);
        $filter->filter($builder, 'alias', 'field', '');
        $filter->filter($builder, 'alias', 'field', []);

        $this->assertSame([], $builder->query);
        $this->assertFalse($filter->isActive());
    }

    public function getValues(): iterable
    {
        return [
            'filter by normal value' => ['asd', false],
            'not filter by empty string' => ['', false],
            'filter by empty string' => ['', true],
            'not filter by null' => [null, false],
            'filter by null' => [null, true],
            'not filter by 0' => [0, false],
            'filter by 0' => [0, true],
            'not filter by \'0\'' => ['0', false],
            'filter by \'0\'' => ['0', true],
        ];
    }

    /**
     * @dataProvider getValues
     */
    public function testDefaultType($value, bool $allowEmpty): void
    {
        $filter = new StringFilter();
        $filter->initialize('field_name', ['allow_empty' => $allowEmpty]);

        $builder = new ProxyQuery(new QueryBuilder());
        $this->assertSame([], $builder->query);

        $filter->filter($builder, 'alias', 'field', ['value' => $value, 'type' => null]);

        if ('' !== (string) $value) {
            $this->assertSame(['alias.field LIKE :field_name_0'], $builder->query);
            $this->assertSame(['field_name_0' => sprintf('%%%s%%', $value)], $builder->parameters);
            $this->assertTrue($filter->isActive());
        } else {
            $this->assertSame([], $builder->query);
            $this->assertFalse($filter->isActive());
        }
    }

    /**
     * @dataProvider getValues
     */
    public function testContains($value, bool $allowEmpty): void
    {
        $filter = new StringFilter();
        $filter->initialize('field_name', ['allow_empty' => $allowEmpty]);

        $builder = new ProxyQuery(new QueryBuilder());
        $this->assertSame([], $builder->query);

        $filter->filter($builder, 'alias', 'field', ['value' => $value, 'type' => StringOperatorType::TYPE_CONTAINS]);

        if ('' !== (string) $value) {
            $this->assertSame(['alias.field LIKE :field_name_0'], $builder->query);
            $this->assertSame(['field_name_0' => sprintf('%%%s%%', $value)], $builder->parameters);
            $this->assertTrue($filter->isActive());
        } else {
            $this->assertSame([], $builder->query);
            $this->assertFalse($filter->isActive());
        }
    }

    /**
     * @dataProvider getValues
     */
    public function testStartsWith($value, bool $allowEmpty): void
    {
        $filter = new StringFilter();
        $filter->initialize('field_name', ['allow_empty' => $allowEmpty]);

        $builder = new ProxyQuery(new QueryBuilder());
        $this->assertSame([], $builder->query);

        $filter->filter($builder, 'alias', 'field', ['value' => $value, 'type' => StringOperatorType::TYPE_STARTS_WITH]);

        if ('' !== (string) $value) {
            $this->assertSame(['alias.field LIKE :field_name_0'], $builder->query);
            $this->assertSame(['field_name_0' => sprintf('%s%%', $value)], $builder->parameters);
            $this->assertTrue($filter->isActive());
        } else {
            $this->assertSame([], $builder->query);
            $this->assertFalse($filter->isActive());
        }
    }

    /**
     * @dataProvider getValues
     */
    public function testEndsWith($value, bool $allowEmpty): void
    {
        $filter = new StringFilter();
        $filter->initialize('field_name', ['allow_empty' => $allowEmpty]);

        $builder = new ProxyQuery(new QueryBuilder());
        $this->assertSame([], $builder->query);

        $filter->filter($builder, 'alias', 'field', ['value' => $value, 'type' => StringOperatorType::TYPE_ENDS_WITH]);

        if ('' !== (string) $value) {
            $this->assertSame(['alias.field LIKE :field_name_0'], $builder->query);
            $this->assertSame(['field_name_0' => sprintf('%%%s', $value)], $builder->parameters);
            $this->assertTrue($filter->isActive());
        } else {
            $this->assertSame([], $builder->query);
            $this->assertFalse($filter->isActive());
        }
    }

    /**
     * @dataProvider getValues
     */
    public function testNotContains($value, bool $allowEmpty): void
    {
        $filter = new StringFilter();
        $filter->initialize('field_name', ['allow_empty' => $allowEmpty]);

        $builder = new ProxyQuery(new QueryBuilder());
        $this->assertSame([], $builder->query);

        $filter->filter($builder, 'alias', 'field', ['value' => $value, 'type' => StringOperatorType::TYPE_NOT_CONTAINS]);

        if ('' !== (string) $value) {
            $this->assertSame(['alias.field NOT LIKE :field_name_0 OR alias.field IS NULL'], $builder->query);
            $this->assertSame(['field_name_0' => sprintf('%%%s%%', $value)], $builder->parameters);
            $this->assertTrue($filter->isActive());
        } else {
            $this->assertSame([], $builder->query);
            $this->assertFalse($filter->isActive());
        }
    }

    /**
     * @dataProvider getValues
     */
    public function testEquals($value, bool $allowEmpty): void
    {
        $filter = new StringFilter();
        $filter->initialize('field_name', ['allow_empty' => $allowEmpty]);

        $builder = new ProxyQuery(new QueryBuilder());
        $this->assertSame([], $builder->query);

        $filter->filter($builder, 'alias', 'field', ['value' => $value, 'type' => StringOperatorType::TYPE_EQUAL]);

        if ('' !== (string) $value || $allowEmpty) {
            $this->assertSame(['alias.field = :field_name_0'], $builder->query);
            $this->assertSame(['field_name_0' => (string) ($value ?? '')], $builder->parameters);
            $this->assertTrue($filter->isActive());
        } else {
            $this->assertSame([], $builder->query);
            $this->assertFalse($filter->isActive());
        }
    }

    /**
     * @dataProvider getValues
     */
    public function testNotEquals($value, bool $allowEmpty): void
    {
        $filter = new StringFilter();
        $filter->initialize('field_name', ['allow_empty' => $allowEmpty]);

        $builder = new ProxyQuery(new QueryBuilder());
        $this->assertSame([], $builder->query);

        $filter->filter($builder, 'alias', 'field', ['value' => $value, 'type' => StringOperatorType::TYPE_NOT_EQUAL]);

        if ('' !== (string) $value || $allowEmpty) {
            $this->assertSame(['alias.field <> :field_name_0 OR alias.field IS NULL'], $builder->query);
            $this->assertSame(['field_name_0' => (string) ($value ?? '')], $builder->parameters);
            $this->assertTrue($filter->isActive());
        } else {
            $this->assertSame([], $builder->query);
            $this->assertFalse($filter->isActive());
        }
    }

    public function testEqualsWithValidParentAssociationMappings(): void
    {
        $filter = new StringFilter();
        $filter->initialize('field_name', [
            'field_name' => 'field_name',
            'parent_association_mappings' => [
                [
                    'fieldName' => 'association_mapping',
                ],
                [
                    'fieldName' => 'sub_association_mapping',
                ],
                [
                    'fieldName' => 'sub_sub_association_mapping',
                ],
            ],
        ]);

        $builder = new ProxyQuery(new QueryBuilder());
        $this->assertSame([], $builder->query);

        $filter->apply($builder, ['type' => StringOperatorType::TYPE_EQUAL, 'value' => 'asd']);

        $this->assertSame(
            'o.association_mapping',
            $builder->query[0]
        );
        $this->assertSame(
            's_association_mapping.sub_association_mapping',
            $builder->query[1]
        );
        $this->assertSame(
            's_association_mapping_sub_association_mapping.sub_sub_association_mapping',
            $builder->query[2]
        );
        $this->assertSame(
            's_association_mapping_sub_association_mapping_sub_sub_association_mapping.field_name = :field_name_0',
            $builder->query[3]
        );
        $this->assertSame(['field_name_0' => 'asd'], $builder->parameters);
        $this->assertTrue($filter->isActive());
    }

    /**
     * @dataProvider caseSensitiveDataProvider
     */
    public function testCaseSensitive(bool $caseSensitive, int $operatorType, string $expectedQuery, string $expectedParameter): void
    {
        $filter = new StringFilter();
        $filter->initialize('field_name', ['case_sensitive' => $caseSensitive]);

        $builder = new ProxyQuery(new QueryBuilder());
        $this->assertSame([], $builder->query);

        $filter->filter($builder, 'alias', 'field', ['value' => 'FooBar', 'type' => $operatorType]);
        $this->assertSame([$expectedQuery], $builder->query);
        $this->assertSame(['field_name_0' => $expectedParameter], $builder->parameters);
        $this->assertTrue($filter->isActive());
    }

    public function caseSensitiveDataProvider(): array
    {
        return [
            [false, StringOperatorType::TYPE_CONTAINS, 'LOWER(alias.field) LIKE :field_name_0', '%foobar%'],
            [false, StringOperatorType::TYPE_NOT_CONTAINS, 'LOWER(alias.field) NOT LIKE :field_name_0 OR alias.field IS NULL', '%foobar%'],
            [false, StringOperatorType::TYPE_EQUAL, 'LOWER(alias.field) = :field_name_0', 'foobar'],
            [false, StringOperatorType::TYPE_NOT_EQUAL, 'LOWER(alias.field) <> :field_name_0 OR alias.field IS NULL', 'foobar'],
            [false, StringOperatorType::TYPE_STARTS_WITH, 'LOWER(alias.field) LIKE :field_name_0', 'foobar%'],
            [false, StringOperatorType::TYPE_ENDS_WITH, 'LOWER(alias.field) LIKE :field_name_0', '%foobar'],
            [true, StringOperatorType::TYPE_CONTAINS, 'alias.field LIKE :field_name_0', '%FooBar%'],
            [true, StringOperatorType::TYPE_NOT_CONTAINS, 'alias.field NOT LIKE :field_name_0 OR alias.field IS NULL', '%FooBar%'],
            [true, StringOperatorType::TYPE_EQUAL, 'alias.field = :field_name_0', 'FooBar'],
            [true, StringOperatorType::TYPE_NOT_EQUAL, 'alias.field <> :field_name_0 OR alias.field IS NULL', 'FooBar'],
            [true, StringOperatorType::TYPE_STARTS_WITH, 'alias.field LIKE :field_name_0', 'FooBar%'],
            [true, StringOperatorType::TYPE_ENDS_WITH, 'alias.field LIKE :field_name_0', '%FooBar'],
        ];
    }
}
