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

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\Tools\SchemaTool;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType\UuidType;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Util\NonIntegerIdentifierTestClass;
use Sonata\DoctrineORMAdminBundle\Tests\Helpers\PHPUnit_Framework_TestCase;
use Symfony\Bridge\Doctrine\Test\DoctrineTestHelper;

class ProxyQueryTest extends PHPUnit_Framework_TestCase
{
    const DOUBLE_NAME_CLASS = 'Symfony\Bridge\Doctrine\Tests\Fixtures\DoubleNameEntity';

    /**
     * @var EntityManager
     */
    private $em;

    public static function setUpBeforeClass()
    {
        if (!Type::hasType('uuid')) {
            Type::addType('uuid', 'Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType\UuidType');
        }
    }

    protected function setUp()
    {
        $this->em = DoctrineTestHelper::createTestEntityManager();

        $schemaTool = new SchemaTool($this->em);
        $classes = array(
            $this->em->getClassMetadata(self::DOUBLE_NAME_CLASS),
        );

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
        return array(
            array('aaa', 'bbb', 'id', 'id_idx', 33, Type::INTEGER),
            array('aaa', 'bbb', 'associatedId', 'associatedId_idx', 33, null),
            array('aaa', 'bbb', 'id.value', 'id_value_idx', 33, Type::INTEGER),
            array('aaa', 'bbb', 'id.uuid', 'id_uuid_idx', new NonIntegerIdentifierTestClass('80fb6f91-bba1-4d35-b3d4-e06b24494e85'), UuidType::NAME),
        );
    }

    /**
     * @dataProvider dataGetFixedQueryBuilder
     *
     * @param $class
     * @param $alias
     * @param $id
     */
    public function testGetFixedQueryBuilder($class, $alias, $id, $expectedId, $value, $identifierType)
    {
        $meta = $this->createMock('Doctrine\ORM\Mapping\ClassMetadataInfo');
        $meta->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(array($id));
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
            ->setMethods(array('execute'))
            ->getMock();
        $q->expects($this->any())
            ->method('execute')
            ->willReturn(array(array($id => $value)));

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->setConstructorArgs(array($em))
            ->getMock();
        $qb->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);
        $qb->expects($this->any())
            ->method('getQuery')
            ->willReturn($q);
        $qb->expects($this->once())
            ->method('setParameter')
            ->with($this->equalTo($expectedId), $this->equalTo(array($value)));
        $qb->expects($this->any())
            ->method('getDQLPart')
            ->will($this->returnCallBack(function ($part) use ($class, $alias) {
                $parts = array(
                    'from' => array(new From($class, $alias)),
                    'orderBy' => array(new OrderBy('whatever', 'DESC')),
                );

                return $parts[$part];
            }));
        $qb->expects($this->once())
            ->method('addOrderBy')
            ->with("$alias.$id", null);
        $qb->expects($this->once())
            ->method('getRootEntities')
            ->willReturn(array($class));
        $qb->expects($this->exactly(2))
            ->method('getRootAliases')
            ->willReturn(array($alias));

        $pq = $this->getMockBuilder('Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery')
            ->setConstructorArgs(array($qb))
            ->setMethods(array('a'))
            ->getMock();

        /* Work */

        $pq->execute();
    }

    public function testAddOrderedColumns()
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
}
