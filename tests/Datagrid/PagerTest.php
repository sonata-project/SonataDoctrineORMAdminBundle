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

namespace Sonata\DoctrineORMAdminBundle\Tests\Datagrid;

use Doctrine\ORM\AbstractQuery;
use PHPUnit\Framework\TestCase;
use Sonata\DoctrineORMAdminBundle\Datagrid\Pager;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Tests\Filter\QueryBuilder;

class PagerTest extends TestCase
{
    public function dataGetComputeNbResult()
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @dataProvider dataGetComputeNbResult
     *
     * @param bool $distinct
     */
    public function testComputeNbResult($distinct): void
    {
        $query = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSingleScalarResult'])
            ->getMockForAbstractClass();

        $query->expects($this->once())
            ->method('getSingleScalarResult');

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuery', 'select', 'resetDQLPart'])
            ->getMock();

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $queryBuilder->expects($this->once())
            ->method('select');

        $proxyQuery = new ProxyQuery($queryBuilder);
        $proxyQuery->setDistinct($distinct);

        $queryBuilder->expects($this->once())
            ->method('resetDQLPart')
            ->willReturn($proxyQuery);

        $pager = new Pager();
        $pager->setQuery($proxyQuery);
        $pager->computeNbResult();
    }
}
