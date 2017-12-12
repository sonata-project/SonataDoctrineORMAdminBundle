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

namespace Sonata\DoctrineORMAdminBundle\Tests\Model;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Version;
use PHPUnit\Framework\TestCase;
use Sonata\DoctrineORMAdminBundle\Admin\FieldDescription;
use Sonata\DoctrineORMAdminBundle\Model\ModelManager;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType\UuidType;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\AssociatedEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\ContainerEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\Embeddable\EmbeddedEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\UuidEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\VersionedEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Util\NonIntegerIdentifierTestClass;

class ModelManagerTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!Type::hasType('uuid')) {
            Type::addType('uuid', 'Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType\UuidType');
        }
    }

    public function testSortParameters(): void
    {
        $registry = $this->createMock('Symfony\Bridge\Doctrine\RegistryInterface');

        $manager = new ModelManager($registry);

        $datagrid1 = $this->createMock('Sonata\AdminBundle\Datagrid\Datagrid');
        $datagrid2 = $this->createMock('Sonata\AdminBundle\Datagrid\Datagrid');

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
            ->will($this->returnValue([
                '_sort_by' => $field1,
                '_sort_order' => 'ASC',
            ]));

        $datagrid2
            ->expects($this->any())
            ->method('getValues')
            ->will($this->returnValue([
                '_sort_by' => $field3,
                '_sort_order' => 'ASC',
            ]));

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
        return [
            [true],
            [false],
        ];
    }

    /**
     * @dataProvider getVersionDataProvider
     */
    public function testGetVersion($isVersioned): void
    {
        $object = new VersionedEntity();

        $modelManager = $this->getMockBuilder('Sonata\DoctrineORMAdminBundle\Model\ModelManager')
            ->disableOriginalConstructor()
            ->setMethods(['getMetadata'])
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
        return [
            [true,  false],
            [true,  true],
            [false, false],
        ];
    }

    /**
     * @dataProvider lockDataProvider
     */
    public function testLock($isVersioned, $expectsException): void
    {
        $object = new VersionedEntity();

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['lock'])
            ->getMock();

        $modelManager = $this->getMockBuilder('Sonata\DoctrineORMAdminBundle\Model\ModelManager')
            ->disableOriginalConstructor()
            ->setMethods(['getMetadata', 'getEntityManager'])
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

            $this->expectException('Sonata\AdminBundle\Exception\LockException');
        }

        $modelManager->lock($object, 123);
    }

    public function testGetParentMetadataForProperty(): void
    {
        if (version_compare(Version::VERSION, '2.5') < 0) {
            $this->markTestSkipped('Test for embeddables needs to run on Doctrine >= 2.5');

            return;
        }

        $containerEntityClass = 'Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\ContainerEntity';
        $associatedEntityClass = 'Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\AssociatedEntity';
        $embeddedEntityClass = 'Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\Embeddable\EmbeddedEntity';
        $modelManagerClass = 'Sonata\DoctrineORMAdminBundle\Model\ModelManager';

        $object = new ContainerEntity(new AssociatedEntity(null, new EmbeddedEntity()), new EmbeddedEntity());

        $em = $this->createMock('Doctrine\ORM\EntityManager');

        /** @var \PHPUnit_Framework_MockObject_MockObject|ModelManager $modelManager */
        $modelManager = $this->getMockBuilder($modelManagerClass)
            ->disableOriginalConstructor()
            ->setMethods(['getMetadata', 'getEntityManager'])
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
                    [
                        [$containerEntityClass, $containerEntityMetadata],
                        [$embeddedEntityClass, $embeddedEntityMetadata],
                        [$associatedEntityClass, $associatedEntityMetadata],
                    ]
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

        list($metadata, $lastPropertyName) = $modelManager
            ->getParentMetadataForProperty($containerEntityClass, 'associatedEntity.embeddedEntity.plainField');
        $this->assertEquals($metadata->fieldMappings[$lastPropertyName]['type'], 'boolean');
    }

    public function getMetadataForEmbeddedEntity()
    {
        $metadata = new ClassMetadata('Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\Embeddable\EmbeddedEntity');

        $metadata->fieldMappings = [
            'plainField' => [
                'fieldName' => 'plainField',
                'columnName' => 'plainField',
                'type' => 'boolean',
            ],
        ];

        return $metadata;
    }

    public function getMetadataForAssociatedEntity()
    {
        $embeddedEntityClass = 'Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\Embeddable\EmbeddedEntity';

        $metadata = new ClassMetadata('Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\AssociatedEntity');

        $metadata->fieldMappings = [
            'plainField' => [
                'fieldName' => 'plainField',
                'columnName' => 'plainField',
                'type' => 'string',
            ],
        ];

        $metadata->embeddedClasses['embeddedEntity'] = [
            'class' => $embeddedEntityClass,
            'columnPrefix' => 'embeddedEntity',
        ];

        $metadata->inlineEmbeddable('embeddedEntity', $this->getMetadataForEmbeddedEntity());

        return $metadata;
    }

    public function getMetadataForContainerEntity()
    {
        $containerEntityClass = 'Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\ContainerEntity';
        $associatedEntityClass = 'Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\AssociatedEntity';
        $embeddedEntityClass = 'Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\Embeddable\EmbeddedEntity';

        $metadata = new ClassMetadata($containerEntityClass);

        $metadata->fieldMappings = [
            'plainField' => [
                'fieldName' => 'plainField',
                'columnName' => 'plainField',
                'type' => 'integer',
            ],
        ];

        $metadata->associationMappings['associatedEntity'] = [
            'fieldName' => 'associatedEntity',
            'targetEntity' => $associatedEntityClass,
            'sourceEntity' => $containerEntityClass,
        ];

        $metadata->embeddedClasses['embeddedEntity'] = [
            'class' => $embeddedEntityClass,
            'columnPrefix' => 'embeddedEntity',
        ];

        $metadata->inlineEmbeddable('embeddedEntity', $this->getMetadataForEmbeddedEntity());

        return $metadata;
    }

    public function testNonIntegerIdentifierType(): void
    {
        $uuid = new NonIntegerIdentifierTestClass('efbcfc4b-8c43-4d42-aa4c-d707e55151ac');
        $entity = new UuidEntity($uuid);

        $meta = $this->createMock('Doctrine\ORM\Mapping\ClassMetadataInfo');
        $meta->expects($this->any())
            ->method('getIdentifierValues')
            ->willReturn([$entity->getId()]);
        $meta->expects($this->any())
            ->method('getTypeOfField')
            ->willReturn(UuidType::NAME);

        $mf = $this->createMock('Doctrine\ORM\Mapping\ClassMetadataFactory');
        $mf->expects($this->any())
            ->method('getMetadataFor')
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

        $registry = $this->createMock('Symfony\Bridge\Doctrine\RegistryInterface');
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $manager = new ModelManager($registry);
        $result = $manager->getIdentifierValues($entity);

        $this->assertEquals($entity->getId()->toString(), $result[0]);
    }

    public function testAssociationIdentifierType(): void
    {
        $entity = new ContainerEntity(new AssociatedEntity(42, new EmbeddedEntity()), new EmbeddedEntity());

        $meta = $this->createMock('Doctrine\ORM\Mapping\ClassMetadataInfo');
        $meta->expects($this->any())
            ->method('getIdentifierValues')
            ->willReturn([$entity->getAssociatedEntity()->getPlainField()]);
        $meta->expects($this->any())
            ->method('getTypeOfField')
            ->willReturn(null);

        $mf = $this->createMock('Doctrine\ORM\Mapping\ClassMetadataFactory');
        $mf->expects($this->any())
            ->method('getMetadataFor')
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

        $registry = $this->createMock('Symfony\Bridge\Doctrine\RegistryInterface');
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $manager = new ModelManager($registry);
        $result = $manager->getIdentifierValues($entity);

        $this->assertSame(42, $result[0]);
    }

    /**
     * [sortBy, sortOrder, isAddOrderBy].
     *
     * @return array
     */
    public function getSortableInDataSourceIteratorDataProvider()
    {
        return [
            [null, null, false],
            ['', 'ASC', false],
            ['field', 'ASC', true],
            ['field', null, true],
        ];
    }

    /**
     * @dataProvider getSortableInDataSourceIteratorDataProvider
     *
     * @param string|null $sortBy
     * @param string|null $sortOrder
     * @param bool        $isAddOrderBy
     */
    public function testSortableInDataSourceIterator($sortBy, $sortOrder, $isAddOrderBy): void
    {
        $datagrid = $this->getMockForAbstractClass('Sonata\AdminBundle\Datagrid\DatagridInterface');
        $configuration = $this->getMockBuilder('Doctrine\ORM\Configuration')->getMock();
        $configuration->expects($this->any())
            ->method('getDefaultQueryHints')
            ->willReturn([]);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->setConstructorArgs([$em])
            ->getMock();
        $query = new Query($em);

        $proxyQuery = $this->getMockBuilder('Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery')
            ->setConstructorArgs([$queryBuilder])
            ->setMethods(['getSortBy', 'getSortOrder', 'getRootAliases'])
            ->getMock();

        $proxyQuery->expects($this->any())
            ->method('getSortOrder')
            ->willReturn($sortOrder);

        $proxyQuery->expects($this->any())
            ->method('getSortBy')
            ->willReturn($sortBy);

        $queryBuilder->expects($isAddOrderBy ? $this->atLeastOnce() : $this->never())
            ->method('addOrderBy');

        $proxyQuery->expects($this->any())
            ->method('getRootAliases')
            ->willReturn(['a']);

        $queryBuilder->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        $datagrid->expects($this->any())
            ->method('getQuery')
            ->willReturn($proxyQuery);

        $registry = $this->getMockBuilder('Symfony\Bridge\Doctrine\RegistryInterface')->getMock();
        $manager = new ModelManager($registry);
        $manager->getDataSourceIterator($datagrid, []);
    }

    public function testModelReverseTransform(): void
    {
        $class = 'Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\SimpleEntity';

        $metadataFactory = $this->createMock('Doctrine\ORM\Mapping\ClassMetadataFactory');
        $modelManager = $this->createMock('Doctrine\Common\Persistence\ObjectManager');
        $registry = $this->createMock('Symfony\Bridge\Doctrine\RegistryInterface');

        $classMetadata = new ClassMetadataInfo($class);
        $classMetadata->reflClass = new \ReflectionClass($class);

        $modelManager->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects($this->once())
            ->method('getMetadataFor')
            ->with($class)
            ->willReturn($classMetadata);
        $registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($class)
            ->willReturn($modelManager);

        $manager = new ModelManager($registry);
        $this->assertInstanceOf($class, $object = $manager->modelReverseTransform(
            $class,
            [
                'schmeckles' => 42,
                'multi_word_property' => 'hello',
            ]
        ));
        $this->assertSame(42, $object->getSchmeckles());
        $this->assertSame('hello', $object->getMultiWordProperty());
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
}
