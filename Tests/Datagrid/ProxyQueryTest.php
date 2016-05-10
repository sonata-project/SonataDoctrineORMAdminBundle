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

use Doctrine\ORM\Query\Expr\From;

class ProxyQueryTest extends \PHPUnit_Framework_TestCase
{
    public function dataGetFixedQueryBuilder()
    {
        return array(
            array('aaa', 'bbb', 'id', 'id_idx', 33),
            array('aaa', 'bbb', 'id.value', 'id_value_idx', 33),
        );
    }

    /**
     * @dataProvider dataGetFixedQueryBuilder
     *
     * @param $class
     * @param $alias
     * @param $id
     */
    public function testGetFixedQueryBuilder($class, $alias, $id, $expectedId, $value)
    {
        $meta = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataInfo')
            ->disableOriginalConstructor()
            ->getMock();
        $meta->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(array($id));

        $mf = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $mf->expects($this->any())
            ->method('getMetadataFor')
            ->with($this->equalTo($class))
            ->willReturn($meta);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($mf);

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

        $pq = $this->getMockBuilder('Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery')
            ->setConstructorArgs(array($qb))
            ->setMethods(array('a'))
            ->getMock();

        /* Work */

        $pq->execute();
    }
}
