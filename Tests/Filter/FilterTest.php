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

use Sonata\DoctrineORMAdminBundle\Filter\Filter;

class FilterTest_Filter extends Filter
{
    /**
     * Apply the filter to the QueryBuilder instance
     *
     * @param $queryBuilder
     * @param string $alias
     * @param string $field
     * @param string $value
     * @return void
     */
    public function filter($queryBuilder, $alias, $field, $value)
    {
        // TODO: Implement filter() method.
    }

    public function getDefaultOptions()
    {
        return array('option1' => 2);
    }

    public function getRenderSettings()
    {
        return array('sonata_type_filter_default', array(
            'type'    => $this->getFieldType(),
            'options' => $this->getFieldOptions()
        ));
    }

    public function testAssociation($queryBuilder, $value)
    {
        return $this->association($queryBuilder, $value);
    }
}

class FilterTest extends \PHPUnit_Framework_TestCase
{
    public function testFieldDescription()
    {
        $filter = new FilterTest_Filter();
        $this->assertEquals(array('option1' => 2), $filter->getDefaultOptions());
        $this->assertEquals(null, $filter->getOption('1'));

        $filter->initialize('field_name', array('field_options' => array('class' => 'FooBar')));

        $this->assertEquals(2, $filter->getOption('option1'));
        $this->assertEquals(null, $filter->getOption('foo'));
        $this->assertEquals('bar', $filter->getOption('foo', 'bar'));

        $this->assertEquals('field_name', $filter->getName());
        $this->assertEquals('text', $filter->getFieldType());
        $this->assertEquals(array('class' => 'FooBar'), $filter->getFieldOptions());
    }

    public function testValues()
    {
        $filter = new FilterTest_Filter();
        $this->assertEmpty($filter->getValue());

        $filter->setValue(42);
        $this->assertEquals(42, $filter->getValue());
    }

    public function testAliasOption()
    {
        $filter = new FilterTest_Filter();

        $filter->initialize('field_name', array('alias' => 'association_alias', 'field_name' => 'field_name'));

        $this->assertEquals('association_alias', $filter->getOption('alias'));

        $builder = new QueryBuilder;
        $this->assertEquals(array('association_alias', 'field_name'), $filter->testAssociation($builder, 'value'));

        $filter->initialize('field_name', array('field_name' => 'field_name'));
        $this->assertEquals(array($builder->getRootAlias(), 'field_name'), $filter->testAssociation($builder, 'value'));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testExceptionOnEmptyFieldName()
    {
        $filter = new FilterTest_Filter();
        $filter->getFieldName();
    }
}