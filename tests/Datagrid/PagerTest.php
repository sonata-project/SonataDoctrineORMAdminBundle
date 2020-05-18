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
    public function entityClassDataProvider(): array
    {
        return [
            [User::class], // single identifier
            [UserBrowser::class], // composite identifier
        ];
    }

    /**
     * @dataProvider entityClassDataProvider
     */
    public function testComputeNbResultForCompositeId(string $className): void
    {
        $em = DoctrineTestHelper::createTestEntityManager();
        $classes = [
            $em->getClassMetadata($className),
        ];
        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema($classes);

        $qb = $em->createQueryBuilder()
            ->select('e')
            ->from($className, 'e');
        $pq = new ProxyQuery($qb);
        $pager = new Pager();
        $pager->setCountColumn($em->getClassMetadata($className)->getIdentifierFieldNames());
        $pager->setQuery($pq);
        $this->assertSame(0, $pager->computeNbResult());
    }

    public function dataGetComputeNbResult(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @dataProvider dataGetComputeNbResult
     */
    public function testComputeNbResult(bool $distinct): void
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

    /**
     * @dataProvider initDataProvider
     */
    public function testInit(int $computedNbResult, int $page, int $expectedFirstResult): void
    {
        $query = $this->createMock(ProxyQuery::class);
        $pager = $this->createPartialMock(Pager::class, ['computeNbResult']);

        $pager->method('computeNbResult')->willReturn($computedNbResult);

        $pager->setMaxPerPage(10);
        $pager->setPage($page);
        $pager->setQuery($query);

        $query->expects($this->once())->method('setFirstResult')->with($expectedFirstResult);
        $query->expects($this->once())->method('setMaxResults')->with(10);

        $pager->init();
    }

    public function initDataProvider(): iterable
    {
        return [
            [50, 3, 20],
            [50, 10, 40],
            [15, 3, 10],
            [15, 1, 0],
        ];
    }
}
