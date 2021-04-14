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

use Sonata\AdminBundle\Form\Type\Operator\StringOperatorType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter;

class StringFilterTest extends FilterTestCase
{
    public function testEmpty(): void
    {
        $filter = new StringFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($proxyQuery, 'alias', 'field', null);
        $filter->filter($proxyQuery, 'alias', 'field', '');
        $filter->filter($proxyQuery, 'alias', 'field', []);

        $this->assertSameQuery([], $proxyQuery);
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

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());
        $this->assertSameQuery([], $proxyQuery);

        $filter->filter($proxyQuery, 'alias', 'field', ['value' => $value, 'type' => null]);

        if ('' !== (string) $value) {
            $this->assertSameQuery(['WHERE alias.field LIKE :field_name_0'], $proxyQuery);
            $this->assertSameQueryParameters(['field_name_0' => sprintf('%%%s%%', $value)], $proxyQuery);
            $this->assertTrue($filter->isActive());
        } else {
            $this->assertSameQuery([], $proxyQuery);
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

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());
        $this->assertSameQuery([], $proxyQuery);

        $filter->filter($proxyQuery, 'alias', 'field', ['value' => $value, 'type' => StringOperatorType::TYPE_CONTAINS]);

        if ('' !== (string) $value) {
            $this->assertSameQuery(['WHERE alias.field LIKE :field_name_0'], $proxyQuery);
            $this->assertSameQueryParameters(['field_name_0' => sprintf('%%%s%%', $value)], $proxyQuery);
            $this->assertTrue($filter->isActive());
        } else {
            $this->assertSameQuery([], $proxyQuery);
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

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());
        $this->assertSameQuery([], $proxyQuery);

        $filter->filter($proxyQuery, 'alias', 'field', ['value' => $value, 'type' => StringOperatorType::TYPE_STARTS_WITH]);

        if ('' !== (string) $value) {
            $this->assertSameQuery(['WHERE alias.field LIKE :field_name_0'], $proxyQuery);
            $this->assertSameQueryParameters(['field_name_0' => sprintf('%s%%', $value)], $proxyQuery);
            $this->assertTrue($filter->isActive());
        } else {
            $this->assertSameQuery([], $proxyQuery);
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

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());
        $this->assertSameQuery([], $proxyQuery);

        $filter->filter($proxyQuery, 'alias', 'field', ['value' => $value, 'type' => StringOperatorType::TYPE_ENDS_WITH]);

        if ('' !== (string) $value) {
            $this->assertSameQuery(['WHERE alias.field LIKE :field_name_0'], $proxyQuery);
            $this->assertSameQueryParameters(['field_name_0' => sprintf('%%%s', $value)], $proxyQuery);
            $this->assertTrue($filter->isActive());
        } else {
            $this->assertSameQuery([], $proxyQuery);
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

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());
        $this->assertSameQuery([], $proxyQuery);

        $filter->filter($proxyQuery, 'alias', 'field', ['value' => $value, 'type' => StringOperatorType::TYPE_NOT_CONTAINS]);

        if ('' !== (string) $value) {
            $this->assertSameQuery(['WHERE alias.field NOT LIKE :field_name_0 OR alias.field IS NULL'], $proxyQuery);
            $this->assertSameQueryParameters(['field_name_0' => sprintf('%%%s%%', $value)], $proxyQuery);
            $this->assertTrue($filter->isActive());
        } else {
            $this->assertSameQuery([], $proxyQuery);
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

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());
        $this->assertSameQuery([], $proxyQuery);

        $filter->filter($proxyQuery, 'alias', 'field', ['value' => $value, 'type' => StringOperatorType::TYPE_EQUAL]);

        if ('' !== (string) $value || $allowEmpty) {
            $this->assertSameQuery(['WHERE alias.field = :field_name_0'], $proxyQuery);
            $this->assertSameQueryParameters(['field_name_0' => (string) ($value ?? '')], $proxyQuery);
            $this->assertTrue($filter->isActive());
        } else {
            $this->assertSameQuery([], $proxyQuery);
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

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());
        $this->assertSameQuery([], $proxyQuery);

        $filter->filter($proxyQuery, 'alias', 'field', ['value' => $value, 'type' => StringOperatorType::TYPE_NOT_EQUAL]);

        if ('' !== (string) $value || $allowEmpty) {
            $this->assertSameQuery(['WHERE alias.field <> :field_name_0 OR alias.field IS NULL'], $proxyQuery);
            $this->assertSameQueryParameters(['field_name_0' => (string) ($value ?? '')], $proxyQuery);
            $this->assertTrue($filter->isActive());
        } else {
            $this->assertSameQuery([], $proxyQuery);
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

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());
        $this->assertSameQuery([], $proxyQuery);

        $filter->apply($proxyQuery, ['type' => StringOperatorType::TYPE_EQUAL, 'value' => 'asd']);

        $this->assertSameQuery([
            'LEFT JOIN o.association_mapping AS s_association_mapping',
            'LEFT JOIN s_association_mapping.sub_association_mapping AS s_association_mapping_sub_association_mapping',
            'LEFT JOIN s_association_mapping_sub_association_mapping.sub_sub_association_mapping AS s_association_mapping_sub_association_mapping_sub_sub_association_mapping',
            'WHERE s_association_mapping_sub_association_mapping_sub_sub_association_mapping.field_name = :field_name_0',
        ], $proxyQuery);
        $this->assertSameQueryParameters(['field_name_0' => 'asd'], $proxyQuery);
        $this->assertTrue($filter->isActive());
    }

    /**
     * NEXT_MAJOR: Remove the "legacy" group annotation.
     *
     * @group legacy
     *
     * @dataProvider caseSensitiveDataProvider
     *
     * @param array<string, mixed> $options
     */
    public function testCaseSensitive(array $options, int $operatorType, string $expectedQuery, string $expectedParameter): void
    {
        $filter = new StringFilter();
        $filter->initialize('field_name', $options);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());
        $this->assertSameQuery([], $proxyQuery);

        $filter->filter($proxyQuery, 'alias', 'field', ['value' => 'FooBar', 'type' => $operatorType]);
        $this->assertSameQuery([$expectedQuery], $proxyQuery);
        $this->assertSameQueryParameters(['field_name_0' => $expectedParameter], $proxyQuery);
        $this->assertTrue($filter->isActive());
    }

    public function caseSensitiveDataProvider(): iterable
    {
        yield [[], StringOperatorType::TYPE_CONTAINS, 'WHERE alias.field LIKE :field_name_0', '%FooBar%'];
        yield [['force_case_insensitivity' => null], StringOperatorType::TYPE_CONTAINS, 'WHERE alias.field LIKE :field_name_0', '%FooBar%'];
        yield [['force_case_insensitivity' => false], StringOperatorType::TYPE_CONTAINS, 'WHERE alias.field LIKE :field_name_0', '%FooBar%'];
        yield [['force_case_insensitivity' => false], StringOperatorType::TYPE_NOT_CONTAINS, 'WHERE alias.field NOT LIKE :field_name_0 OR alias.field IS NULL', '%FooBar%'];
        yield [['force_case_insensitivity' => false], StringOperatorType::TYPE_EQUAL, 'WHERE alias.field = :field_name_0', 'FooBar'];
        yield [['force_case_insensitivity' => false], StringOperatorType::TYPE_NOT_EQUAL, 'WHERE alias.field <> :field_name_0 OR alias.field IS NULL', 'FooBar'];
        yield [['force_case_insensitivity' => false], StringOperatorType::TYPE_STARTS_WITH, 'WHERE alias.field LIKE :field_name_0', 'FooBar%'];
        yield [['force_case_insensitivity' => false], StringOperatorType::TYPE_ENDS_WITH, 'WHERE alias.field LIKE :field_name_0', '%FooBar'];
        yield [['force_case_insensitivity' => true], StringOperatorType::TYPE_CONTAINS, 'WHERE LOWER(alias.field) LIKE :field_name_0', '%foobar%'];
        yield [['force_case_insensitivity' => true], StringOperatorType::TYPE_NOT_CONTAINS, 'WHERE LOWER(alias.field) NOT LIKE :field_name_0 OR alias.field IS NULL', '%foobar%'];
        yield [['force_case_insensitivity' => true], StringOperatorType::TYPE_EQUAL, 'WHERE LOWER(alias.field) = :field_name_0', 'foobar'];
        yield [['force_case_insensitivity' => true], StringOperatorType::TYPE_NOT_EQUAL, 'WHERE LOWER(alias.field) <> :field_name_0 OR alias.field IS NULL', 'foobar'];
        yield [['force_case_insensitivity' => true], StringOperatorType::TYPE_STARTS_WITH, 'WHERE LOWER(alias.field) LIKE :field_name_0', 'foobar%'];
        yield [['force_case_insensitivity' => true], StringOperatorType::TYPE_ENDS_WITH, 'WHERE LOWER(alias.field) LIKE :field_name_0', '%foobar'];

        // NEXT_MAJOR: Remove the following test cases.
        yield [['force_case_insensitivity' => null, 'case_sensitive' => null], StringOperatorType::TYPE_CONTAINS, 'WHERE alias.field LIKE :field_name_0', '%FooBar%'];
        yield [['force_case_insensitivity' => null, 'case_sensitive' => false], StringOperatorType::TYPE_CONTAINS, 'WHERE LOWER(alias.field) LIKE :field_name_0', '%foobar%'];
        yield [['force_case_insensitivity' => null, 'case_sensitive' => true], StringOperatorType::TYPE_CONTAINS, 'WHERE alias.field LIKE :field_name_0', '%FooBar%'];
        yield [['force_case_insensitivity' => false, 'case_sensitive' => false], StringOperatorType::TYPE_CONTAINS, 'WHERE alias.field LIKE :field_name_0', '%FooBar%'];
        yield [['force_case_insensitivity' => true, 'case_sensitive' => true], StringOperatorType::TYPE_CONTAINS, 'WHERE LOWER(alias.field) LIKE :field_name_0', '%foobar%'];

        yield [['case_sensitive' => false], StringOperatorType::TYPE_CONTAINS, 'WHERE LOWER(alias.field) LIKE :field_name_0', '%foobar%'];
        yield [['case_sensitive' => false], StringOperatorType::TYPE_NOT_CONTAINS, 'WHERE LOWER(alias.field) NOT LIKE :field_name_0 OR alias.field IS NULL', '%foobar%'];
        yield [['case_sensitive' => false], StringOperatorType::TYPE_EQUAL, 'WHERE LOWER(alias.field) = :field_name_0', 'foobar'];
        yield [['case_sensitive' => false], StringOperatorType::TYPE_NOT_EQUAL, 'WHERE LOWER(alias.field) <> :field_name_0 OR alias.field IS NULL', 'foobar'];
        yield [['case_sensitive' => false], StringOperatorType::TYPE_STARTS_WITH, 'WHERE LOWER(alias.field) LIKE :field_name_0', 'foobar%'];
        yield [['case_sensitive' => false], StringOperatorType::TYPE_ENDS_WITH, 'WHERE LOWER(alias.field) LIKE :field_name_0', '%foobar'];
        yield [['case_sensitive' => true], StringOperatorType::TYPE_CONTAINS, 'WHERE alias.field LIKE :field_name_0', '%FooBar%'];
        yield [['case_sensitive' => true], StringOperatorType::TYPE_NOT_CONTAINS, 'WHERE alias.field NOT LIKE :field_name_0 OR alias.field IS NULL', '%FooBar%'];
        yield [['case_sensitive' => true], StringOperatorType::TYPE_EQUAL, 'WHERE alias.field = :field_name_0', 'FooBar'];
        yield [['case_sensitive' => true], StringOperatorType::TYPE_NOT_EQUAL, 'WHERE alias.field <> :field_name_0 OR alias.field IS NULL', 'FooBar'];
        yield [['case_sensitive' => true], StringOperatorType::TYPE_STARTS_WITH, 'WHERE alias.field LIKE :field_name_0', 'FooBar%'];
        yield [['case_sensitive' => true], StringOperatorType::TYPE_ENDS_WITH, 'WHERE alias.field LIKE :field_name_0', '%FooBar'];
    }

    /**
     * NEXT_MAJOR: Remove this test.
     *
     * @group legacy
     *
     * @expectedDeprecation The "format" option is deprecated since sonata-project/doctrine-orm-admin-bundle 3.21 and will be removed in version 4.0.
     */
    public function testFormatOption(): void
    {
        $filter = new StringFilter();
        $filter->initialize('field_name', ['format' => '%s']);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());
        $this->assertSameQuery([], $proxyQuery);

        $filter->filter($proxyQuery, 'alias', 'field', ['value' => 'asd', 'type' => StringOperatorType::TYPE_CONTAINS]);
        $this->assertSameQuery(['WHERE alias.field LIKE :field_name_0'], $proxyQuery);
        $this->assertSameQueryParameters(['field_name_0' => 'asd'], $proxyQuery);
    }
}
