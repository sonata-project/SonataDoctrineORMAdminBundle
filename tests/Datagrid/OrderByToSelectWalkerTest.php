<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Tests\Datagrid;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Version;
use PHPUnit\Framework\TestCase;
use Sonata\DoctrineORMAdminBundle\Datagrid\OrderByToSelectWalker;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\ORM\Menu;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\ORM\StoreProduct;
use Symfony\Bridge\Doctrine\Test\DoctrineTestHelper;

/**
 * @author Dariusz Markowicz <dmarkowicz77@gmail.com>
 */
final class OrderByToSelectWalkerTest extends TestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    protected function setUp()
    {
        $this->em = DoctrineTestHelper::createTestEntityManager();
    }

    protected function tearDown()
    {
        $this->em = null;
    }

    public function testOrderByCompositeId()
    {
        $qb = $this->em->createQueryBuilder()
            ->select('IDENTITY(o.store) as store, IDENTITY(o.product) as product')
            ->distinct()
            ->from(StoreProduct::class, 'o')
            ->orderBy('o.name', 'ASC')
            ->addOrderBy('o.product', 'DESC');

        $query = $qb->getQuery();
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [OrderByToSelectWalker::class]);

        $this->assertEquals(
            // NEXT_MAJOR: Remove this check when dropping support for doctrine/orm < 2.5
            version_compare(Version::VERSION, '2.5') < 0
                ? 'SELECT DISTINCT s0_.store_id AS sclr0, s0_.product_id AS sclr1, s0_.name AS name2 FROM StoreProduct s0_ ORDER BY s0_.name ASC, s0_.product_id DESC'
                : 'SELECT DISTINCT s0_.store_id AS sclr_0, s0_.product_id AS sclr_1, s0_.name AS name_2 FROM StoreProduct s0_ ORDER BY s0_.name ASC, s0_.product_id DESC',
            $query->getSQL()
        );
    }

    public function testOrderByCompositeIdWholeObject()
    {
        $qb = $this->em->createQueryBuilder()
            ->select('o')
            ->distinct()
            ->from(StoreProduct::class, 'o')
            ->orderBy('o.name', 'ASC')
            ->addOrderBy('o.product', 'DESC');

        $query = $qb->getQuery();
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [OrderByToSelectWalker::class]);

        $this->assertEquals(
            // NEXT_MAJOR: Remove this check when dropping support for doctrine/orm < 2.5
            version_compare(Version::VERSION, '2.5') < 0
                ? 'SELECT DISTINCT s0_.name AS name0, s0_.store_id AS store_id1, s0_.product_id AS product_id2 FROM StoreProduct s0_ ORDER BY s0_.name ASC, s0_.product_id DESC'
                : 'SELECT DISTINCT s0_.name AS name_0, s0_.store_id AS store_id_1, s0_.product_id AS product_id_2 FROM StoreProduct s0_ ORDER BY s0_.name ASC, s0_.product_id DESC',
            $query->getSQL()
        );
    }

    public function testOrderByAssociation()
    {
        $qb = $this->em->createQueryBuilder()
            ->select('m.id')
            ->distinct()
            ->from(Menu::class, 'm')
            ->orderBy('m.root, m.lft');

        $query = $qb->getQuery();
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [OrderByToSelectWalker::class]);

        $this->assertEquals(
            // NEXT_MAJOR: Remove this check when dropping support for doctrine/orm < 2.5
            version_compare(Version::VERSION, '2.5') < 0
                ? 'SELECT DISTINCT m0_.id AS id0, m0_.tree_root AS sclr1, m0_.lft AS lft2 FROM Menu m0_ ORDER BY m0_.tree_root ASC, m0_.lft ASC'
                : 'SELECT DISTINCT m0_.id AS id_0, m0_.tree_root AS sclr_1, m0_.lft AS lft_2 FROM Menu m0_ ORDER BY m0_.tree_root ASC, m0_.lft ASC',
            $query->getSQL()
        );
    }
}
