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
use Doctrine\ORM\Query\Expr\From;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType\UuidType;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Util\NonIntegerIdentifierTestClass;

class ProxyQueryTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        if (!Type::hasType('uuid')) {
            Type::addType('uuid', 'Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType\UuidType');
        }
    }

    public function dataGetFixedQueryBuilder()
    {
        return array(
            array('aaa', 'bbb', 'id', 'id_idx', 33, Type::INTEGER),
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
        $meta = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataInfo')
            ->disableOriginalConstructor()
            ->getMock();
        $meta->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(array($id));
        $meta->expects($this->any())
            ->method('getTypeOfField')
            ->willReturn($identifierType);

        $mf = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $mf->expects($this->any())
            ->method('getMetadataFor')
            ->with($this->equalTo($class))
            ->willReturn($meta);

        $platform = $this->getMockBuilder('Doctrine\DBAL\Platforms\PostgreSqlPlatform')
            ->disableOriginalConstructor()
            ->getMock();

        $conn = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $conn->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($mf);
        $em->expects($this->any())
            ->method('getConnection')
            ->willReturn($conn);

        $q = $this->getMock('PDOStatement');
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
        $qb->expects($this->once())
            ->method('getDQLPart')
            ->with($this->equalTo('from'))
            ->willReturn(array(new From($class, $alias)));
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
}
