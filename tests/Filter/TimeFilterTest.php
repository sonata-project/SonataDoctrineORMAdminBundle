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

use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\TimeFilter;
use Symfony\Component\Form\Extension\Core\Type\TimeType;

/**
 * @author Marko Kunic <kunicmarko20@gmail.com>
 */
class TimeFilterTest extends FilterTestCase
{
    public function testEmpty(): void
    {
        $filter = new TimeFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($proxyQuery, 'alias', 'field', []);
        $filter->filter($proxyQuery, 'alias', 'field', ['value' => '']);

        $this->assertSameQuery([], $proxyQuery);
        $this->assertFalse($filter->isActive());
    }

    public function testGetType(): void
    {
        $this->assertSame(TimeType::class, (new TimeFilter())->getFieldType());
    }
}
