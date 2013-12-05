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
use Sonata\CoreBundle\Form\Type\EqualType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;

class ModelFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param  array                                               $options
     * @return \Sonata\AdminBundle\Admin\FieldDescriptionInterface
     */
    public function getFieldDescription(array $options)
    {
        $fieldDescription = $this->getMock('Sonata\AdminBundle\Admin\FieldDescriptionInterface');
        $fieldDescription->expects($this->once())->method('getOptions')->will($this->returnValue($options));
        $fieldDescription->expects($this->once())->method('getName')->will($this->returnValue('field_name'));

        return $fieldDescription;
    }

    public function testFilterEmpty()
    {
        $filter = new ModelFilter;
        $filter->initialize('field_name', array('field_options' => array('class' => 'FooBar')));

        $builder = new ProxyQuery(new QueryBuilder);

        $filter->filter($builder, 'alias', 'field', null);
        $filter->filter($builder, 'alias', 'field', array());

        $this->assertEquals(array(), $builder->query);
        $this->assertEquals(false, $filter->isActive());
    }

    public function testFilterArray()
    {
        $filter = new ModelFilter;
        $filter->initialize('field_name', array('field_options' => array('class' => 'FooBar')));

        $builder = new ProxyQuery(new QueryBuilder);

        $filter->filter($builder, 'alias', 'field', array(
            'type' => EqualType::TYPE_IS_EQUAL,
            'value' => array('1', '2')
        ));

        // the alias is now computer by the entityJoin method
        $this->assertEquals(array('in_alias', 'in_alias IN :field_name_0'), $builder->query);
        $this->assertEquals(true, $filter->isActive());
    }

    public function testFilterScalar()
    {
        $filter = new ModelFilter;
        $filter->initialize('field_name', array('field_options' => array('class' => 'FooBar')));

        $builder = new ProxyQuery(new QueryBuilder);

        $filter->filter($builder, 'alias', 'field', array('type' => EqualType::TYPE_IS_EQUAL, 'value' => 2));

        $this->assertEquals(array('alias = :field_name_0'), $builder->query);
        $this->assertEquals(array('field_name_0' => 2), $builder->parameters);
        $this->assertEquals(true, $filter->isActive());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testAssociationWithInvalidMapping()
    {
        $filter = new ModelFilter;
        $filter->initialize('field_name', array('mapping_type' => 'foo'));

        $builder = new ProxyQuery(new QueryBuilder);

        $filter->apply($builder, array('value' => 'asd'));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testAssociationWithValidMappingAndEmptyFieldName()
    {
        $filter = new ModelFilter;
        $filter->initialize('field_name', array('mapping_type' => ClassMetadataInfo::ONE_TO_ONE));

        $builder = new ProxyQuery(new QueryBuilder);

        $filter->apply($builder, array('value' => 'asd'));
        $this->assertEquals(true, $filter->isActive());
    }

    public function testAssociationWithValidMapping()
    {
        $filter = new ModelFilter;
        $filter->initialize('field_name', array(
            'mapping_type' => ClassMetadataInfo::ONE_TO_ONE,
            'field_name' => 'field_name',
            'association_mapping' => array(
                'fieldName' => 'association_mapping'
            )
        ));

        $builder = new ProxyQuery(new QueryBuilder);

        $filter->apply($builder, array('type' => EqualType::TYPE_IS_EQUAL, 'value' => 'asd'));

        $this->assertEquals(array('o.association_mapping', 's_association_mapping = :field_name_0'), $builder->query);
        $this->assertEquals(true, $filter->isActive());
    }

    public function testAssociationWithValidParentAssociationMappings()
    {
        $filter = new ModelFilter;
        $filter->initialize('field_name', array(
            'mapping_type' => ClassMetadataInfo::ONE_TO_ONE,
            'field_name' => 'field_name',
            'parent_association_mappings' => array(
                array(
                    'fieldName' => 'association_mapping'
                ),
                array(
                    'fieldName' => 'sub_association_mapping'
                ),
            ),
            'association_mapping' => array(
                'fieldName' => 'sub_sub_association_mapping'
            )
        ));

        $builder = new ProxyQuery(new QueryBuilder);

        $filter->apply($builder, array('type' => EqualType::TYPE_IS_EQUAL, 'value' => 'asd'));

        $this->assertEquals(array(
            'o.association_mapping',
            's_association_mapping.sub_association_mapping',
            's_association_mapping_sub_association_mapping.sub_sub_association_mapping',
            's_association_mapping_sub_association_mapping_sub_sub_association_mapping = :field_name_0'
        ), $builder->query);
        $this->assertEquals(true, $filter->isActive());
    }
}
