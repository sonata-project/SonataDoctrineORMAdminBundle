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

use PHPUnit\Framework\TestCase;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\DateFilter;
use Symfony\Component\Form\Extension\Core\Type\DateType;

/**
 * @author Ennio Wolsink <ennio@rimote.nl>
 */
class DateFilterTest extends TestCase
{
    public function testEmpty(): void
    {
        $filter = new DateFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery(new QueryBuilder());

        $filter->filter($builder, 'alias', 'field', null);
        $filter->filter($builder, 'alias', 'field', '');

        $this->assertSame([], $builder->query);
        $this->assertFalse($filter->isActive());
    }

    public function testGetType(): void
    {
        $this->assertSame(DateType::class, (new DateFilter())->getFieldType());
    }

    public function testFilterRecordsWholeDay(): void
    {
        $filter = new DateFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $builder = new ProxyQuery(new QueryBuilder());
        $filter->filter($builder, 'alias', 'field', ['value' => new \DateTime()]);

        $this->assertCount(2, $builder->parameters);
        $this->assertCount(2, $builder->query);
    }
}
