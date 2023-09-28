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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\Hydration\SimpleObjectHydrator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub\Exception as ExceptionStub;
use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Exception\LockException;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Model\ModelManager;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType\ProductIdType;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType\UuidBinaryType;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType\UuidType;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType\ValueObjectWithMagicToStringImpl;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType\ValueObjectWithToStringImpl;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\AssociatedEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\ContainerEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\Embeddable\EmbeddedEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\ORM\User;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\Product;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\ProductId;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\SimpleEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\UuidBinaryEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\UuidEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\VersionedEntity;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Util\NonIntegerIdentifierTestClass;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class ModelManagerTest extends TestCase
{
    /**
     * @var ManagerRegistry&MockObject
     */
    private ManagerRegistry $registry;

    /**
     * @var ModelManager<object>
     */
    private ModelManager $modelManager;

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

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->modelManager = new ModelManager($this->registry, PropertyAccess::createPropertyAccessor());
    }

    public function testGetRealClassWithProxyObject(): void
    {
        $proxyClass = User::class;
        $baseClass = \stdClass::class;

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(static::once())
            ->method('getName')
            ->willReturn($baseClass);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(static::once())
            ->method('getClassMetadata')
            ->with($proxyClass)
            ->willReturn($classMetadata);

        $this->registry->expects(static::once())
            ->method('getManagerForClass')
            ->with($proxyClass)
            ->willReturn($entityManager);

        static::assertSame($baseClass, $this->modelManager->getRealClass(new User()));
    }

    public function testGetRealClassWithNonProxyObject(): void
    {
        $this->registry->expects(static::once())
            ->method('getManagerForClass')
            ->with(\DateTime::class)
            ->willReturn(null);

        static::assertSame(\DateTime::class, $this->modelManager->getRealClass(new \DateTime()));
    }

    /**
     * @phpstan-return iterable<array-key, array{class-string}>
     */
    public function provideGetIdentifierValuesWhenIdentifierIsValueObjectWithToStringMethodCases(): iterable
    {
        yield 'value object with toString implementation' => [ValueObjectWithToStringImpl::class];
        yield 'value object with magic toString implementation' => [ValueObjectWithMagicToStringImpl::class];
    }

    /**
     * @param class-string $vbClassName
     *
     * @dataProvider provideGetIdentifierValuesWhenIdentifierIsValueObjectWithToStringMethodCases
     */
    public function testGetIdentifierValuesWhenIdentifierIsValueObjectWithToStringMethod(string $vbClassName): void
    {
        $entity = new UuidBinaryEntity(new $vbClassName('a7ef873a-e7b5-11e9-81b4-2a2ae2dbcce4'));

        $platform = $this->createMock(AbstractPlatform::class);

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->method('getIdentifierValues')->willReturn([$entity->getId()]);
        $classMetadata->method('getTypeOfField')->willReturn(UuidBinaryType::NAME);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getClassMetadata')->willReturn($classMetadata);
        $entityManager->method('getConnection')->willReturn($connection);

        $this->registry->method('getManagerForClass')->willReturn($entityManager);

        static::assertSame(
            ['a7ef873a-e7b5-11e9-81b4-2a2ae2dbcce4'],
            $this->modelManager->getIdentifierValues($entity)
        );
    }

    public function testInstantiateWithDeprecatedRegistryInterface(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $this->registry->expects(static::once())
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn($em);
        static::assertSame($em, $this->modelManager->getEntityManager(\stdClass::class));
    }

    /**
     * @dataProvider provideSupportsQueryCases
     */
    public function testSupportsQuery(bool $expected, object $object): void
    {
        static::assertSame($expected, $this->modelManager->supportsQuery($object));
    }

    /**
     * @phpstan-return iterable<array-key, array{bool, object}>
     */
    public function provideSupportsQueryCases(): iterable
    {
        yield [true, new ProxyQuery($this->createMock(QueryBuilder::class))];
        yield [true, $this->createMock(AbstractQuery::class)];
        yield [true, $this->createMock(QueryBuilder::class)];
        yield [false, new \stdClass()];
    }

    /**
     * @phpstan-return iterable<array-key, array{bool}>
     */
    public function provideGetVersionCases(): iterable
    {
        yield [true];
        yield [false];
    }

    /**
     * @dataProvider provideGetVersionCases
     */
    public function testGetVersion(bool $isVersioned): void
    {
        $object = new VersionedEntity();

        $this->setGetMetadataExpectation($object::class, $this->getMetadata($object::class, $isVersioned));

        if ($isVersioned) {
            $object->version = 123;

            static::assertNotNull($this->modelManager->getLockVersion($object));
        } else {
            static::assertNull($this->modelManager->getLockVersion($object));
        }
    }

    /**
     * @phpstan-return iterable<array-key, array{bool, bool}>
     */
    public function provideLockCases(): iterable
    {
        yield [true,  false];
        yield [true,  true];
        yield [false, false];
    }

    /**
     * @dataProvider provideLockCases
     */
    public function testLock(bool $isVersioned, bool $expectsException): void
    {
        $object = new VersionedEntity();

        $metadata = $this->getMetadata($object::class, $isVersioned);

        $em = $this->setGetMetadataExpectation($object::class, $metadata);

        $em->expects($isVersioned ? static::once() : static::never())
            ->method('lock');

        if ($expectsException) {
            $em->expects(static::once())
                ->method('lock')
                ->will(static::throwException(OptimisticLockException::lockFailed($object)));

            $this->expectException(LockException::class);
        }

        $this->modelManager->lock($object, 123);
    }

    public function testGetIdentifierValuesForIdInObjectTypeBinaryToStringSupport(): void
    {
        $uuid = new NonIntegerIdentifierTestClass('efbcfc4b-8c43-4d42-aa4c-d707e55151ac');

        $entity = new UuidEntity($uuid);

        $meta = $this->createMock(ClassMetadata::class);
        $meta->expects(static::any())
            ->method('getIdentifierValues')
            ->willReturn([$entity->getId()]);
        $meta->expects(static::any())
            ->method('getTypeOfField')
            ->willReturn(UuidBinaryType::NAME);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->expects(static::any())
            ->method('hasDoctrineTypeMappingFor')
            ->with(UuidBinaryType::NAME)
            ->willReturn(true);
        $platform->expects(static::any())
            ->method('getDoctrineTypeMapping')
            ->with(UuidBinaryType::NAME)
            ->willReturn('binary');

        $conn = $this->createMock(Connection::class);
        $conn->expects(static::any())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(static::any())
            ->method('getClassMetadata')
            ->willReturn($meta);
        $em->expects(static::any())
            ->method('getConnection')
            ->willReturn($conn);

        $this->registry->expects(static::any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $result = $this->modelManager->getIdentifierValues($entity);

        static::assertSame($entity->getId()->toString(), $result[0]);
    }

    public function testNonIntegerIdentifierType(): void
    {
        $uuid = new NonIntegerIdentifierTestClass('efbcfc4b-8c43-4d42-aa4c-d707e55151ac');
        $entity = new UuidEntity($uuid);

        $meta = $this->createMock(ClassMetadata::class);
        $meta->expects(static::any())
            ->method('getIdentifierValues')
            ->willReturn([$entity->getId()]);
        $meta->expects(static::any())
            ->method('getTypeOfField')
            ->willReturn(UuidType::NAME);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->expects(static::any())
            ->method('hasDoctrineTypeMappingFor')
            ->with(UuidType::NAME)
            ->willReturn(false);
        $platform->expects(static::never())
            ->method('getDoctrineTypeMapping');

        $conn = $this->createMock(Connection::class);
        $conn->expects(static::any())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(static::any())
            ->method('getClassMetadata')
            ->willReturn($meta);
        $em->expects(static::any())
            ->method('getConnection')
            ->willReturn($conn);

        $this->registry->expects(static::any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $result = $this->modelManager->getIdentifierValues($entity);

        static::assertSame($entity->getId()->toString(), $result[0]);
    }

    public function testIntegerIdentifierType(): void
    {
        $id = new ProductId(12345);
        $entity = new Product($id, 'Some product');

        $meta = $this->createMock(ClassMetadata::class);
        $meta->expects(static::any())
            ->method('getIdentifierValues')
            ->willReturn([$entity->getId()]);
        $meta->expects(static::any())
            ->method('getTypeOfField')
            ->willReturn(ProductIdType::NAME);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->expects(static::any())
            ->method('hasDoctrineTypeMappingFor')
            ->with(ProductIdType::NAME)
            ->willReturn(false);
        $platform->expects(static::never())
            ->method('getDoctrineTypeMapping');

        $conn = $this->createMock(Connection::class);
        $conn->expects(static::any())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(static::any())
            ->method('getClassMetadata')
            ->willReturn($meta);
        $em->expects(static::any())
            ->method('getConnection')
            ->willReturn($conn);

        $this->registry->expects(static::any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $result = $this->modelManager->getIdentifierValues($entity);

        static::assertSame((string) $entity->getId()->getId(), $result[0]);
    }

    public function testAssociationIdentifierType(): void
    {
        $entity = new ContainerEntity(new AssociatedEntity(new EmbeddedEntity(), 42), new EmbeddedEntity());

        $meta = $this->createMock(ClassMetadata::class);
        $meta->expects(static::any())
            ->method('getIdentifierValues')
            ->willReturn([$entity->getAssociatedEntity()->getPlainField()]);
        $meta->expects(static::any())
            ->method('getTypeOfField')
            ->willReturn(null);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->expects(static::never())
            ->method('hasDoctrineTypeMappingFor');

        $conn = $this->createMock(Connection::class);
        $conn->expects(static::any())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(static::any())
            ->method('getClassMetadata')
            ->willReturn($meta);
        $em->expects(static::any())
            ->method('getConnection')
            ->willReturn($conn);

        $this->registry->expects(static::any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $result = $this->modelManager->getIdentifierValues($entity);

        static::assertSame(42, $result[0]);
    }

    public function testReverseTransform(): void
    {
        $object = new SimpleEntity();
        $class = SimpleEntity::class;

        $objectManager = $this->createMock(EntityManagerInterface::class);

        $classMetadata = new ClassMetadata($class);
        $classMetadata->reflClass = new \ReflectionClass($class);

        $objectManager->expects(static::once())
            ->method('getClassMetadata')
            ->with($class)
            ->willReturn($classMetadata);
        $this->registry->expects(static::once())
            ->method('getManagerForClass')
            ->with($class)
            ->willReturn($objectManager);

        $this->modelManager->reverseTransform($object, [
            'schmeckles' => 42,
            'multi_word_property' => 'hello',
        ]);
        static::assertSame(42, $object->getSchmeckles());
        static::assertSame('hello', $object->getMultiWordProperty());
    }

    public function testGetEntityManagerException(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->modelManager->getEntityManager(VersionedEntity::class);
    }

    /**
     * @dataProvider createUpdateRemoveData
     */
    public function testCreate(\Throwable $exception): void
    {
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->method('getName')->willReturn(VersionedEntity::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $this->registry->expects(static::atLeastOnce())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $entityManager->expects(static::once())
            ->method('persist');

        $entityManager->expects(static::once())
            ->method('flush')
            ->willThrowException($exception);

        $entityManager->expects(static::once())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $this->expectException(ModelManagerException::class);

        $this->modelManager->create(new VersionedEntity());
    }

    /**
     * @phpstan-return iterable<array-key, array{\Throwable}>
     */
    public function createUpdateRemoveData(): iterable
    {
        yield \PDOException::class => [
            new \PDOException(),
        ];
        yield 'DBALException' => [
            new Exception(),
        ];
    }

    /**
     * @dataProvider createUpdateRemoveData
     */
    public function testUpdate(\Throwable $exception): void
    {
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->method('getName')->willReturn(VersionedEntity::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $this->registry->expects(static::atLeastOnce())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $entityManager->expects(static::once())
            ->method('persist');

        $entityManager->expects(static::once())
            ->method('flush')
            ->willThrowException($exception);

        $entityManager->expects(static::once())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $this->expectException(ModelManagerException::class);

        $this->modelManager->update(new VersionedEntity());
    }

    /**
     * @dataProvider createUpdateRemoveData
     */
    public function testRemove(\Throwable $exception): void
    {
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->method('getName')->willReturn(VersionedEntity::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $this->registry->expects(static::atLeastOnce())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $entityManager->expects(static::once())
            ->method('remove');

        $entityManager->expects(static::once())
            ->method('flush')
            ->willThrowException($exception);

        $entityManager->expects(static::once())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $this->expectException(ModelManagerException::class);

        $this->modelManager->delete(new VersionedEntity());
    }

    /**
     * @return iterable<int|string, array<int, string|array<int, object|null>|null>>
     *
     * @phpstan-return iterable<int|string, array{0: string, 1: ?array<int, object>, 2: array<int, ?ExceptionStub>}>
     */
    public function provideFailingBatchDeleteCases(): iterable
    {
        yield [
            'Failed to delete object "Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\VersionedEntity" (id: 42) while'
            .' performing batch deletion (20 objects were successfully deleted before this error)',
            array_fill(0, 21, new VersionedEntity()),
            [null, static::throwException(new Exception())],
        ];

        yield [
            'Failed to delete object "Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\VersionedEntity" (id: 42) while'
            .' performing batch deletion',
            [new VersionedEntity(), new VersionedEntity()],
            [static::throwException(new Exception())],
        ];

        yield [
            'Failed to perform batch deletion for "Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\VersionedEntity" objects',
            null,
            [null],
        ];
    }

    /**
     * @param array<int, object>|null        $result
     * @param array<int, ExceptionStub|null> $onConsecutiveFlush
     *
     * @dataProvider provideFailingBatchDeleteCases
     */
    public function testFailingBatchDelete(string $expectedExceptionMessage, ?array $result, array $onConsecutiveFlush): void
    {
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata
            ->method('getIdentifierValues')
            ->willReturn([
                'id' => 42,
            ]);
        $classMetadata->expects(static::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $classMetadata->table['name'] = 'versioned_entity';

        $batchSize = 20;

        $em = $this->setGetMetadataExpectation(VersionedEntity::class, $classMetadata);
        $em
            ->expects(static::exactly(null === $result ? 0 : \count($result)))
            ->method('remove');
        $em
            ->expects(static::exactly(null === $result ? 0 : (int) ceil(\count($result) / $batchSize)))
            ->method('flush')
            ->will(static::onConsecutiveCalls(
                ...$onConsecutiveFlush
            ));
        $em
            ->method('getConfiguration')
            ->willReturn(new Configuration());

        $uow = $this->createMock(UnitOfWork::class);
        $uow
            ->expects(static::exactly(null === $result ? 0 : 1))
            ->method('getEntityState')
            ->willReturn(UnitOfWork::STATE_MANAGED);

        $em
            ->expects(static::exactly(null === $result ? 0 : 1))
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('getDatabasePlatform')
            ->willReturn($this->createStub(AbstractPlatform::class));
        $connection
            ->method('getParams')
            ->willReturn([]);

        $em
            ->method('getConnection')
            ->willReturn($connection);

        $hydrator = $this->createMock(SimpleObjectHydrator::class);
        $hydrator
            ->expects(static::once())
            ->method('toIterable')
            ->willReturnCallback(static function () use ($result): iterable {
                if (null === $result) {
                    throw new Exception();
                }

                return $result;
            });

        $em
            ->expects(static::once())
            ->method('newHydrator')
            ->willReturn($hydrator);

        $queryBuilder = new QueryBuilder($em);
        $queryBuilder
            ->select('ve')
            ->from(VersionedEntity::class, 've');

        $query = new Query($em);
        $query->setDQL($queryBuilder->getDQL());

        $em
            ->expects(static::once())
            ->method('createQuery')
            ->willReturn($query);

        $cmf = $this->createMock(ClassMetadataFactory::class);
        $cmf
            ->expects(static::once())
            ->method('getMetadataFor')
            ->with(VersionedEntity::class)
            ->willReturn($classMetadata);

        $em
            ->expects(static::once())
            ->method('getMetadataFactory')
            ->willReturn($cmf);

        $this->expectException(ModelManagerException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->modelManager->batchDelete(VersionedEntity::class, new ProxyQuery($queryBuilder), $batchSize);
    }

    /**
     * @param string[]          $expectedParameters
     * @param string[]          $identifierFieldNames
     * @param array<int|string> $ids
     *
     * @dataProvider provideAddIdentifiersToQueryCases
     *
     * @phpstan-param non-empty-array<int|string> $ids
     */
    public function testAddIdentifiersToQuery(array $expectedParameters, array $identifierFieldNames, array $ids): void
    {
        $em = $this->createMock(EntityManagerInterface::class);

        /** @var QueryBuilder&MockObject $queryBuilder */
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->setConstructorArgs([$em])
            ->onlyMethods(['getRootAliases', 'andWhere'])
            ->getMock();

        $queryBuilder
            ->expects(static::once())
            ->method('getRootAliases')
            ->willReturn(['p']);

        $queryBuilder
            ->expects(static::once())
            ->method('andWhere')
            ->with(static::stringContains(sprintf('( p.%s = :field_', $identifierFieldNames[0])));

        $proxyQuery = new ProxyQuery($queryBuilder);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects(static::once())
            ->method('getIdentifierFieldNames')
            ->willReturn($identifierFieldNames);
        $this->setGetMetadataExpectation(Product::class, $metadata);

        $this->modelManager->addIdentifiersToQuery(Product::class, $proxyQuery, $ids);

        static::assertCount(\count($expectedParameters), $proxyQuery->getQueryBuilder()->getParameters());

        foreach ($proxyQuery->getParameters() as $offset => $parameter) {
            static::assertSame($expectedParameters[$offset], $parameter->getValue());
        }
    }

    /**
     * @phpstan-return iterable<array-key, array{string[], string[], non-empty-array<int|string>}>
     */
    public function provideAddIdentifiersToQueryCases(): iterable
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

    /**
     * @psalm-suppress InvalidArgument
     */
    public function testAddIdentifiersToQueryWithEmptyIdentifiers(): void
    {
        $datagrid = $this->createStub(ProxyQueryInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Array passed as argument 3 to "Sonata\DoctrineORMAdminBundle\Model\ModelManager::addIdentifiersToQuery()" must not be empty.');

        // @phpstan-ignore-next-line
        $this->modelManager->addIdentifiersToQuery(\stdClass::class, $datagrid, []);
    }

    /**
     * @phpstan-template T of object
     * @phpstan-param class-string<T> $class
     * @phpstan-return ClassMetadata<T>
     */
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

    /**
     * @return EntityManagerInterface&MockObject
     *
     * @phpstan-template T of object
     * @phpstan-param class-string<T> $class
     * @phpstan-param ClassMetadata<T> $classMetadata
     */
    private function setGetMetadataExpectation(string $class, ClassMetadata $classMetadata): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->registry->expects(static::atLeastOnce())
            ->method('getManagerForClass')
            ->with($class)
            ->willReturn($em);

        $em->expects(static::atLeastOnce())
            ->method('getClassMetadata')
            ->with($class)
            ->willReturn($classMetadata);

        return $em;
    }
}
