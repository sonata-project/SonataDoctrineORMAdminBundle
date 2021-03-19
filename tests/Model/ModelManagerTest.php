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
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\AdminBundle\Datagrid\Datagrid;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Exception\LockException;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Sonata\DoctrineORMAdminBundle\Admin\FieldDescription;
use Sonata\DoctrineORMAdminBundle\Datagrid\OrderByToSelectWalker;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Model\ModelManager;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType\ProductIdType;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType\UuidBinaryType;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType\UuidType;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType\ValueObjectWithMagicToStringImpl;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType\ValueObjectWithToStringImpl;
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
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class ModelManagerTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @var ManagerRegistry|MockObject
     */
    private $registry;

    /**
     * @var ModelManager
     */
    private $modelManager;

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

    protected function setup(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->modelManager = new ModelManager($this->registry, PropertyAccess::createPropertyAccessor());
    }

    public function valueObjectDataProvider(): array
    {
        return [
            'value object with toString implementation' => [ValueObjectWithToStringImpl::class],
            'value object with magic toString implementation' => [ValueObjectWithMagicToStringImpl::class],
        ];
    }

    /**
     * @dataProvider valueObjectDataProvider
     */
    public function testGetIdentifierValuesWhenIdentifierIsValueObjectWithToStringMethod(string $vbClassName): void
    {
        $entity = new UuidBinaryEntity(new $vbClassName('a7ef873a-e7b5-11e9-81b4-2a2ae2dbcce4'));

        $platform = $this->createMock(MySqlPlatform::class);

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);

        $classMetadata = $this->createMock(ClassMetadataInfo::class);
        $classMetadata->method('getIdentifierValues')->willReturn([$entity->getId()]);
        $classMetadata->method('getTypeOfField')->willReturn(UuidBinaryType::NAME);

        $classMetadataFactory = $this->createMock(ClassMetadataFactory::class);
        $classMetadataFactory->method('getMetadataFor')->willReturn($classMetadata);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getMetadataFactory')->willReturn($classMetadataFactory);
        $entityManager->method('getConnection')->willReturn($connection);

        $this->registry->method('getManagerForClass')->willReturn($entityManager);

        $this->assertSame(
            ['a7ef873a-e7b5-11e9-81b4-2a2ae2dbcce4'],
            $this->modelManager->getIdentifierValues($entity)
        );
    }

    public function testInstantiateWithDeprecatedRegistryInterface(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('x')
            ->willReturn($em)
        ;
        $this->assertSame($em, $this->modelManager->getEntityManager('x'));
    }

    /**
     * @dataProvider supportsQueryDataProvider
     */
    public function testSupportsQuery(bool $expected, object $object): void
    {
        $this->assertSame($expected, $this->modelManager->supportsQuery($object));
    }

    public function supportsQueryDataProvider(): iterable
    {
        yield [true, $this->createMock(ProxyQuery::class)];
        yield [true, $this->createMock(QueryBuilder::class)];
        yield [false, new \stdClass()];
    }

    /**
     * NEXT_MAJOR: Remove this test.
     *
     * @group legacy
     */
    public function testSortParameters(): void
    {
        $datagrid1 = $this->createMock(Datagrid::class);
        $datagrid2 = $this->createMock(Datagrid::class);

        $field1 = new FieldDescription('field1');

        $field2 = new FieldDescription('field2');

        $field3 = new FieldDescription('field3');
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

        $this->expectDeprecation('Method Sonata\DoctrineORMAdminBundle\Model\ModelManager::getSortParameters() is deprecated since sonata-project/doctrine-orm-admin-bundle 3.23 and will be removed in version 4.0.');
        $parameters = $this->modelManager->getSortParameters($field1, $datagrid1);

        $this->assertSame('DESC', $parameters['filter']['_sort_order']);
        $this->assertSame('field1', $parameters['filter']['_sort_by']);

        $parameters = $this->modelManager->getSortParameters($field2, $datagrid1);

        $this->assertSame('ASC', $parameters['filter']['_sort_order']);
        $this->assertSame('field2', $parameters['filter']['_sort_by']);

        $parameters = $this->modelManager->getSortParameters($field3, $datagrid1);

        $this->assertSame('ASC', $parameters['filter']['_sort_order']);
        $this->assertSame('field3sortBy', $parameters['filter']['_sort_by']);

        $parameters = $this->modelManager->getSortParameters($field3, $datagrid2);

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

    /**
     * NEXT_MAJOR: Remove this test.
     *
     * @group legacy
     */
    public function testGetParentMetadataForProperty(): void
    {
        $containerEntityClass = ContainerEntity::class;
        $associatedEntityClass = AssociatedEntity::class;
        $embeddedEntityClass = EmbeddedEntity::class;
        $modelManagerClass = ModelManager::class;

        $em = $this->createMock(EntityManager::class);

        /** @var MockObject|ModelManager $modelManager */
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
                    // NEXT_MAJOR: Remove the 'sonata_deprecation_mute'
                    [$containerEntityClass, 'sonata_deprecation_mute', $containerEntityMetadata],
                    [$embeddedEntityClass, 'sonata_deprecation_mute', $embeddedEntityMetadata],
                    [$associatedEntityClass, 'sonata_deprecation_mute', $associatedEntityMetadata],
                ]
            );

        /** @var ClassMetadata $metadata */
        [$metadata, $lastPropertyName] = $modelManager
            ->getParentMetadataForProperty($containerEntityClass, 'plainField');
        $this->assertSame($metadata->fieldMappings[$lastPropertyName]['type'], 'integer');

        [$metadata, $lastPropertyName] = $modelManager
            ->getParentMetadataForProperty($containerEntityClass, 'associatedEntity.plainField');
        $this->assertSame($metadata->fieldMappings[$lastPropertyName]['type'], 'string');

        [$metadata, $lastPropertyName] = $modelManager
            ->getParentMetadataForProperty($containerEntityClass, 'embeddedEntity.plainField');
        $this->assertSame($metadata->fieldMappings[$lastPropertyName]['type'], 'boolean');

        [$metadata, $lastPropertyName] = $modelManager
            ->getParentMetadataForProperty($containerEntityClass, 'associatedEntity.embeddedEntity.plainField');
        $this->assertSame($metadata->fieldMappings[$lastPropertyName]['type'], 'boolean');

        [$metadata, $lastPropertyName] = $modelManager
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

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $result = $this->modelManager->getIdentifierValues($entity);

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

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $result = $this->modelManager->getIdentifierValues($entity);

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

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $result = $this->modelManager->getIdentifierValues($entity);

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

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $result = $this->modelManager->getIdentifierValues($entity);

        $this->assertSame(42, $result[0]);
    }

    /**
     * NEXT_MAJOR: Remove this dataprovider.
     *
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
     * NEXT_MAJOR: Remove this test.
     *
     * @group legacy
     *
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

        $this->expectDeprecation('Method Sonata\DoctrineORMAdminBundle\Model\ModelManager::getDataSourceIterator() is deprecated since sonata-project/doctrine-orm-admin-bundle 3.27 and will be removed in 4.0.');
        $this->modelManager->getDataSourceIterator($datagrid, []);

        if ($isAddOrderBy) {
            $this->assertArrayHasKey($key = 'doctrine.customTreeWalkers', $hints = $query->getHints());
            $this->assertContains(OrderByToSelectWalker::class, $hints[$key]);
        }
    }

    public function testModelReverseTransform(): void
    {
        $class = SimpleEntity::class;

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $objectManager = $this->createMock(ObjectManager::class);

        $classMetadata = new ClassMetadata($class);
        $classMetadata->reflClass = new \ReflectionClass($class);

        $objectManager->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects($this->once())
            ->method('getMetadataFor')
            ->with($class)
            ->willReturn($classMetadata);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($class)
            ->willReturn($objectManager);

        $this->assertInstanceOf($class, $object = $this->modelManager->modelReverseTransform(
            $class,
            [
                'schmeckles' => 42,
                'multi_word_property' => 'hello',
            ]
        ));
        $this->assertSame(42, $object->getSchmeckles());
        $this->assertSame('hello', $object->getMultiWordProperty());
    }

    public function testReverseTransform(): void
    {
        $object = new SimpleEntity();
        $class = SimpleEntity::class;

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $objectManager = $this->createMock(ObjectManager::class);

        $classMetadata = new ClassMetadata($class);
        $classMetadata->reflClass = new \ReflectionClass($class);

        $objectManager->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects($this->once())
            ->method('getMetadataFor')
            ->with($class)
            ->willReturn($classMetadata);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($class)
            ->willReturn($objectManager);

        $this->modelManager->reverseTransform($object, [
            'schmeckles' => 42,
            'multi_word_property' => 'hello',
        ]);
        $this->assertSame(42, $object->getSchmeckles());
        $this->assertSame('hello', $object->getMultiWordProperty());
    }

    /**
     * NEXT_MAJOR: Remove this test.
     *
     * @group legacy
     */
    public function testCollections(): void
    {
        $this->expectDeprecation('Method Sonata\DoctrineORMAdminBundle\Model\ModelManager::getModelCollectionInstance() is deprecated since sonata-project/doctrine-orm-admin-bundle 3.23 and will be removed in version 4.0.');
        $collection = $this->modelManager->getModelCollectionInstance('whyDoWeEvenHaveThisParameter');
        $this->assertInstanceOf(ArrayCollection::class, $collection);

        $item1 = 'item1';
        $item2 = 'item2';
        $this->modelManager->collectionAddElement($collection, $item1);
        $this->modelManager->collectionAddElement($collection, $item2);

        $this->assertTrue($this->modelManager->collectionHasElement($collection, $item1));

        $this->modelManager->collectionRemoveElement($collection, $item1);

        $this->assertFalse($this->modelManager->collectionHasElement($collection, $item1));

        $this->modelManager->collectionClear($collection);

        $this->assertTrue($collection->isEmpty());
    }

    /**
     * NEXT_MAJOR: Remove this test.
     *
     * @group legacy
     */
    public function testModelTransform(): void
    {
        $this->expectDeprecation('Method Sonata\DoctrineORMAdminBundle\Model\ModelManager::modelTransform() is deprecated since sonata-project/doctrine-orm-admin-bundle 3.27 and will be removed in version 4.0.');

        $result = $this->modelManager->modelTransform('thisIsNotUsed', 'doWeNeedThisMethod');

        $this->assertSame('doWeNeedThisMethod', $result);
    }

    /**
     * NEXT_MAJOR: Remove this test.
     *
     * @group legacy
     */
    public function testGetPaginationParameters(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $field = $this->createMock(FieldDescriptionInterface::class);

        $datagrid->expects($this->once())
            ->method('getValues')
            ->willReturn(['_sort_by' => $field]);

        $field->expects($this->once())
            ->method('getName')
            ->willReturn($name = 'test');

        $this->expectDeprecation('Method Sonata\DoctrineORMAdminBundle\Model\ModelManager::getPaginationParameters() is deprecated since sonata-project/doctrine-orm-admin-bundle 3.23 and will be removed in version 4.0.');
        $result = $this->modelManager->getPaginationParameters($datagrid, $page = 5);

        $this->assertSame($page, $result['filter']['_page']);
        $this->assertSame($name, $result['filter']['_sort_by']);
    }

    /**
     * NEXT_MAJOR: Remove this test.
     *
     * @group legacy
     */
    public function testGetModelInstanceException(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->modelManager->getModelInstance(AbstractEntity::class);
    }

    /**
     * NEXT_MAJOR: Remove this test.
     *
     * @group legacy
     */
    public function testGetModelInstanceForProtectedEntity(): void
    {
        $this->assertInstanceOf(ProtectedEntity::class, $this->modelManager->getModelInstance(ProtectedEntity::class));
    }

    public function testGetEntityManagerException(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->modelManager->getEntityManager(VersionedEntity::class);
    }

    /**
     * NEXT_MAJOR: Remove this test.
     *
     * @group legacy
     */
    public function testGetNewFieldDescriptionInstanceException(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->modelManager->getNewFieldDescriptionInstance(VersionedEntity::class, [], []);
    }

    /**
     * @dataProvider createUpdateRemoveData
     */
    public function testCreate($exception): void
    {
        $entityManger = $this->createMock(EntityManager::class);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($entityManger);

        $entityManger->expects($this->once())
            ->method('persist');

        $entityManger->expects($this->once())
            ->method('flush')
            ->willThrowException($exception);

        $this->expectException(ModelManagerException::class);

        $this->modelManager->create(new VersionedEntity());
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
        $entityManger = $this->createMock(EntityManager::class);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($entityManger);

        $entityManger->expects($this->once())
            ->method('persist');

        $entityManger->expects($this->once())
            ->method('flush')
            ->willThrowException($exception);

        $this->expectException(ModelManagerException::class);

        $this->modelManager->update(new VersionedEntity());
    }

    /**
     * @dataProvider createUpdateRemoveData
     */
    public function testRemove($exception): void
    {
        $entityManger = $this->createMock(EntityManager::class);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($entityManger);

        $entityManger->expects($this->once())
            ->method('remove');

        $entityManger->expects($this->once())
            ->method('flush')
            ->willThrowException($exception);

        $this->expectException(ModelManagerException::class);

        $this->modelManager->delete(new VersionedEntity());
    }

    /**
     * NEXT_MAJOR: Remove this method.
     *
     * @group legacy
     *
     * @expectedDeprecation Passing null as argument 1 for Sonata\DoctrineORMAdminBundle\Model\ModelManager::find() is deprecated since sonata-project/doctrine-orm-admin-bundle 3.20 and will be not allowed in version 4.0.
     */
    public function testFindBadId(): void
    {
        $this->assertNull($this->modelManager->find('notImportant', null));
    }

    /**
     * @dataProvider getWrongEntities
     *
     * @param mixed $entity
     */
    public function testNormalizedIdentifierException($entity): void
    {
        $this->expectException(\RuntimeException::class);

        $this->modelManager->getNormalizedIdentifier($entity);
    }

    public function getWrongEntities(): iterable
    {
        yield [0];
        yield [1];
        yield [false];
        yield [true];
        yield [[]];
        yield [''];
        yield ['sonata-project'];
    }

    /**
     * NEXT_MAJOR: Remove this method.
     *
     * @group legacy
     *
     * @expectedDeprecation Passing null as argument 1 for Sonata\DoctrineORMAdminBundle\Model\ModelManager::getNormalizedIdentifier() is deprecated since sonata-project/doctrine-orm-admin-bundle 3.20 and will be not allowed in version 4.0.
     */
    public function testGetUrlsafeIdentifierNull(): void
    {
        $this->assertNull($this->modelManager->getNormalizedIdentifier(null));
    }

    /**
     * @dataProvider addIdentifiersToQueryProvider
     */
    public function testAddIdentifiersToQuery(array $expectedParameters, array $identifierFieldNames, array $ids): void
    {
        $modelManager = $this->getMockBuilder(ModelManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIdentifierFieldNames'])
            ->getMock();

        $modelManager
            ->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn($identifierFieldNames);

        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->setConstructorArgs([$em])
            ->setMethodsExcept(['getParameters', 'setParameter'])
            ->getMock();

        $queryBuilder
            ->expects($this->exactly(\count($expectedParameters)))
            ->method('getRootAliases')
            ->willReturn(['p']);

        $queryBuilder
            ->expects($this->once())
            ->method('andWhere')
            ->with($this->stringContains(sprintf('( p.%s = :field_', $identifierFieldNames[0])));

        $proxyQuery = $this->getMockBuilder(ProxyQuery::class)
            ->setConstructorArgs([$queryBuilder])
            ->setMethods(['getSortBy'])
            ->getMock();

        $modelManager->addIdentifiersToQuery(Product::class, $proxyQuery, $ids);

        $this->assertCount(\count($expectedParameters), $proxyQuery->getParameters());

        foreach ($proxyQuery->getParameters() as $offset => $parameter) {
            $this->assertSame($expectedParameters[$offset], $parameter->getValue());
        }
    }

    public function addIdentifiersToQueryProvider(): iterable
    {
        yield [['1', '2'], ['id'], [1, 2]];
        yield [['112', '2020'], ['id'], ['112', '2020']];
        yield [['1', '42', '2', '256'], ['id', 'foreignId'], ['1~42', '2~256']];
        yield [['a', '4', 'b', '52'], ['id', 'foreignId'], ['a~4', 'b~52']];
        yield [['048b78d8-eced-47bb-8dff-31d7d32352a0', '1986'], ['mixed'], ['048b78d8-eced-47bb-8dff-31d7d32352a0', '1986']];
        yield [
            [
                '048b78d8-eced-47bb-8dff-31d7d32352a0',
                '3d6e98f5-8e43-4a81-b39b-3303c0aa5841',
            ], [
                'guid',
            ], [
                '048b78d8-eced-47bb-8dff-31d7d32352a0',
                '3d6e98f5-8e43-4a81-b39b-3303c0aa5841',
            ],
        ];
        yield [
            [
                '048b78d8-eced-47bb-8dff-31d7d32352a0',
                '3d6e98f5-8e43-4a81-b39b-3303c0aa5841',
                'dfc1c309-8628-4e1a-8ce3-d3727dedaac6',
                'f31b0cb3-a7a5-4297-ba3d-d810b286b002',
            ], [
                'guid',
                'foreingGuid',
            ], [
                '048b78d8-eced-47bb-8dff-31d7d32352a0~3d6e98f5-8e43-4a81-b39b-3303c0aa5841',
                'dfc1c309-8628-4e1a-8ce3-d3727dedaac6~f31b0cb3-a7a5-4297-ba3d-d810b286b002',
            ],
        ];
    }

    public function testAddIdentifiersToQueryWithEmptyIdentifiers(): void
    {
        $datagrid = $this->createStub(ProxyQueryInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Array passed as argument 3 to "Sonata\DoctrineORMAdminBundle\Model\ModelManager::addIdentifiersToQuery()" must not be empty.');

        $this->modelManager->addIdentifiersToQuery(\stdClass::class, $datagrid, []);
    }

    private function getMetadata(string $class, bool $isVersioned = false): ClassMetadata
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
