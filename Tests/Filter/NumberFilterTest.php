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

use Sonata\AdminBundle\Form\Type\Filter\NumberType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\NumberFilter;

class NumberFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testFilterEmpty()
    {
        $filter = new NumberFilter();
        $filter->initialize('field_name', array('field_options' => array('class' => 'FooBar')));

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', null);
        $filter->filter($builder, 'alias', 'field', 'asds');

        $this->assertEquals(array(), $builder->query);
        $this->assertEquals(false, $filter->isActive());
    }

    public function testFilterInvalidOperator()
    {
        $filter = new NumberFilter();
        $filter->initialize('field_name', array('field_options' => array('class' => 'FooBar')));

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', array('type' => 'foo'));

        $this->assertEquals(array(), $builder->query);
        $this->assertEquals(false, $filter->isActive());
    }

    public function testFilter()
    {
        $filter = new NumberFilter();
        $filter->initialize('field_name', array('field_options' => array('class' => 'FooBar')));

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', array('type' => NumberType::TYPE_EQUAL, 'value' => 42));
        $filter->filter($builder, 'alias', 'field', array('type' => NumberType::TYPE_GREATER_EQUAL, 'value' => 42));
        $filter->filter($builder, 'alias', 'field', array('type' => NumberType::TYPE_GREATER_THAN, 'value' => 42));
        $filter->filter($builder, 'alias', 'field', array('type' => NumberType::TYPE_LESS_EQUAL, 'value' => 42));
        $filter->filter($builder, 'alias', 'field', array('type' => NumberType::TYPE_LESS_THAN, 'value' => 42));
        $filter->filter($builder, 'alias', 'field', array('value' => 42));

        $expected = array(
            'alias.field = :field_name_0',
            'alias.field >= :field_name_1',
            'alias.field > :field_name_2',
            'alias.field <= :field_name_3',
            'alias.field < :field_name_4',
            'alias.field = :field_name_5',
        );

        $this->assertEquals($expected, $builder->query);
        $this->assertEquals(true, $filter->isActive());
    }
}
