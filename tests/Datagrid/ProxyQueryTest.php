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

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType\UuidType;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\DoubleNameEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Query\FooWalker;
use Symfony\Bridge\Doctrine\Test\DoctrineTestHelper;

class ProxyQueryTest extends TestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    public static function setUpBeforeClass(): void
    {
        if (!Type::hasType('uuid')) {
            Type::addType('uuid', UuidType::class);
        }
    }

    protected function setUp(): void
    {
        $this->em = DoctrineTestHelper::createTestEntityManager();

        $schemaTool = new SchemaTool($this->em);
        $classes = [
            $this->em->getClassMetadata(DoubleNameEntity::class),
        ];

        try {
            $schemaTool->dropSchema($classes);
        } catch (\Exception $e) {
        }

        try {
            $schemaTool->createSchema($classes);
        } catch (\Exception $e) {
        }
    }

    protected function tearDown(): void
    {
        unset($this->em);
    }

    public function testSetHint(): void
    {
        $entity1 = new DoubleNameEntity(1, 'Foo', null);
        $entity2 = new DoubleNameEntity(2, 'Bar', null);

        $this->em->persist($entity1);
        $this->em->persist($entity2);
        $this->em->flush();

        $qb = $this->em->createQueryBuilder()->select('o')->from(DoubleNameEntity::class, 'o');

        $pq = new ProxyQuery($qb);
        $pq->setHint(
            Query::HINT_CUSTOM_OUTPUT_WALKER,
            FooWalker::class
        );
        $pq->setHint('hint', 'value');

        $result = $pq->execute();

        $this->assertSame([$entity2, $entity1], $result);
    }

    public function testSortOrderValidatesItsInput(): void
    {
        $query = new ProxyQuery($this->em->createQueryBuilder());
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            '"ASC,injection" is not a valid sort order, valid values are "ASC, DESC"'
        );
        $query->setSortOrder('ASC,injection');
    }

    /**
     * @phpstan-return iterable<array{string}>
     */
    public function validSortOrders(): iterable
    {
        return [
            ['ASC'],
            ['DESC'],
            ['asc'],
            ['desc'],
            ['AsC'],
            ['deSc'],
        ];
    }

    /**
     * @dataProvider validSortOrders
     */
    public function testItAllowsSortOrdersWithStrangeCase(string $validValue): void
    {
        $query = new ProxyQuery($this->em->createQueryBuilder());
        $query->setSortOrder($validValue);
        $this->assertSame($validValue, $query->getSortOrder());
    }

    public function testExecuteWithOrderBy(): void
    {
        $entity1 = new DoubleNameEntity(1, 'Foo', 'Bar');
        $entity2 = new DoubleNameEntity(2, 'Bar', 'Bar');
        $entity3 = new DoubleNameEntity(3, 'Bar', 'Foo');

        $this->em->persist($entity1);
        $this->em->persist($entity2);
        $this->em->persist($entity3);
        $this->em->flush();

        $query = new ProxyQuery(
            $this->em->createQueryBuilder()->select('o')->from(DoubleNameEntity::class, 'o')
        );
        $query->setSortBy([], ['fieldName' => 'name2'])->setSortOrder('ASC');

        $this->assertSame([$entity1, $entity2, $entity3], $query->execute());

        $query2 = new ProxyQuery(
            $this->em->createQueryBuilder()->select('o')->from(DoubleNameEntity::class, 'o')->addOrderBy('o.name')
        );
        $query2->setSortBy([], ['fieldName' => 'name2'])->setSortOrder('ASC');

        $this->assertSame([$entity2, $entity1, $entity3], $query2->execute());
    }
}
