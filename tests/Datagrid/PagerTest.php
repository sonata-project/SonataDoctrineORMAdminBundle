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

use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use Sonata\DoctrineORMAdminBundle\Datagrid\Pager;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\ORM\User;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\ORM\UserBrowser;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\TestEntityManagerFactory;

final class PagerTest extends TestCase
{
    public function testGetCurrentPageResults(): void
    {
        $iterator = new \ArrayIterator([new \stdClass()]);

        $paginator = $this->createMock(Paginator::class);
        $paginator->expects(self::once())->method('getIterator')->willReturn($iterator);

        $pq = $this->createMock(ProxyQueryInterface::class);
        $pq->method('execute')->willReturn($paginator);

        $pager = new Pager();
        $pager->setQuery($pq);

        self::assertSame($iterator, $pager->getCurrentPageResults());
    }

    /**
     * @phpstan-return iterable<array-key, array{class-string}>
     */
    public function entityClassDataProvider(): iterable
    {
        return [
            [User::class], // single identifier
            [UserBrowser::class], // composite identifier
        ];
    }

    /**
     * @phpstan-param class-string $className
     *
     * @dataProvider entityClassDataProvider
     */
    public function testCountResults(string $className): void
    {
        $em = TestEntityManagerFactory::create();
        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema([
            $em->getClassMetadata($className),
        ]);

        $qb = $em->createQueryBuilder()->select('e')->from($className, 'e');
        $pq = new ProxyQuery($qb);

        $pager = new Pager();
        $pager->setQuery($pq);
        $pager->init();

        $this->assertSame(0, $pager->countResults());
    }
}
