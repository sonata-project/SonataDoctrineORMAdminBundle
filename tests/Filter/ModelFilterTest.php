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
use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\Type\Operator\EqualOperatorType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\ModelFilter;

class ModelFilterTest extends FilterTestCase
{
    /**
     * @return \Sonata\AdminBundle\Admin\FieldDescriptionInterface
     */
    public function getFieldDescription(array $options)
    {
        $fieldDescription = $this->createMock(FieldDescriptionInterface::class);
        $fieldDescription->expects($this->once())->method('getOptions')->willReturn($options);
        $fieldDescription->expects($this->once())->method('getName')->willReturn('field_name');

        return $fieldDescription;
    }

    public function testFilterEmpty(): void
    {
        $filter = new ModelFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($builder, 'alias', 'field', null);
        $filter->filter($builder, 'alias', 'field', []);

        $this->assertSame([], $builder->query);
        $this->assertFalse($filter->isActive());
    }

    public function testFilterArray(): void
    {
        $filter = new ModelFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($builder, 'alias', 'field', [
            'type' => EqualOperatorType::TYPE_EQUAL,
            'value' => ['1', '2'],
        ]);

        // the alias is now computer by the entityJoin method
        $this->assertSame(['alias IN :field_name_0'], $builder->query);
        $this->assertSame(['field_name_0' => ['1', '2']], $builder->queryParameters);
        $this->assertTrue($filter->isActive());
    }

    public function testFilterArrayTypeIsNotEqual(): void
    {
        $filter = new ModelFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar'], 'field_name' => 'field_name']);

        $builder = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($builder, 'alias', 'field', [
            'type' => EqualOperatorType::TYPE_NOT_EQUAL,
            'value' => ['1', '2'],
        ]);

        // the alias is now computer by the entityJoin method
        $this->assertSame(
            'alias NOT IN :field_name_0 OR IDENTITY('.current(($builder->getRootAliases())).'.field_name) IS NULL',
            $builder->query[0]
        );
        $this->assertSame(['field_name_0' => ['1', '2']], $builder->queryParameters);
        $this->assertTrue($filter->isActive());
    }

    public function testFilterScalar(): void
    {
        $filter = new ModelFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($builder, 'alias', 'field', ['type' => EqualOperatorType::TYPE_EQUAL, 'value' => 2]);

        $this->assertSame(['alias IN :field_name_0'], $builder->query);
        $this->assertSame(['field_name_0' => [2]], $builder->queryParameters);
        $this->assertTrue($filter->isActive());
    }

    public function testFilterScalarTypeIsNotEqual(): void
    {
        $filter = new ModelFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar'], 'field_name' => 'field_name']);

        $builder = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($builder, 'alias', 'field', ['type' => EqualOperatorType::TYPE_NOT_EQUAL, 'value' => 2]);

        $this->assertSame(
            'alias NOT IN :field_name_0 OR IDENTITY('.current(($builder->getRootAliases())).'.field_name) IS NULL',
            $builder->query[0]
        );

        $this->assertSame(['field_name_0' => [2]], $builder->queryParameters);
        $this->assertTrue($filter->isActive());
    }

    public function testAssociationWithInvalidMapping(): void
    {
        $this->expectException(\RuntimeException::class);

        $filter = new ModelFilter();
        $filter->initialize('field_name', ['mapping_type' => 'foo']);

        $builder = new ProxyQuery($this->createQueryBuilderStub());

        $filter->apply($builder, ['value' => 'asd']);
    }

    public function testAssociationWithValidMappingAndEmptyFieldName(): void
    {
        $this->expectException(\RuntimeException::class);

        $filter = new ModelFilter();
        $filter->initialize('field_name', ['mapping_type' => ClassMetadata::ONE_TO_ONE]);

        $builder = new ProxyQuery($this->createQueryBuilderStub());

        $filter->apply($builder, ['value' => 'asd']);
        $this->assertTrue($filter->isActive());
    }

    public function testAssociationWithValidMapping(): void
    {
        $filter = new ModelFilter();
        $filter->initialize('field_name', [
            'mapping_type' => ClassMetadata::ONE_TO_ONE,
            'field_name' => 'field_name',
            'association_mapping' => [
                'fieldName' => 'association_mapping',
            ],
        ]);

        $builder = new ProxyQuery($this->createQueryBuilderStub());

        $filter->apply($builder, ['type' => EqualOperatorType::TYPE_EQUAL, 'value' => 'asd']);

        $this->assertSame([
            'LEFT JOIN o.association_mapping AS s_association_mapping',
            's_association_mapping IN :field_name_0',
        ], $builder->query);
        $this->assertTrue($filter->isActive());
    }

    public function testAssociationWithValidParentAssociationMappings(): void
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

        $builder = new ProxyQuery($this->createQueryBuilderStub());

        $filter->apply($builder, ['type' => EqualOperatorType::TYPE_EQUAL, 'value' => 'asd']);

        $this->assertSame([
            'LEFT JOIN o.association_mapping AS s_association_mapping',
            'LEFT JOIN s_association_mapping.sub_association_mapping AS s_association_mapping_sub_association_mapping',
            'LEFT JOIN s_association_mapping_sub_association_mapping.sub_sub_association_mapping AS s_association_mapping_sub_association_mapping_sub_sub_association_mapping',
            's_association_mapping_sub_association_mapping_sub_sub_association_mapping IN :field_name_0',
        ], $builder->query);
        $this->assertTrue($filter->isActive());
    }
}
