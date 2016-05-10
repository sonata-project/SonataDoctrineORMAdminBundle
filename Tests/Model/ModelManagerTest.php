<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Tests\Model;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Version;
use Sonata\DoctrineORMAdminBundle\Admin\FieldDescription;
use Sonata\DoctrineORMAdminBundle\Model\ModelManager;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\AssociatedEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\ContainerEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\Embeddable\EmbeddedEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\VersionedEntity;

class ModelManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testSortParameters()
    {
        $registry = $this->getMock('Symfony\Bridge\Doctrine\RegistryInterface');

        $manager = new ModelManager($registry);

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
                '_sort_by' => $field1,
                '_sort_order' => 'ASC',
            )));

        $datagrid2
            ->expects($this->any())
            ->method('getValues')
            ->will($this->returnValue(array(
                '_sort_by' => $field3,
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

    public function getVersionDataProvider()
    {
        return array(
            array(true),
            array(false),
        );
    }

    /**
     * @dataProvider getVersionDataProvider
     */
    public function testGetVersion($isVersioned)
    {
        $object = new VersionedEntity();

        $modelManager = $this->getMockBuilder('Sonata\DoctrineORMAdminBundle\Model\ModelManager')
            ->disableOriginalConstructor()
            ->setMethods(array('getMetadata'))
            ->getMock();

        $metadata = $this->getMetadata(get_class($object), $isVersioned);

        $modelManager->expects($this->any())
            ->method('getMetadata')
            ->will($this->returnValue($metadata));

        if ($isVersioned) {
            $object->version = 123;

            $this->assertNotNull($modelManager->getLockVersion($object));
        } else {
            $this->assertNull($modelManager->getLockVersion($object));
        }
    }

    public function lockDataProvider()
    {
        return array(
            array(true,  false),
            array(true,  true),
            array(false, false),
        );
    }

    /**
     * @dataProvider lockDataProvider
     */
    public function testLock($isVersioned, $expectsException)
    {
        $object = new VersionedEntity();

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(array('lock'))
            ->getMock();

        $modelManager = $this->getMockBuilder('Sonata\DoctrineORMAdminBundle\Model\ModelManager')
            ->disableOriginalConstructor()
            ->setMethods(array('getMetadata', 'getEntityManager'))
            ->getMock();

        $modelManager->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $metadata = $this->getMetadata(get_class($object), $isVersioned);

        $modelManager->expects($this->any())
            ->method('getMetadata')
            ->will($this->returnValue($metadata));

        if ($expectsException) {
            $em->expects($this->once())
                ->method('lock')
                ->will($this->throwException(OptimisticLockException::lockFailed($object)));

            $this->setExpectedException('Sonata\AdminBundle\Exception\LockException');
        }

        $modelManager->lock($object, 123);
    }

    private function getMetadata($class, $isVersioned)
    {
        $metadata = new ClassMetadata($class);

        $metadata->isVersioned = $isVersioned;

        if ($isVersioned) {
            $versionField = 'version';
            $metadata->versionField = $versionField;
            $metadata->reflFields[$versionField] = new \ReflectionProperty($class, $versionField);
        }

        return $metadata;
    }

    public function testGetParentMetadataForProperty()
    {
        if (version_compare(Version::VERSION, '2.5') < 0) {
            $this->markTestSkipped('Test for embeddables needs to run on Doctrine >= 2.5');

            return;
        }

        $containerEntityClass = 'Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\ContainerEntity';
        $associatedEntityClass = 'Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\AssociatedEntity';
        $embeddedEntityClass = 'Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\Embeddable\EmbeddedEntity';
        $modelManagerClass = 'Sonata\DoctrineORMAdminBundle\Model\ModelManager';

        $object = new ContainerEntity(new AssociatedEntity(), new EmbeddedEntity());

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ModelManager $modelManager */
        $modelManager = $this->getMockBuilder($modelManagerClass)
            ->disableOriginalConstructor()
            ->setMethods(array('getMetadata', 'getEntityManager'))
            ->getMock();

        $modelManager->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $containerEntityMetadata = $this->getMetadataForContainerEntity();
        $associatedEntityMetadata = $this->getMetadataForAssociatedEntity();
        $embeddedEntityMetadata = $this->getMetadataForEmbeddedEntity();

        $modelManager->expects($this->any())->method('getMetadata')
            ->will(
                $this->returnValueMap(
                    array(
                        array($containerEntityClass, $containerEntityMetadata),
                        array($embeddedEntityClass, $embeddedEntityMetadata),
                        array($associatedEntityClass, $associatedEntityMetadata),
                    )
                )
            );

        /** @var ClassMetadata $metadata */
        list($metadata, $lastPropertyName) = $modelManager
            ->getParentMetadataForProperty($containerEntityClass, 'plainField');
        $this->assertEquals($metadata->fieldMappings[$lastPropertyName]['type'], 'integer');

        list($metadata, $lastPropertyName) = $modelManager
            ->getParentMetadataForProperty($containerEntityClass, 'associatedEntity.plainField');
        $this->assertEquals($metadata->fieldMappings[$lastPropertyName]['type'], 'string');

        list($metadata, $lastPropertyName) = $modelManager
            ->getParentMetadataForProperty($containerEntityClass, 'embeddedEntity.plainField');
        $this->assertEquals($metadata->fieldMappings[$lastPropertyName]['type'], 'boolean');
    }

    public function getMetadataForEmbeddedEntity()
    {
        $metadata = new ClassMetadata('Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\Embeddable\EmbeddedEntity');

        $metadata->fieldMappings = array(
            'plainField' => array(
                'fieldName' => 'plainField',
                'columnName' => 'plainField',
                'type' => 'boolean',
            ),
        );

        return $metadata;
    }

    public function getMetadataForAssociatedEntity()
    {
        $metadata = new ClassMetadata('Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\AssociatedEntity');

        $metadata->fieldMappings = array(
            'plainField' => array(
                'fieldName' => 'plainField',
                'columnName' => 'plainField',
                'type' => 'string',
            ),
        );

        return $metadata;
    }

    public function getMetadataForContainerEntity()
    {
        $containerEntityClass = 'Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\ContainerEntity';
        $associatedEntityClass = 'Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\AssociatedEntity';
        $embeddedEntityClass = 'Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\Embeddable\EmbeddedEntity';

        $metadata = new ClassMetadata($containerEntityClass);

        $metadata->fieldMappings = array(
            'plainField' => array(
                'fieldName' => 'plainField',
                'columnName' => 'plainField',
                'type' => 'integer',
            ),
        );

        $metadata->associationMappings['associatedEntity'] = array(
            'fieldName' => 'associatedEntity',
            'targetEntity' => $associatedEntityClass,
            'sourceEntity' => $containerEntityClass,
        );

        $metadata->embeddedClasses['embeddedEntity'] = array(
            'class' => $embeddedEntityClass,
            'columnPrefix' => 'embeddedEntity',
        );

        $metadata->inlineEmbeddable('embeddedEntity', $this->getMetadataForEmbeddedEntity());

        return $metadata;
    }
}
