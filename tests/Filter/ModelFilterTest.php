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
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\Type\Operator\EqualOperatorType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\ModelFilter;

class ModelFilterTest extends FilterTestCase
{
    public function testFilterEmpty(): void
    {
        $filter = new ModelFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray([]));

        $this->assertSameQuery([], $proxyQuery);
        $this->assertFalse($filter->isActive());
    }

    public function testFilterArray(): void
    {
        $filter = new ModelFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray([
            'type' => EqualOperatorType::TYPE_EQUAL,
            'value' => ['1', '2'],
        ]));

        // the alias is now computer by the entityJoin method
        $this->assertSameQuery(['WHERE alias IN :field_name_0'], $proxyQuery);
        $this->assertSameQueryParameters(['field_name_0' => ['1', '2']], $proxyQuery);
        $this->assertTrue($filter->isActive());
    }

    public function testFilterArrayTypeIsNotEqual(): void
    {
        $filter = new ModelFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar'], 'field_name' => 'field_name']);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray([
            'type' => EqualOperatorType::TYPE_NOT_EQUAL,
            'value' => ['1', '2'],
        ]));

        // the alias is now computer by the entityJoin method
        $this->assertSameQuery(
            ['WHERE alias NOT IN :field_name_0 OR IDENTITY('.current(($proxyQuery->getRootAliases())).'.field_name) IS NULL'],
            $proxyQuery
        );
        $this->assertSameQueryParameters(['field_name_0' => ['1', '2']], $proxyQuery);
        $this->assertTrue($filter->isActive());
    }

    public function testFilterScalar(): void
    {
        $filter = new ModelFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray(['type' => EqualOperatorType::TYPE_EQUAL, 'value' => 2]));

        $this->assertSameQuery(['WHERE alias IN :field_name_0'], $proxyQuery);
        $this->assertSameQueryParameters(['field_name_0' => [2]], $proxyQuery);
        $this->assertTrue($filter->isActive());
    }

    public function testFilterScalarTypeIsNotEqual(): void
    {
        $filter = new ModelFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar'], 'field_name' => 'field_name']);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray(['type' => EqualOperatorType::TYPE_NOT_EQUAL, 'value' => 2]));

        $this->assertSameQuery(
            ['WHERE alias NOT IN :field_name_0 OR IDENTITY('.current(($proxyQuery->getRootAliases())).'.field_name) IS NULL'],
            $proxyQuery
        );

        $this->assertSameQueryParameters(['field_name_0' => [2]], $proxyQuery);
        $this->assertTrue($filter->isActive());
    }

    public function testAssociationWithInvalidMapping(): void
    {
        $filter = new ModelFilter();
        $filter->initialize('field_name', ['mapping_type' => 'foo']);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $this->expectException(\RuntimeException::class);

        $filter->apply($proxyQuery, FilterData::fromArray(['value' => 'asd']));
    }

    public function testAssociationWithValidMappingAndEmptyFieldName(): void
    {
        $this->expectException(\RuntimeException::class);

        $filter = new ModelFilter();
        $filter->initialize('field_name', ['mapping_type' => ClassMetadata::ONE_TO_ONE]);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $filter->apply($proxyQuery, FilterData::fromArray(['value' => 'asd']));
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

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $filter->apply($proxyQuery, FilterData::fromArray(['type' => EqualOperatorType::TYPE_EQUAL, 'value' => 'asd']));

        $this->assertSameQuery([
            'LEFT JOIN o.association_mapping AS s_association_mapping',
            'WHERE s_association_mapping IN :field_name_0',
        ], $proxyQuery);
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

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $filter->apply($proxyQuery, FilterData::fromArray(['type' => EqualOperatorType::TYPE_EQUAL, 'value' => 'asd']));

        $this->assertSameQuery([
            'LEFT JOIN o.association_mapping AS s_association_mapping',
            'LEFT JOIN s_association_mapping.sub_association_mapping AS s_association_mapping_sub_association_mapping',
            'LEFT JOIN s_association_mapping_sub_association_mapping.sub_sub_association_mapping AS s_association_mapping_sub_association_mapping_sub_sub_association_mapping',
            'WHERE s_association_mapping_sub_association_mapping_sub_sub_association_mapping IN :field_name_0',
        ], $proxyQuery);
        $this->assertTrue($filter->isActive());
    }
}
