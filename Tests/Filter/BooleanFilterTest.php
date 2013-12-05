<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Tests\Filter;

use Sonata\DoctrineORMAdminBundle\Filter\BooleanFilter;
use Sonata\CoreBundle\Form\Type\BooleanType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;

class BooleanFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testFilterEmpty()
    {
        $filter = new BooleanFilter;
        $filter->initialize('field_name', array('field_options' => array('class' => 'FooBar')));

        $builder = new ProxyQuery(new QueryBuilder);

        $filter->filter($builder, 'alias', 'field', null);
        $filter->filter($builder, 'alias', 'field', '');
        $filter->filter($builder, 'alias', 'field', 'test');
        $filter->filter($builder, 'alias', 'field', false);

        $filter->filter($builder, 'alias', 'field', array());
        $filter->filter($builder, 'alias', 'field', array(null, 'test'));

        $this->assertEquals(array(), $builder->query);
        $this->assertEquals(false, $filter->isActive());
    }

    public function testFilterNo()
    {
        $filter = new BooleanFilter;
        $filter->initialize('field_name', array('field_options' => array('class' => 'FooBar')));

        $builder = new ProxyQuery(new QueryBuilder);

        $filter->filter($builder, 'alias', 'field', array('type' => null, 'value' => BooleanType::TYPE_NO));

        $this->assertEquals(array('alias.field = :field_name_0'), $builder->query);
        $this->assertEquals(array('field_name_0' => 0), $builder->parameters);
        $this->assertEquals(true, $filter->isActive());
    }

    public function testFilterYes()
    {
        $filter = new BooleanFilter;
        $filter->initialize('field_name', array('field_options' => array('class' => 'FooBar')));

        $builder = new ProxyQuery(new QueryBuilder);

        $filter->filter($builder, 'alias', 'field', array('type' => null, 'value' => BooleanType::TYPE_YES));

        $this->assertEquals(array('alias.field = :field_name_0'), $builder->query);
        $this->assertEquals(array('field_name_0' => 1), $builder->parameters);
        $this->assertEquals(true, $filter->isActive());
    }

    public function testFilterArray()
    {
        $filter = new BooleanFilter;
        $filter->initialize('field_name', array('field_options' => array('class' => 'FooBar')));

        $builder = new ProxyQuery(new QueryBuilder);

        $filter->filter($builder, 'alias', 'field', array('type' => null, 'value' => array(BooleanType::TYPE_NO)));

        $this->assertEquals(array('in_alias.field', 'alias.field IN ("0")'), $builder->query);
        $this->assertEquals(true, $filter->isActive());
    }
}
