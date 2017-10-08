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

use Sonata\AdminBundle\Form\Type\Filter\ChoiceType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter;
use Sonata\DoctrineORMAdminBundle\Tests\Helpers\PHPUnit_Framework_TestCase;

class StringFilterTest extends PHPUnit_Framework_TestCase
{
    public function testEmpty()
    {
        $filter = new StringFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', null);
        $filter->filter($builder, 'alias', 'field', '');

        $this->assertEquals([], $builder->query);
        $this->assertEquals(false, $filter->isActive());
    }

    public function testContains()
    {
        $filter = new StringFilter();
        $filter->initialize('field_name', ['format' => '%s']);

        $builder = new ProxyQuery(new QueryBuilder());
        $this->assertEquals([], $builder->query);

        $filter->filter($builder, 'alias', 'field', ['value' => 'asd', 'type' => ChoiceType::TYPE_CONTAINS]);
        $this->assertEquals(['alias.field LIKE :field_name_0'], $builder->query);
        $this->assertEquals(['field_name_0' => 'asd'], $builder->parameters);

        $builder = new ProxyQuery(new QueryBuilder());
        $this->assertEquals([], $builder->query);

        $filter->filter($builder, 'alias', 'field', ['value' => 'asd', 'type' => null]);
        $this->assertEquals(['alias.field LIKE :field_name_0'], $builder->query);
        $this->assertEquals(['field_name_0' => 'asd'], $builder->parameters);
        $this->assertEquals(true, $filter->isActive());
    }

    public function testNotContains()
    {
        $filter = new StringFilter();
        $filter->initialize('field_name', ['format' => '%s']);

        $builder = new ProxyQuery(new QueryBuilder());
        $this->assertEquals([], $builder->query);

        $filter->filter($builder, 'alias', 'field', ['value' => 'asd', 'type' => ChoiceType::TYPE_NOT_CONTAINS]);
        $this->assertEquals(['alias.field NOT LIKE :field_name_0', 'alias.field IS NULL'], $builder->query[0]->getParts());
        $this->assertEquals(['field_name_0' => 'asd'], $builder->parameters);
        $this->assertEquals(true, $filter->isActive());
    }

    public function testEquals()
    {
        $filter = new StringFilter();
        $filter->initialize('field_name', ['format' => '%s']);

        $builder = new ProxyQuery(new QueryBuilder());
        $this->assertEquals([], $builder->query);

        $filter->filter($builder, 'alias', 'field', ['value' => 'asd', 'type' => ChoiceType::TYPE_EQUAL]);
        $this->assertEquals(['alias.field = :field_name_0'], $builder->query);
        $this->assertEquals(['field_name_0' => 'asd'], $builder->parameters);
        $this->assertEquals(true, $filter->isActive());
    }

    public function testEqualsWithValidParentAssociationMappings()
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
        $this->assertEquals([], $builder->query);

        $filter->apply($builder, ['type' => ChoiceType::TYPE_EQUAL, 'value' => 'asd']);

        $this->assertEquals([
            'o.association_mapping',
            's_association_mapping.sub_association_mapping',
            's_association_mapping_sub_association_mapping.sub_sub_association_mapping',
            's_association_mapping_sub_association_mapping_sub_sub_association_mapping.field_name = :field_name_0',
        ], $builder->query);
        $this->assertEquals(['field_name_0' => 'asd'], $builder->parameters);
        $this->assertEquals(true, $filter->isActive());
    }
}
