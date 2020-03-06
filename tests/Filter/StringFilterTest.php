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
use Sonata\AdminBundle\Form\Type\Filter\ChoiceType;
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

        $this->assertSame([], $builder->query);
        $this->assertFalse($filter->isActive());
    }

    public function testNullValue(): void
    {
        $filter = new StringFilter();
        $filter->initialize('field_name', ['format' => '%s']);

        $builder = new ProxyQuery(new QueryBuilder());
        $this->assertSame([], $builder->query);

        $filter->filter($builder, 'alias', 'field', ['value' => null, 'type' => ChoiceType::TYPE_EQUAL]);
        $this->assertFalse($filter->isActive());
    }

    public function testContains(): void
    {
        $filter = new StringFilter();
        $filter->initialize('field_name', ['format' => '%s']);

        $builder = new ProxyQuery(new QueryBuilder());
        $this->assertSame([], $builder->query);

        $filter->filter($builder, 'alias', 'field', ['value' => 'asd', 'type' => ChoiceType::TYPE_CONTAINS]);
        $this->assertSame(['alias.field LIKE :field_name_0'], $builder->query);
        $this->assertSame(['field_name_0' => 'asd'], $builder->parameters);

        $builder = new ProxyQuery(new QueryBuilder());
        $this->assertSame([], $builder->query);

        $filter->filter($builder, 'alias', 'field', ['value' => 'asd', 'type' => null]);
        $this->assertSame(['alias.field LIKE :field_name_0'], $builder->query);
        $this->assertSame(['field_name_0' => 'asd'], $builder->parameters);
        $this->assertTrue($filter->isActive());
    }

    public function testNotContains(): void
    {
        $filter = new StringFilter();
        $filter->initialize('field_name', ['format' => '%s']);

        $builder = new ProxyQuery(new QueryBuilder());
        $this->assertSame([], $builder->query);

        $filter->filter($builder, 'alias', 'field', ['value' => 'asd', 'type' => ChoiceType::TYPE_NOT_CONTAINS]);
        $this->assertSame(['alias.field NOT LIKE :field_name_0 OR alias.field IS NULL'], $builder->query);
        $this->assertSame(['field_name_0' => 'asd'], $builder->parameters);
        $this->assertTrue($filter->isActive());
    }

    public function testEquals(): void
    {
        $filter = new StringFilter();
        $filter->initialize('field_name', ['format' => '%s']);

        $builder = new ProxyQuery(new QueryBuilder());
        $this->assertSame([], $builder->query);

        $filter->filter($builder, 'alias', 'field', ['value' => 'asd', 'type' => ChoiceType::TYPE_EQUAL]);
        $this->assertSame(['alias.field = :field_name_0'], $builder->query);
        $this->assertSame(['field_name_0' => 'asd'], $builder->parameters);
        $this->assertTrue($filter->isActive());
    }

    public function testEqualsWithValidParentAssociationMappings(): void
    {
        $filter = new StringFilter();
        $filter->initialize('field_name', [
            'format' => '%s',
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

        $filter->apply($builder, ['type' => ChoiceType::TYPE_EQUAL, 'value' => 'asd']);

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

    public function testCaseSensitiveFalse(): void
    {
        $filter = new StringFilter();
        $filter->initialize('field_name', ['case_sensitive' => false]);

        $builder = new ProxyQuery(new QueryBuilder());
        $this->assertSame([], $builder->query);

        $filter->filter($builder, 'alias', 'field', ['value' => 'FooBar', 'type' => ChoiceType::TYPE_CONTAINS]);
        $this->assertSame(['LOWER(alias.field) LIKE :field_name_0'], $builder->query);
        $this->assertSame(['field_name_0' => '%foobar%'], $builder->parameters);
        $this->assertTrue($filter->isActive());
    }

    public function testCaseSensitiveTrue(): void
    {
        $filter = new StringFilter();
        $filter->initialize('field_name', ['case_sensitive' => true]);

        $builder = new ProxyQuery(new QueryBuilder());
        $this->assertSame([], $builder->query);

        $filter->filter($builder, 'alias', 'field', ['value' => 'FooBar', 'type' => ChoiceType::TYPE_CONTAINS]);
        $this->assertSame(['alias.field LIKE :field_name_0'], $builder->query);
        $this->assertSame(['field_name_0' => '%FooBar%'], $builder->parameters);
        $this->assertTrue($filter->isActive());
    }
}
