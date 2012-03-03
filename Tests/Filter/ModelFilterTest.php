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

use Sonata\DoctrineORMAdminBundle\Filter\ModelFilter;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sonata\AdminBundle\Form\Type\Filter\ChoiceType;

class ModelFilterTest extends \PHPUnit_Framework_TestCase
{
    public function getFieldDescription(array $options)
    {
        $fieldDescription = $this->getMock('Sonata\AdminBundle\Admin\FieldDescriptionInterface');
        $fieldDescription->expects($this->once())
            ->method('getOptions')
            ->will($this->returnValue($options));

        $fieldDescription->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('field_name'));

        return $fieldDescription;
    }

    public function testFilterEmpty()
    {
        $filter = new ModelFilter;
        $filter->initialize('field_name', array('field_options' => array('class' => 'FooBar')));

        $builder = new QueryBuilder;

        $filter->filter($builder, 'alias', 'field', null);
        $filter->filter($builder, 'alias', 'field', array());

        $this->assertEquals(array(), $builder->query);
        $this->assertEquals(false, $filter->isActive());
    }

    public function testFilterArray()
    {
        $filter = new ModelFilter;
        $filter->initialize('field_name', array('field_options' => array('class' => 'FooBar')));

        $builder = new QueryBuilder;

        $filter->filter($builder, 'alias', 'field', array('type' => ChoiceType::TYPE_CONTAINS, 'value' => array('1', '2')));

        $this->assertEquals(array('in_alias.field', 'alias.field IN ("1,2")'), $builder->query);
        $this->assertEquals(true, $filter->isActive());
    }

    public function testFilterScalar()
    {
        $filter = new ModelFilter;
        $filter->initialize('field_name', array('field_options' => array('class' => 'FooBar')));

        $builder = new QueryBuilder;

        $filter->filter($builder, 'alias', 'field', array('type' => ChoiceType::TYPE_CONTAINS, 'value' => 2));

        $this->assertEquals(array('alias.field = :field_name'), $builder->query);
        $this->assertEquals(array('field_name' => 2), $builder->parameters);
        $this->assertEquals(true, $filter->isActive());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testAssociationWithInvalidMapping()
    {
        $filter = new ModelFilter;
        $filter->initialize('field_name', array('mapping_type' => 'foo'));

        $filter->apply(new QueryBuilder, 'asd');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testAssociationWithValidMappingAndEmptyFieldName()
    {
        $filter = new ModelFilter;
        $filter->initialize('field_name', array('mapping_type' => ClassMetadataInfo::ONE_TO_ONE));

        $filter->apply(new QueryBuilder, 'asd');
        $this->assertEquals(true, $filter->isActive());
    }

    public function testAssociationWithValidMapping()
    {
        $filter = new ModelFilter;
        $filter->initialize('field_name', array(
            'mapping_type' => ClassMetadataInfo::ONE_TO_ONE,
            'field_name' => 'field_name',
        ));

        $builder = new QueryBuilder;

        $filter->apply($builder, array('type' => ChoiceType::TYPE_CONTAINS, 'value' => 'asd'));

        $this->assertEquals(array('o.field_name', 's_field_name.id = :field_name'), $builder->query);
        $this->assertEquals(true, $filter->isActive());
    }
}
