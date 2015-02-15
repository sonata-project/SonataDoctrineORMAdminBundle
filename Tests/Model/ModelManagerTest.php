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
    /**
     * @var \Symfony\Bridge\Doctrine\RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    /**
     * @var ModelManager
     */
    private $manager;

    protected function setUp()
    {
        $this->registry = $this->getMock('Symfony\Bridge\Doctrine\RegistryInterface');
        $this->manager = new ModelManager($this->registry);
    }

    public function testSortParameters()
    {
        $manager = $this->manager;

        $datagrid1 = $this->mockDatagrid();
        $datagrid2 = $this->mockDatagrid();

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

    public function testGetMetadata()
    {
        $class = 'MySexyClass';

        $entityManager = $this->mockGetEntityManager($class);
        $metadataFactory = $this->mockGetMetadataFactory($entityManager);
        $metadata = $this->mockGetEntityMetadata($metadataFactory, $class);

        $this->assertEquals(
            $metadata,
            $this->manager->getMetadata($class)
        );
    }

    public function testGetEntityManager()
    {
        $class = 'stdClass';

        $entityManager = $this->mockGetEntityManager($class);

        $this->assertEquals(
            $entityManager,
            $this->manager->getEntityManager($class)
        );

        $this->assertEquals(
            $entityManager,
            $this->manager->getEntityManager('\stdClass'),
            'Expected getEntityManager to use left trim \\'
        );

        $this->assertEquals(
            $entityManager,
            $this->manager->getEntityManager(new \stdClass()),
            'Expected getEntityManager to use get_class'
        );
    }

    public function testGetUnknownEntityManager()
    {
        $class = 'stdClass';
        $this->setExpectedException('\RuntimeException', sprintf('No entity manager defined for class %s', $class));

        $this->mockGetEntityManager($class, null);

        $this->manager->getEntityManager($class);
    }

    /**
     * @dataProvider dataFindString
     */
    public function testFindWithString($class, $identifierFields, $identifier, $expectedIdentifier)
    {
        $entity = new \stdClass();

        $entityManager = $this->mockGetEntityManager($class);
        $metadataFactory = $this->mockGetMetadataFactory($entityManager);

        $metadata = $this->mockGetEntityMetadata($metadataFactory, $class);
        $metadata->expects($this->atLeastOnce())
            ->method('getIdentifierFieldNames')
            ->willReturn($identifierFields);

        $repository = $this->mockGetEntityRepository($entityManager, $class);
        $repository->expects($this->once())
            ->method('find')
            ->with($expectedIdentifier)
            ->willReturn($entity);

        $this->assertEquals($entity, $this->manager->find($class, $identifier));
    }

    public function dataFindString()
    {
        return array(
            array('stdClass', array('id'), '1', array('id' => '1')),
            array('stdClass', array('id1', 'id2'), '1~2', array('id1' => '1', 'id2' => '2')),
        );
    }

    /**
     * @dataProvider dataFindOther
     */
    public function testFindWithOtherIdentifiers($class, $identifier)
    {
        $entity = new \stdClass();

        $entityManager = $this->mockGetEntityManager($class);
        $repository = $this->mockGetEntityRepository($entityManager, $class);
        $repository->expects($this->once())
            ->method('find')
            ->with($identifier)
            ->willReturn($entity);

        $this->assertEquals($entity, $this->manager->find($class, $identifier));
    }

    public function dataFindOther()
    {
        $idObject = new \stdClass();
        $idList = array(1,2,3);

        return array(
            array('stdClass', $idObject),
            array('stdClass', $idList)
        );
    }

    public function testGetIdentifierValuesForManagedEntity()
    {
        $entity = new \stdClass();
        $identifiers = array('ola', 'senior');

        $entityManager = $this->mockGetEntityManager('stdClass');
        $uow = $this->mockGetUnitOfWork($entityManager);

        $uow->expects($this->atLeastOnce())
            ->method('isInIdentityMap')
            ->with($entity)
            ->willReturn(true);

        $uow->expects($this->once())
            ->method('getEntityIdentifier')
            ->with($entity)
            ->willReturn($identifiers);

        $this->assertEquals(
            $identifiers,
            $this->manager->getIdentifierValues($entity)
        );
    }

    /**
     * @dataProvider dataNewIdentifierValues
     */
    public function testGetIdentifierValuesForNewEntity($identifiers)
    {
        $entity = new \stdClass();
        $entityClass = 'stdClass';

        $entityManager = $this->mockGetEntityManager($entityClass);
        $metadataFactory = $this->mockGetMetadataFactory($entityManager);

        $uow = $this->mockGetUnitOfWork($entityManager);
        $uow->expects($this->atLeastOnce())
            ->method('isInIdentityMap')
            ->with($entity)
            ->willReturn(false);

        $metadata = $this->mockGetEntityMetadata($metadataFactory, $entityClass);
        $metadata->expects($this->atLeastOnce())
            ->method('getIdentifierValues')
            ->with($entity)
            ->willReturn($identifiers);

        $this->assertEquals(
            $identifiers,
            $this->manager->getIdentifierValues($entity)
        );
    }

    public function dataNewIdentifierValues()
    {
        return array(
            array(array('id' => 'value')),
            array(array('event' => 'name', 'createdAt' => new \DateTime()))
        );
    }

    public function testGetIdentifierValuesForNewEntityWithAssociationKeys()
    {
        $entity = new \stdClass();
        $entityClass = 'stdClass';

        $association = new \DateTime();
        $associationClass = 'DateTime';

        $entityManager = $this->mockGetEntityManager($entityClass);
        $metadataFactory = $this->mockGetMetadataFactory($entityManager);

        // Entity setup
        $uow = $this->mockGetUnitOfWork($entityManager);
        $uow->expects($this->atLeastOnce())
            ->method('isInIdentityMap')
            ->with($entity)
            ->willReturn(false);

        $metadata = $this->mockGetEntityMetadata($metadataFactory, $entityClass);
        $metadata->associationMappings = array(
            'fk_object' => array('targetEntity' => $associationClass),
            'fk_simple' => array('targetEntity' => 'InvalidIShouldNotBeUsedEver')
        );
        $metadata->expects($this->atLeastOnce())
            ->method('getIdentifierValues')
            ->with($entity)
            ->willReturn(array(
                'id' => '1',
                'fk_simple' => 'x',
                'fk_object' => $association
            ));

        // Association setup
        $associationMetadata = $this->mockClassMetadata();
        $associationMetadata->identifier = array('id');

        $uow->expects($this->atLeastOnce())
            ->method('getEntityIdentifier')
            ->with($association)
            ->willReturn(array('id' => $association->getTimestamp()));
        $entityManager->expects($this->atLeastOnce())
            ->method('getClassMetadata')
            ->with($associationClass)
            ->willReturn($associationMetadata);

        $this->assertEquals(
            array(
                'id' => '1',
                'fk_simple' => 'x',
                'fk_object' => $association->getTimestamp()
            ),
            $this->manager->getIdentifierValues($entity)
        );
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject|\Doctrine\ORM\Mapping\ClassMetadataFactory $metadataFactory
     * @param string $class
     * @return \PHPUnit_Framework_MockObject_MockObject|\Doctrine\ORM\Mapping\ClassMetadata
     */
    private function mockGetEntityMetadata($metadataFactory, $class)
    {
        $metadata = $this->mockClassMetadata();

        $metadataFactory->expects($this->atLeastOnce())
            ->method('getMetadataFor')
            ->with($class)
            ->willReturn($metadata);

        return $metadata;
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject|\Doctrine\ORM\EntityManager $entityManager
     * @return \PHPUnit_Framework_MockObject_MockObject|\Doctrine\ORM\Mapping\ClassMetadataFactory
     */
    private function mockGetMetadataFactory($entityManager)
    {
        $metadataFactory = $this->getMockBuilder('\Doctrine\ORM\Mapping\ClassMetadataFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this->atLeastOnce())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);

        return $metadataFactory;
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject|\Doctrine\ORM\EntityManager $entityManager
     * @param string $class
     * @return \PHPUnit_Framework_MockObject_MockObject|\Doctrine\ORM\EntityRepository
     */
    private function mockGetEntityRepository($entityManager, $class)
    {
        $repository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this->atLeastOnce())
            ->method('getRepository')
            ->with($class)
            ->willReturn($repository);

        return $repository;
    }

    /**
     * @param string $class
     * @param mixed $entityManager A custom entity manager return value
     * @return mixed|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockGetEntityManager($class, $entityManager = true)
    {
        if ($entityManager === true) {
            $entityManager = $this->mockEntityManager();
        }

        // Use once since the ModelManager::getEntityManager must use it's cache
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($class)
            ->willReturn($entityManager);

        return $entityManager;
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject|\Doctrine\ORM\EntityManager $entityManager
     * @return \PHPUnit_Framework_MockObject_MockObject|\Doctrine\ORM\UnitOfWork
     */
    private function mockGetUnitOfWork($entityManager)
    {
        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this->atLeastOnce())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        return $uow;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Sonata\AdminBundle\Datagrid\Datagrid
     */
    private function mockDatagrid()
    {
        return $this->getMockBuilder('\Sonata\AdminBundle\Datagrid\Datagrid')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Doctrine\ORM\Mapping\ClassMetadata
     */
    private function mockClassMetadata()
    {
        $metadata = $this->getMockBuilder('\Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        return $metadata;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function mockEntityManager()
    {
        $entityManager = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        return $entityManager;
    }
}
