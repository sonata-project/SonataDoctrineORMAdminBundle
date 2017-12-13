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

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Version;
use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Datagrid\Datagrid;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Exception\LockException;
use Sonata\DoctrineORMAdminBundle\Admin\FieldDescription;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Model\ModelManager;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType\UuidType;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\AssociatedEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\ContainerEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\Embeddable\EmbeddedEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\SimpleEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\UuidEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\VersionedEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Util\NonIntegerIdentifierTestClass;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ModelManagerTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        if (!Type::hasType('uuid')) {
            Type::addType('uuid', UuidType::class);
        }
    }

    public function testSortParameters()
    {
        $registry = $this->createMock(RegistryInterface::class);

        $manager = new ModelManager($registry);

        $datagrid1 = $this->createMock(Datagrid::class);
        $datagrid2 = $this->createMock(Datagrid::class);

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
    public function testGetVersion($isVersioned)
    {
        $object = new VersionedEntity();

        $modelManager = $this->getMockBuilder(ModelManager::class)
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
    public function testLock($isVersioned, $expectsException)
    {
        $object = new VersionedEntity();

        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['lock'])
            ->getMock();

        $modelManager = $this->getMockBuilder(ModelManager::class)
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

            $this->expectException(LockException::class);
        }

        $modelManager->lock($object, 123);
    }

    public function testGetParentMetadataForProperty()
    {
        if (version_compare(Version::VERSION, '2.5') < 0) {
            $this->markTestSkipped('Test for embeddables needs to run on Doctrine >= 2.5');

            return;
        }

        $containerEntityClass = ContainerEntity::class;
        $associatedEntityClass = AssociatedEntity::class;
        $embeddedEntityClass = EmbeddedEntity::class;
        $modelManagerClass = ModelManager::class;

        $object = new ContainerEntity(new AssociatedEntity(null, new EmbeddedEntity()), new EmbeddedEntity());

        $em = $this->createMock(EntityManager::class);

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
        $metadata = new ClassMetadata(EmbeddedEntity::class);

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
        $embeddedEntityClass = EmbeddedEntity::class;

        $metadata = new ClassMetadata(AssociatedEntity::class);

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
        $containerEntityClass = ContainerEntity::class;
        $associatedEntityClass = AssociatedEntity::class;
        $embeddedEntityClass = EmbeddedEntity::class;

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

    public function testNonIntegerIdentifierType()
    {
        $uuid = new NonIntegerIdentifierTestClass('efbcfc4b-8c43-4d42-aa4c-d707e55151ac');
        $entity = new UuidEntity($uuid);

        $meta = $this->createMock(ClassMetadataInfo::class);
        $meta->expects($this->any())
            ->method('getIdentifierValues')
            ->willReturn([$entity->getId()]);
        $meta->expects($this->any())
            ->method('getTypeOfField')
            ->willReturn(UuidType::NAME);

        $mf = $this->createMock(ClassMetadataFactory::class);
        $mf->expects($this->any())
            ->method('getMetadataFor')
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

        $registry = $this->createMock(RegistryInterface::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $manager = new ModelManager($registry);
        $result = $manager->getIdentifierValues($entity);

        $this->assertEquals($entity->getId()->toString(), $result[0]);
    }

    public function testAssociationIdentifierType()
    {
        $entity = new ContainerEntity(new AssociatedEntity(42, new EmbeddedEntity()), new EmbeddedEntity());

        $meta = $this->createMock(ClassMetadataInfo::class);
        $meta->expects($this->any())
            ->method('getIdentifierValues')
            ->willReturn([$entity->getAssociatedEntity()->getPlainField()]);
        $meta->expects($this->any())
            ->method('getTypeOfField')
            ->willReturn(null);

        $mf = $this->createMock(ClassMetadataFactory::class);
        $mf->expects($this->any())
            ->method('getMetadataFor')
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

        $registry = $this->createMock(RegistryInterface::class);
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
    public function testSortableInDataSourceIterator($sortBy, $sortOrder, $isAddOrderBy)
    {
        $datagrid = $this->getMockForAbstractClass(DatagridInterface::class);
        $configuration = $this->getMockBuilder(Configuration::class)->getMock();
        $configuration->expects($this->any())
            ->method('getDefaultQueryHints')
            ->willReturn([]);

        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->setConstructorArgs([$em])
            ->getMock();
        $query = new Query($em);

        $proxyQuery = $this->getMockBuilder(ProxyQuery::class)
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

        $registry = $this->getMockBuilder(RegistryInterface::class)->getMock();
        $manager = new ModelManager($registry);
        $manager->getDataSourceIterator($datagrid, []);
    }

    public function testModelReverseTransform()
    {
        $class = SimpleEntity::class;

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $modelManager = $this->createMock(ObjectManager::class);
        $registry = $this->createMock(RegistryInterface::class);

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
