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
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\CountWalker;
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
        $q = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSingleScalarResult', 'getHint', 'setHint'])
            ->getMockForAbstractClass();

        $q->expects($this->once())
            ->method('getSingleScalarResult');

        $q->expects($this->once())
            ->method('getHint')
            ->willReturn(null);

        $q->expects($this->exactly(2))
            ->method('setHint')
            ->withConsecutive(
                [$this->equalTo(CountWalker::HINT_DISTINCT), $this->equalTo($distinct)],
                [$this->equalTo(Query::HINT_CUSTOM_TREE_WALKERS), $this->equalTo([CountWalker::class])]
            );

        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuery'])
            ->getMock();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($q);

        $pq = new ProxyQuery($qb);
        $pq->setDistinct($distinct);

        $pager = new Pager();
        $pager->setQuery($pq);
        $pager->computeNbResult();
    }
}
