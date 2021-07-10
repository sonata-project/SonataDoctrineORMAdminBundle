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

use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\DateFilter;
use Symfony\Component\Form\Extension\Core\Type\DateType;

/**
 * @author Ennio Wolsink <ennio@rimote.nl>
 */
final class DateFilterTest extends FilterTestCase
{
    public function testEmpty(): void
    {
        $filter = new DateFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray([]));

        $this->assertSameQuery([], $proxyQuery);
        $this->assertFalse($filter->isActive());
    }

    public function testGetType(): void
    {
        $filter = new DateFilter();
        $filter->initialize('foo');

        $this->assertSame(DateType::class, $filter->getFieldType());
    }

    public function testFilterRecordsWholeDay(): void
    {
        $filter = new DateFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());
        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray(['value' => new \DateTime()]));

        $this->assertSameQuery([
            'WHERE alias.field < :field_name_0',
            'WHERE alias.field >= :field_name_1',
        ], $proxyQuery);
        $this->assertTrue($filter->isActive());

        $builder = $proxyQuery->getQueryBuilder();
        \assert($builder instanceof TestQueryBuilder);
        $this->assertCount(2, $builder->queryParameters);
    }
}
