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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType\UuidType;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Query\FooWalker;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Util\NonIntegerIdentifierTestClass;
use Symfony\Bridge\Doctrine\Test\DoctrineTestHelper;
use Symfony\Bridge\Doctrine\Tests\Fixtures\DoubleNameEntity;

class ProxyQueryTest extends TestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    public static function setUpBeforeClass()
    {
        if (!Type::hasType('uuid')) {
            Type::addType('uuid', UuidType::class);
        }
    }

    protected function setUp()
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

    protected function tearDown()
    {
        $this->em = null;
    }

    public function dataGetFixedQueryBuilder()
    {
        return [
            ['aaa', 'bbb', 'id', 'id_idx', 33, Type::INTEGER, true],
            ['aaa', 'bbb', 'associatedId', 'associatedId_idx', 33, null, true],
            ['aaa', 'bbb', 'id.value', 'id_value_idx', 33, Type::INTEGER, false],
            ['aaa', 'bbb', 'id.uuid', 'id_uuid_idx', new NonIntegerIdentifierTestClass('80fb6f91-bba1-4d35-b3d4-e06b24494e85'), UuidType::NAME, false],
        ];
    }

    /**
     * @dataProvider dataGetFixedQueryBuilder
     */
    public function testGetFixedQueryBuilder($class, $alias, $id, $expectedId, $value, $identifierType, $distinct)
    {
        $meta = $this->createMock(ClassMetadata::class);
        $meta->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn([$id]);
        $meta->expects($this->any())
            ->method('getTypeOfField')
            ->willReturn($identifierType);

        $mf = $this->createMock(ClassMetadataFactory::class);
        $mf->expects($this->any())
            ->method('getMetadataFor')
            ->with($this->equalTo($class))
            ->willReturn($meta);

        $platform = $this->createMock(PostgreSqlPlatform::class);

        $conn = $this->createMock(Connection::class);
        $conn->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($mf);
        $em->expects($this->any())
            ->method('getConnection')
            ->willReturn($conn);

        // NEXT MAJOR: Replace this when dropping PHP < 5.6
        // $q = $this->createMock('PDOStatement');
        $q = $this->getMockBuilder('stdClass')
            ->setMethods(['execute', 'setHint'])
            ->getMock();
        $q->expects($this->once())
           ->method('setHint')
           ->willReturn($q);
        $q->expects($this->any())
            ->method('execute')
            ->willReturn([[$id => $value]]);

        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->setConstructorArgs([$em])
            ->getMock();
        $qb->expects($this->once())
            ->method('distinct')
            ->with($this->equalTo($distinct));
        $qb->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);
        $qb->expects($this->any())
            ->method('getQuery')
            ->willReturn($q);
        $qb->expects($this->once())
            ->method('setParameter')
            ->with($this->equalTo($expectedId), $this->equalTo([$value]));
        $qb->expects($this->any())
            ->method('getDQLPart')
            ->will($this->returnCallBack(function ($part) use ($class, $alias) {
                $parts = [
                    'from' => [new From($class, $alias)],
                    'orderBy' => [new OrderBy('whatever', 'DESC')],
                ];

                return $parts[$part];
            }));
        $qb->expects($this->once())
            ->method('addOrderBy')
            ->with("$alias.$id", null);
        $qb->expects($this->once())
            ->method('getRootEntities')
            ->willReturn([$class]);
        $qb->expects($this->exactly(2))
            ->method('getRootAliases')
            ->willReturn([$alias]);

        $pq = $this->getMockBuilder(ProxyQuery::class)
            ->setConstructorArgs([$qb])
            ->setMethods(['a'])
            ->getMock();

        $pq->setDistinct($distinct);

        /* Work */
        $pq->execute();
    }

    public function testSetHint()
    {
        $entity1 = new DoubleNameEntity(1, 'Foo', null);
        $entity2 = new DoubleNameEntity(2, 'Bar', null);

        $this->em->persist($entity1);
        $this->em->persist($entity2);
        $this->em->flush();

        $qb = $this->em->createQueryBuilder()
            ->select('o.id')
            ->from(DoubleNameEntity::class, 'o');

        $pq = new ProxyQuery($qb);
        $pq->setHint(
            Query::HINT_CUSTOM_OUTPUT_WALKER,
            FooWalker::class
        );
        $pq->setHint('hint', 'value');

        $result = $pq->execute();

        $this->assertSame(2, $result[0]['id']);
    }

    public function testSortOrderValidatesItsInput()
    {
        $query = new ProxyQuery($this->em->createQueryBuilder());
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            '"ASC,injection" is not a valid sort order, valid values are "ASC, DESC"'
        );
        $query->setSortOrder('ASC,injection');
    }

    public function validSortOrders()
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
    public function testItAllowsSortOrdersWithStrangeCase($validValue)
    {
        $query = new ProxyQuery($this->em->createQueryBuilder());
        $query->setSortOrder($validValue);
        $this->assertSame($validValue, $query->getSortOrder());
    }
}
