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
use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType\UuidType;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Util\NonIntegerIdentifierTestClass;
use Symfony\Bridge\Doctrine\Test\DoctrineTestHelper;
use Symfony\Bridge\Doctrine\Tests\Fixtures\DoubleNameEntity;

class ProxyQueryTest extends TestCase
{
    public const DOUBLE_NAME_CLASS = 'Symfony\Bridge\Doctrine\Tests\Fixtures\DoubleNameEntity';

    /**
     * @var EntityManager
     */
    private $em;

    public static function setUpBeforeClass(): void
    {
        if (!Type::hasType('uuid')) {
            Type::addType('uuid', 'Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType\UuidType');
        }
    }

    protected function setUp(): void
    {
        $this->em = DoctrineTestHelper::createTestEntityManager();

        $schemaTool = new SchemaTool($this->em);
        $classes = [
            $this->em->getClassMetadata(self::DOUBLE_NAME_CLASS),
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
        $this->em = null;
    }

    public function dataGetFixedQueryBuilder()
    {
        return [
            ['aaa', 'bbb', 'id', 'id_idx', 33, Type::INTEGER],
            ['aaa', 'bbb', 'associatedId', 'associatedId_idx', 33, null],
            ['aaa', 'bbb', 'id.value', 'id_value_idx', 33, Type::INTEGER],
            ['aaa', 'bbb', 'id.uuid', 'id_uuid_idx', new NonIntegerIdentifierTestClass('80fb6f91-bba1-4d35-b3d4-e06b24494e85'), UuidType::NAME],
        ];
    }

    /**
     * @dataProvider dataGetFixedQueryBuilder
     *
     * @param $class
     * @param $alias
     * @param $id
     */
    public function testGetFixedQueryBuilder($class, $alias, $id, $expectedId, $value, $identifierType): void
    {
        $meta = $this->createMock('Doctrine\ORM\Mapping\ClassMetadataInfo');
        $meta->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn([$id]);
        $meta->expects($this->any())
            ->method('getTypeOfField')
            ->willReturn($identifierType);

        $mf = $this->createMock('Doctrine\ORM\Mapping\ClassMetadataFactory');
        $mf->expects($this->any())
            ->method('getMetadataFor')
            ->with($this->equalTo($class))
            ->willReturn($meta);

        $platform = $this->createMock('Doctrine\DBAL\Platforms\PostgreSqlPlatform');

        $conn = $this->createMock('Doctrine\DBAL\Connection');
        $conn->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $em = $this->createMock('Doctrine\ORM\EntityManager');
        $em->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($mf);
        $em->expects($this->any())
            ->method('getConnection')
            ->willReturn($conn);

        // NEXT MAJOR: Replace this when dropping PHP < 5.6
        // $q = $this->createMock('PDOStatement');
        $q = $this->getMockBuilder('stdClass')
            ->setMethods(['execute'])
            ->getMock();
        $q->expects($this->any())
            ->method('execute')
            ->willReturn([[$id => $value]]);

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->setConstructorArgs([$em])
            ->getMock();
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

        $pq = $this->getMockBuilder('Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery')
            ->setConstructorArgs([$qb])
            ->setMethods(['a'])
            ->getMock();

        /* Work */

        $pq->execute();
    }

    public function testAddOrderedColumns(): void
    {
        $qb = $this->em->createQueryBuilder()
                       ->select('o.id')
                       ->distinct()
                       ->from(self::DOUBLE_NAME_CLASS, 'o')
                       ->orderBy('o.name', 'ASC')
                       ->addOrderBy('o.name2', 'DESC');

        $pq = $this->getMockBuilder('Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery')
                   ->disableOriginalConstructor()
                   ->getMock();

        $reflection = new \ReflectionClass(get_class($pq));
        $method = $reflection->getMethod('addOrderedColumns');
        $method->setAccessible(true);
        $method->invoke($pq, $qb);

        $dqlPart = $qb->getDqlPart('select');
        $this->assertCount(3, $dqlPart);
        $this->assertEquals('o.id', $dqlPart[0]);
        $this->assertEquals('o.name', $dqlPart[1]);
        $this->assertEquals('o.name2', $dqlPart[2]);
    }

    public function testSetHint(): void
    {
        $entity1 = new DoubleNameEntity(1, 'Foo', null);
        $entity2 = new DoubleNameEntity(2, 'Bar', null);

        $this->em->persist($entity1);
        $this->em->persist($entity2);
        $this->em->flush();

        $qb = $this->em->createQueryBuilder()
                       ->select('o.id')
                       ->from(self::DOUBLE_NAME_CLASS, 'o');

        $pq = new ProxyQuery($qb);
        $pq->setHint(
            \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
            'Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Query\FooWalker'
        );
        $pq->setHint('hint', 'value');

        $result = $pq->execute();

        $this->assertEquals(2, $result[0]['id']);
    }
}
