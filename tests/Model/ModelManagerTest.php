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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Datagrid\Datagrid;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Exception\LockException;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Sonata\AdminBundle\Filter\FilterInterface;
use Sonata\DoctrineORMAdminBundle\Admin\FieldDescription;
use Sonata\DoctrineORMAdminBundle\Datagrid\OrderByToSelectWalker;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Model\ModelManager;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType\ProductIdType;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType\Uuid;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType\UuidBinaryType;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType\UuidType;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\AbstractEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\AssociatedEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\ContainerEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\Embeddable\EmbeddedEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\Embeddable\SubEmbeddedEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\Product;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\ProductId;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\ProtectedEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\SimpleEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\UuidBinaryEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\UuidEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\VersionedEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Util\NonIntegerIdentifierTestClass;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ModelManagerTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!Type::hasType(UuidType::NAME)) {
            Type::addType(UuidType::NAME, UuidType::class);
        }
        if (!Type::hasType(UuidBinaryType::NAME)) {
            Type::addType(UuidBinaryType::NAME, UuidBinaryType::class);
        }
        if (!Type::hasType(ProductIdType::NAME)) {
            Type::addType(ProductIdType::NAME, ProductIdType::class);
        }
    }

    public function testGetIdentifierValuesWhenIdentifierIsValueObjectWithToStringMethod()
    {
        $entity = new UuidBinaryEntity(new Uuid('a7ef873a-e7b5-11e9-81b4-2a2ae2dbcce4'));

        $platform = $this->createMock(MySqlPlatform::class);

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->any())->method('getDatabasePlatform')->willReturn($platform);

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects($this->any())->method('getIdentifierValues')->willReturn([$entity->getId()]);
        $classMetadata->expects($this->any())->method('getTypeOfField')->willReturn(UuidBinaryType::NAME);

        $classMetadataFactory = $this->createMock(ClassMetadataFactory::class);
        $classMetadataFactory->expects($this->any())->method('getMetadataFor')->willReturn($classMetadata);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())->method('getMetadataFactory')->willReturn($classMetadataFactory);
        $entityManager->expects($this->any())->method('getConnection')->willReturn($connection);

        $registry = $this->createMock(RegistryInterface::class);
        $registry->expects($this->any())->method('getManagerForClass')->willReturn($entityManager);

        $manager = new ModelManager($registry);

        $this->assertSame(
            ['a7ef873a-e7b5-11e9-81b4-2a2ae2dbcce4'],
            $manager->getIdentifierValues($entity)
        );
    }

    public function testInstantiateWithDeprecatedRegistryInterface(): void
    {
        $registry = $this->createMock(RegistryInterface::class);
        $manager = new ModelManager($registry);
        $em = $this->createMock(EntityManagerInterface::class);

        $registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('x')
            ->willReturn($em)
        ;
        $this->assertSame($em, $manager->getEntityManager('x'));
    }

    public function testSortParameters(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);

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
            ->willReturn([
                '_sort_by' => $field1,
                '_sort_order' => 'ASC',
            ]);

        $datagrid2
            ->expects($this->any())
            ->method('getValues')
            ->willReturn([
                '_sort_by' => $field3,
                '_sort_order' => 'ASC',
            ]);

        $parameters = $manager->getSortParameters($field1, $datagrid1);

        $this->assertSame('DESC', $parameters['filter']['_sort_order']);
        $this->assertSame('field1', $parameters['filter']['_sort_by']);

        $parameters = $manager->getSortParameters($field2, $datagrid1);

        $this->assertSame('ASC', $parameters['filter']['_sort_order']);
        $this->assertSame('field2', $parameters['filter']['_sort_by']);

        $parameters = $manager->getSortParameters($field3, $datagrid1);

        $this->assertSame('ASC', $parameters['filter']['_sort_order']);
        $this->assertSame('field3sortBy', $parameters['filter']['_sort_by']);

        $parameters = $manager->getSortParameters($field3, $datagrid2);

        $this->assertSame('DESC', $parameters['filter']['_sort_order']);
        $this->assertSame('field3sortBy', $parameters['filter']['_sort_by']);
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

        $modelManager = $this->getMockBuilder(ModelManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMetadata'])
            ->getMock();

        $metadata = $this->getMetadata(\get_class($object), $isVersioned);

        $modelManager->expects($this->any())
            ->method('getMetadata')
            ->willReturn($metadata);

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
            ->willReturn($em);

        $metadata = $this->getMetadata(\get_class($object), $isVersioned);

        $modelManager->expects($this->any())
            ->method('getMetadata')
            ->willReturn($metadata);

        $em->expects($isVersioned ? $this->once() : $this->never())
            ->method('lock');

        if ($expectsException) {
            $em->expects($this->once())
                ->method('lock')
                ->will($this->throwException(OptimisticLockException::lockFailed($object)));

            $this->expectException(LockException::class);
        }

        $modelManager->lock($object, 123);
    }

    public function testGetParentMetadataForProperty(): void
    {
        $containerEntityClass = ContainerEntity::class;
        $associatedEntityClass = AssociatedEntity::class;
        $embeddedEntityClass = EmbeddedEntity::class;
        $modelManagerClass = ModelManager::class;

        $em = $this->createMock(EntityManager::class);

        /** @var \PHPUnit_Framework_MockObject_MockObject|ModelManager $modelManager */
        $modelManager = $this->getMockBuilder($modelManagerClass)
            ->disableOriginalConstructor()
            ->setMethods(['getMetadata', 'getEntityManager'])
            ->getMock();

        $modelManager->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);

        $containerEntityMetadata = $this->getMetadataForContainerEntity();
        $associatedEntityMetadata = $this->getMetadataForAssociatedEntity();
        $embeddedEntityMetadata = $this->getMetadataForEmbeddedEntity();

        $modelManager->expects($this->any())->method('getMetadata')
            ->willReturnMap(

                    [
                        [$containerEntityClass, $containerEntityMetadata],
                        [$embeddedEntityClass, $embeddedEntityMetadata],
                        [$associatedEntityClass, $associatedEntityMetadata],
                    ]

            );

        /** @var ClassMetadata $metadata */
        list($metadata, $lastPropertyName) = $modelManager
            ->getParentMetadataForProperty($containerEntityClass, 'plainField');
        $this->assertSame($metadata->fieldMappings[$lastPropertyName]['type'], 'integer');

        list($metadata, $lastPropertyName) = $modelManager
            ->getParentMetadataForProperty($containerEntityClass, 'associatedEntity.plainField');
        $this->assertSame($metadata->fieldMappings[$lastPropertyName]['type'], 'string');

        list($metadata, $lastPropertyName) = $modelManager
            ->getParentMetadataForProperty($containerEntityClass, 'embeddedEntity.plainField');
        $this->assertSame($metadata->fieldMappings[$lastPropertyName]['type'], 'boolean');

        list($metadata, $lastPropertyName) = $modelManager
            ->getParentMetadataForProperty($containerEntityClass, 'associatedEntity.embeddedEntity.plainField');
        $this->assertSame($metadata->fieldMappings[$lastPropertyName]['type'], 'boolean');

        list($metadata, $lastPropertyName) = $modelManager
            ->getParentMetadataForProperty(
                $containerEntityClass,
                'associatedEntity.embeddedEntity.subEmbeddedEntity.plainField'
            );
        $this->assertSame($metadata->fieldMappings[$lastPropertyName]['type'], 'boolean');
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

    public function getMetadataForSubEmbeddedEntity()
    {
        $metadata = new ClassMetadata(SubEmbeddedEntity::class);

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
        $subEmbeddedEntityClass = SubEmbeddedEntity::class;

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
            'columnPrefix' => 'embedded_entity_',
        ];
        $metadata->embeddedClasses['embeddedEntity.subEmbeddedEntity'] = [
            'class' => $subEmbeddedEntityClass,
            'columnPrefix' => 'embedded_entity_sub_embedded_entity_',
            'declaredField' => 'embeddedEntity',
            'originalField' => 'subEmbeddedEntity',
        ];

        $metadata->inlineEmbeddable('embeddedEntity', $this->getMetadataForEmbeddedEntity());
        $metadata->inlineEmbeddable('embeddedEntity.subEmbeddedEntity', $this->getMetadataForSubEmbeddedEntity());

        return $metadata;
    }

    public function getMetadataForContainerEntity()
    {
        $containerEntityClass = ContainerEntity::class;
        $associatedEntityClass = AssociatedEntity::class;
        $embeddedEntityClass = EmbeddedEntity::class;
        $subEmbeddedEntityClass = SubEmbeddedEntity::class;

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
        $metadata->embeddedClasses['embeddedEntity.subEmbeddedEntity'] = [
            'class' => $subEmbeddedEntityClass,
            'columnPrefix' => 'embedded_entity_sub_embedded_entity_',
            'declaredField' => 'embeddedEntity',
            'originalField' => 'subEmbeddedEntity',
        ];

        $metadata->inlineEmbeddable('embeddedEntity', $this->getMetadataForEmbeddedEntity());
        $metadata->inlineEmbeddable('embeddedEntity.subEmbeddedEntity', $this->getMetadataForSubEmbeddedEntity());

        return $metadata;
    }

    public function testGetIdentifierValuesForIdInObjectTypeBinaryToStringSupport(): void
    {
        $uuid = new NonIntegerIdentifierTestClass('efbcfc4b-8c43-4d42-aa4c-d707e55151ac');

        $entity = new UuidEntity($uuid);

        $meta = $this->createMock(ClassMetadata::class);
        $meta->expects($this->any())
            ->method('getIdentifierValues')
            ->willReturn([$entity->getId()]);
        $meta->expects($this->any())
            ->method('getTypeOfField')
            ->willReturn(UuidBinaryType::NAME); //'uuid_binary'

        $mf = $this->createMock(ClassMetadataFactory::class);
        $mf->expects($this->any())
            ->method('getMetadataFor')
            ->willReturn($meta);

        $platform = $this->createMock(PostgreSqlPlatform::class);
        $platform->expects($this->any())
            ->method('hasDoctrineTypeMappingFor')
            ->with(UuidBinaryType::NAME)
            ->willReturn(true);
        $platform->expects($this->any())
            ->method('getDoctrineTypeMapping')
            ->with(UuidBinaryType::NAME)
            ->willReturn('binary');

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

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $manager = new ModelManager($registry);
        $result = $manager->getIdentifierValues($entity);

        $this->assertSame($entity->getId()->toString(), $result[0]);
    }

    public function testNonIntegerIdentifierType(): void
    {
        $uuid = new NonIntegerIdentifierTestClass('efbcfc4b-8c43-4d42-aa4c-d707e55151ac');
        $entity = new UuidEntity($uuid);

        $meta = $this->createMock(ClassMetadata::class);
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
        $platform->expects($this->any())
            ->method('hasDoctrineTypeMappingFor')
            ->with(UuidType::NAME)
            ->willReturn(false);
        $platform->expects($this->never())
            ->method('getDoctrineTypeMapping');

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

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $manager = new ModelManager($registry);
        $result = $manager->getIdentifierValues($entity);

        $this->assertSame($entity->getId()->toString(), $result[0]);
    }

    public function testIntegerIdentifierType(): void
    {
        $id = new ProductId(12345);
        $entity = new Product($id, 'Some product');

        $meta = $this->createMock(ClassMetadata::class);
        $meta->expects($this->any())
            ->method('getIdentifierValues')
            ->willReturn([$entity->getId()]);
        $meta->expects($this->any())
            ->method('getTypeOfField')
            ->willReturn(ProductIdType::NAME);

        $mf = $this->createMock(ClassMetadataFactory::class);
        $mf->expects($this->any())
            ->method('getMetadataFor')
            ->willReturn($meta);

        $platform = $this->createMock(PostgreSqlPlatform::class);
        $platform->expects($this->any())
            ->method('hasDoctrineTypeMappingFor')
            ->with(ProductIdType::NAME)
            ->willReturn(false);
        $platform->expects($this->never())
            ->method('getDoctrineTypeMapping');

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

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $manager = new ModelManager($registry);
        $result = $manager->getIdentifierValues($entity);

        $this->assertSame((string) $entity->getId()->getId(), $result[0]);
    }

    public function testAssociationIdentifierType(): void
    {
        $entity = new ContainerEntity(new AssociatedEntity(42, new EmbeddedEntity()), new EmbeddedEntity());

        $meta = $this->createMock(ClassMetadata::class);
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
        $platform->expects($this->never())
            ->method('hasDoctrineTypeMappingFor');

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

        $registry = $this->createMock(ManagerRegistry::class);
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

        if ($isAddOrderBy) {
            $this->assertArrayHasKey($key = 'doctrine.customTreeWalkers', $hints = $query->getHints());
            $this->assertContains(OrderByToSelectWalker::class, $hints[$key]);
        }
    }

    public function testModelReverseTransform(): void
    {
        $class = SimpleEntity::class;

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $modelManager = $this->createMock(ObjectManager::class);
        $registry = $this->createMock(ManagerRegistry::class);

        $classMetadata = new ClassMetadata($class);
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

    public function testCollections(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $model = new ModelManager($registry);

        $collection = $model->getModelCollectionInstance('whyDoWeEvenHaveThisParameter');
        $this->assertInstanceOf(ArrayCollection::class, $collection);

        $item1 = 'item1';
        $item2 = 'item2';
        $model->collectionAddElement($collection, $item1);
        $model->collectionAddElement($collection, $item2);

        $this->assertTrue($model->collectionHasElement($collection, $item1));

        $model->collectionRemoveElement($collection, $item1);

        $this->assertFalse($model->collectionHasElement($collection, $item1));

        $model->collectionClear($collection);

        $this->assertTrue($collection->isEmpty());
    }

    public function testModelTransform(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $model = new ModelManager($registry);

        $result = $model->modelTransform('thisIsNotUsed', 'doWeNeedThisMethod');

        $this->assertSame('doWeNeedThisMethod', $result);
    }

    public function testGetPaginationParameters(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $filter = $this->createMock(FilterInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);

        $datagrid->expects($this->once())
            ->method('getValues')
            ->willReturn(['_sort_by' => $filter]);

        $filter->expects($this->once())
            ->method('getName')
            ->willReturn($name = 'test');

        $model = new ModelManager($registry);

        $result = $model->getPaginationParameters($datagrid, $page = 5);

        $this->assertSame($page, $result['filter']['_page']);
        $this->assertSame($name, $result['filter']['_sort_by']);
    }

    public function testGetModelInstanceException(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);

        $model = new ModelManager($registry);

        $this->expectException(\RuntimeException::class);

        $model->getModelInstance(AbstractEntity::class);
    }

    public function testGetModelInstanceForProtectedEntity(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);

        $model = new ModelManager($registry);

        $this->assertInstanceOf(ProtectedEntity::class, $model->getModelInstance(ProtectedEntity::class));
    }

    public function testGetEntityManagerException(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);

        $model = new ModelManager($registry);

        $this->expectException(\RuntimeException::class);

        $model->getEntityManager(VersionedEntity::class);
    }

    public function testGetNewFieldDescriptionInstanceException(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);

        $model = new ModelManager($registry);

        $this->expectException(\RuntimeException::class);

        $model->getNewFieldDescriptionInstance(VersionedEntity::class, [], []);
    }

    /**
     * @dataProvider createUpdateRemoveData
     */
    public function testCreate($exception): void
    {
        $registry = $this->createMock(ManagerRegistry::class);

        $entityManger = $this->createMock(EntityManager::class);

        $registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($entityManger);

        $entityManger->expects($this->once())
            ->method('persist');

        $entityManger->expects($this->once())
            ->method('flush')
            ->willThrowException($exception);

        $model = new ModelManager($registry);

        $this->expectException(ModelManagerException::class);

        $model->create(new VersionedEntity());
    }

    public function createUpdateRemoveData()
    {
        return [
            'PDOException' => [
                new \PDOException(),
            ],
            'DBALException' => [
                new DBALException(),
            ],
        ];
    }

    /**
     * @dataProvider createUpdateRemoveData
     */
    public function testUpdate($exception): void
    {
        $registry = $this->createMock(ManagerRegistry::class);

        $entityManger = $this->createMock(EntityManager::class);

        $registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($entityManger);

        $entityManger->expects($this->once())
            ->method('persist');

        $entityManger->expects($this->once())
            ->method('flush')
            ->willThrowException($exception);

        $model = new ModelManager($registry);

        $this->expectException(ModelManagerException::class);

        $model->update(new VersionedEntity());
    }

    /**
     * @dataProvider createUpdateRemoveData
     */
    public function testRemove($exception): void
    {
        $registry = $this->createMock(ManagerRegistry::class);

        $entityManger = $this->createMock(EntityManager::class);

        $registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($entityManger);

        $entityManger->expects($this->once())
            ->method('remove');

        $entityManger->expects($this->once())
            ->method('flush')
            ->willThrowException($exception);

        $model = new ModelManager($registry);

        $this->expectException(ModelManagerException::class);

        $model->delete(new VersionedEntity());
    }

    public function testFindBadId(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);

        $model = new ModelManager($registry);

        $this->assertNull($model->find('notImportant', null));
    }

    public function testGetUrlsafeIdentifierException(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);

        $model = new ModelManager($registry);

        $this->expectException(\RuntimeException::class);

        $model->getNormalizedIdentifier('test');
    }

    public function testGetUrlsafeIdentifierNull(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);

        $model = new ModelManager($registry);

        $this->assertNull($model->getNormalizedIdentifier(null));
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
