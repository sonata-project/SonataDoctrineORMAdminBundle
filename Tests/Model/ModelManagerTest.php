<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Tests\Model;

use Sonata\DoctrineORMAdminBundle\Admin\FieldDescription;
use Sonata\DoctrineORMAdminBundle\Model\ModelManager;

class ModelManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testSortParameters()
    {
        $registry = $this->getMock('Symfony\Bridge\Doctrine\RegistryInterface');

        $manager  = new ModelManager($registry);

        $datagrid1 = $this->getMockBuilder('\Sonata\AdminBundle\Datagrid\Datagrid')->disableOriginalConstructor()->getMock();
        $datagrid2 = $this->getMockBuilder('\Sonata\AdminBundle\Datagrid\Datagrid')->disableOriginalConstructor()->getMock();

        $field1 = new FieldDescription();
        $field1->setName('field1');

        $field2 = new FieldDescription();
        $field2->setName('field2');

        $field3 = new FieldDescription();
        $field3->setName('field3');
        $field3->setOption('sortable', 'field3sortBy');

        $datagrid1
            ->expects($this->any())
            ->method('getValues')
            ->will($this->returnValue(array(
                '_sort_by'    => $field1,
                '_sort_order' => 'ASC',
            )));

        $datagrid2
            ->expects($this->any())
            ->method('getValues')
            ->will($this->returnValue(array(
                '_sort_by'    => $field3,
                '_sort_order' => 'ASC',
            )));

        $parameters = $manager->getSortParameters($field1, $datagrid1);

        $this->assertEquals('DESC', $parameters['filter']['_sort_order']);
        $this->assertEquals('field1', $parameters['filter']['_sort_by']);

        $parameters = $manager->getSortParameters($field2, $datagrid1);

        $this->assertEquals('ASC', $parameters['filter']['_sort_order']);
        $this->assertEquals('field2', $parameters['filter']['_sort_by']);

        $parameters = $manager->getSortParameters($field3, $datagrid1);

        $this->assertEquals('ASC', $parameters['filter']['_sort_order']);
        $this->assertEquals('field3sortBy', $parameters['filter']['_sort_by']);



        $parameters = $manager->getSortParameters($field3, $datagrid2);

        $this->assertEquals('DESC', $parameters['filter']['_sort_order']);
        $this->assertEquals('field3sortBy', $parameters['filter']['_sort_by']);
    }
}
