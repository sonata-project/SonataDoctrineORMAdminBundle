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
use Sonata\DoctrineORMAdminBundle\Filter\UidFilter;

final class UidFilterTest extends FilterTestCase
{
    public function testSearchEnabled(): void
    {
        $filter = new UidFilter();
        $filter->initialize('field_name');
        static::assertFalse($filter->isSearchEnabled());

        $filter = new UidFilter();
        $filter->initialize('field_name', ['global_search' => true]);
        static::assertTrue($filter->isSearchEnabled());
    }

    public function testEmpty(): void
    {
        $filter = new UidFilter();
        $filter->initialize('field_name');

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray([]));
        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray(['value' => '']));

        self::assertSameQuery([], $proxyQuery);
        static::assertFalse($filter->isActive());
    }
}
