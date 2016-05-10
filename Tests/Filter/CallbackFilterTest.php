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

use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;

class CallbackFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testFilterClosure()
    {
        $builder = new ProxyQuery(new QueryBuilder());

        $filter = new CallbackFilter();
        $filter->initialize('field_name', array(
            'callback' => function ($builder, $alias, $field, $value) {
                $builder->andWhere(sprintf('CUSTOM QUERY %s.%s', $alias, $field));
                $builder->setParameter('value', $value);

                return true;
            },
        ));

        $filter->filter($builder, 'alias', 'field', 'myValue');

        $this->assertEquals(array('CUSTOM QUERY alias.field'), $builder->query);
        $this->assertEquals(array('value' => 'myValue'), $builder->parameters);
        $this->assertEquals(true, $filter->isActive());
    }

    public function testFilterMethod()
    {
        $builder = new ProxyQuery(new QueryBuilder());

        $filter = new CallbackFilter();
        $filter->initialize('field_name', array(
            'callback' => array($this, 'customCallback'),
        ));

        $filter->filter($builder, 'alias', 'field', 'myValue');

        $this->assertEquals(array('CUSTOM QUERY alias.field'), $builder->query);
        $this->assertEquals(array('value' => 'myValue'), $builder->parameters);
        $this->assertEquals(true, $filter->isActive());
    }

    public function customCallback($builder, $alias, $field, $value)
    {
        $builder->andWhere(sprintf('CUSTOM QUERY %s.%s', $alias, $field));
        $builder->setParameter('value', $value);

        return true;
    }

    /**
     * @expectedException RuntimeException
     */
    public function testFilterException()
    {
        $builder = new ProxyQuery(new QueryBuilder());

        $filter = new CallbackFilter();
        $filter->initialize('field_name', array());

        $filter->filter($builder, 'alias', 'field', 'myValue');
    }

    public function testApplyMethod()
    {
        $builder = new ProxyQuery(new QueryBuilder());

        $filter = new CallbackFilter();
        $filter->initialize('field_name_test', array(
            'callback' => function ($builder, $alias, $field, $value) {
                $builder->andWhere(sprintf('CUSTOM QUERY %s.%s', $alias, $field));
                $builder->setParameter('value', $value['value']);

                return true;
            },
            'field_name' => 'field_name_test',
        ));

        $filter->apply($builder, array('value' => 'myValue'));

        $this->assertEquals(array('CUSTOM QUERY o.field_name_test'), $builder->query);
        $this->assertEquals(array('value' => 'myValue'), $builder->parameters);
        $this->assertEquals(true, $filter->isActive());
    }
}
