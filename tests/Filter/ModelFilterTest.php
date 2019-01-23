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

use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\CoreBundle\Form\Type\EqualType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\ModelFilter;

class ModelFilterTest extends TestCase
{
    /**
     * @param array $options
     *
     * @return \Sonata\AdminBundle\Admin\FieldDescriptionInterface
     */
    public function getFieldDescription(array $options)
    {
        $fieldDescription = $this->createMock(FieldDescriptionInterface::class);
        $fieldDescription->expects($this->once())->method('getOptions')->will($this->returnValue($options));
        $fieldDescription->expects($this->once())->method('getName')->will($this->returnValue('field_name'));

        return $fieldDescription;
    }

    public function testFilterEmpty()
    {
        $filter = new ModelFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', null);
        $filter->filter($builder, 'alias', 'field', []);

        $this->assertSame([], $builder->query);
        $this->assertFalse($filter->isActive());
    }

    public function testFilterArray()
    {
        $filter = new ModelFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', [
            'type' => EqualType::TYPE_IS_EQUAL,
            'value' => ['1', '2'],
        ]);

        // the alias is now computer by the entityJoin method
        $this->assertSame(['in_alias', 'in_alias IN :field_name_0'], $builder->query);
        $this->assertSame(['field_name_0' => ['1', '2']], $builder->parameters);
        $this->assertTrue($filter->isActive());
    }

    public function testFilterArrayTypeIsNotEqual()
    {
        $filter = new ModelFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar'], 'field_name' => 'field_name']);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', [
            'type' => EqualType::TYPE_IS_NOT_EQUAL,
            'value' => ['1', '2'],
        ]);

        // the alias is now computer by the entityJoin method
        $this->assertSame([
            'alias NOT IN :field_name_0',
            'IDENTITY('.current(($builder->getRootAliases())).'.field_name) IS NULL',
        ], $builder->query[0]->getParts());
        $this->assertSame(['field_name_0' => ['1', '2']], $builder->parameters);
        $this->assertTrue($filter->isActive());
    }

    public function testFilterScalar()
    {
        $filter = new ModelFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', ['type' => EqualType::TYPE_IS_EQUAL, 'value' => 2]);

        $this->assertSame(['in_alias', 'in_alias IN :field_name_0'], $builder->query);
        $this->assertSame(['field_name_0' => [2]], $builder->parameters);
        $this->assertTrue($filter->isActive());
    }

    public function testFilterScalarTypeIsNotEqual()
    {
        $filter = new ModelFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar'], 'field_name' => 'field_name']);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', ['type' => EqualType::TYPE_IS_NOT_EQUAL, 'value' => 2]);

        $this->assertSame([
            'alias NOT IN :field_name_0',
            'IDENTITY('.current(($builder->getRootAliases())).'.field_name) IS NULL',
        ], $builder->query[0]->getParts());

        $this->assertSame(['field_name_0' => [2]], $builder->parameters);
        $this->assertTrue($filter->isActive());
    }

    public function testAssociationWithInvalidMapping()
    {
        $this->expectException(\RuntimeException::class);

        $filter = new ModelFilter();
        $filter->initialize('field_name', ['mapping_type' => 'foo']);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->apply($builder, ['value' => 'asd']);
    }

    public function testAssociationWithValidMappingAndEmptyFieldName()
    {
        $this->expectException(\RuntimeException::class);

        $filter = new ModelFilter();
        $filter->initialize('field_name', ['mapping_type' => ClassMetadata::ONE_TO_ONE]);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->apply($builder, ['value' => 'asd']);
        $this->assertTrue($filter->isActive());
    }

    public function testAssociationWithValidMapping()
    {
        $filter = new ModelFilter();
        $filter->initialize('field_name', [
            'mapping_type' => ClassMetadata::ONE_TO_ONE,
            'field_name' => 'field_name',
            'association_mapping' => [
                'fieldName' => 'association_mapping',
            ],
        ]);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->apply($builder, ['type' => EqualType::TYPE_IS_EQUAL, 'value' => 'asd']);

        $this->assertSame([
            'o.association_mapping',
            'in_s_association_mapping',
            'in_s_association_mapping IN :field_name_0',
        ], $builder->query);
        $this->assertTrue($filter->isActive());
    }

    public function testAssociationWithValidParentAssociationMappings()
    {
        $filter = new ModelFilter();
        $filter->initialize('field_name', [
            'mapping_type' => ClassMetadata::ONE_TO_ONE,
            'field_name' => 'field_name',
            'parent_association_mappings' => [
                [
                    'fieldName' => 'association_mapping',
                ],
                [
                    'fieldName' => 'sub_association_mapping',
                ],
            ],
            'association_mapping' => [
                'fieldName' => 'sub_sub_association_mapping',
            ],
        ]);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->apply($builder, ['type' => EqualType::TYPE_IS_EQUAL, 'value' => 'asd']);

        $this->assertSame([
            'o.association_mapping',
            's_association_mapping.sub_association_mapping',
            's_association_mapping_sub_association_mapping.sub_sub_association_mapping',
            'in_s_association_mapping_sub_association_mapping_sub_sub_association_mapping',
            'in_s_association_mapping_sub_association_mapping_sub_sub_association_mapping IN :field_name_0',
        ], $builder->query);
        $this->assertTrue($filter->isActive());
    }
}
