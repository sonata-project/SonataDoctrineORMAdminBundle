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
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use Sonata\DoctrineORMAdminBundle\Datagrid\Pager;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Tests\Filter\QueryBuilder;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\ORM\User;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\ORM\UserBrowser;
use Symfony\Bridge\Doctrine\Test\DoctrineTestHelper;

class PagerTest extends TestCase
{
    public function testComputeNbResultFoCompositeId(): void
    {
        $em = DoctrineTestHelper::createTestEntityManager();
        $classes = [
            $em->getClassMetadata(User::class),
            $em->getClassMetadata(UserBrowser::class),
        ];
        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema($classes);

        $qb = $em->createQueryBuilder()
            ->select('ub')
            ->from(UserBrowser::class, 'ub');
        $pq = new ProxyQuery($qb);
        $pager = new Pager();
        $pager->setCountColumn($em->getClassMetadata(UserBrowser::class)->getIdentifierFieldNames());
        $pager->setQuery($pq);
        $this->assertSame(0, $pager->computeNbResult());
    }

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
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([['cnt' => 1], ['cnt' => 2]]);

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
