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

namespace Sonata\DoctrineORMAdminBundle\Model;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface as BaseProxyQueryInterface;
use Sonata\AdminBundle\Exception\LockException;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Sonata\AdminBundle\Model\LockInterface;
use Sonata\AdminBundle\Model\ModelManagerInterface;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final class ModelManager implements ModelManagerInterface, LockInterface
{
    public const ID_SEPARATOR = '~';

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * @var EntityManagerInterface[]
     */
    private $cache = [];

    public function __construct(ManagerRegistry $registry, PropertyAccessorInterface $propertyAccessor)
    {
        $this->registry = $registry;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function create(object $object): void
    {
        try {
            $entityManager = $this->getEntityManager($object);
            $entityManager->persist($object);
            $entityManager->flush();
        } catch (\PDOException $e) {
            throw new ModelManagerException(
                sprintf('Failed to create object: %s', ClassUtils::getClass($object)),
                $e->getCode(),
                $e
            );
        } catch (DBALException $e) {
            throw new ModelManagerException(
                sprintf('Failed to create object: %s', ClassUtils::getClass($object)),
                $e->getCode(),
                $e
            );
        }
    }

    public function update(object $object): void
    {
        try {
            $entityManager = $this->getEntityManager($object);
            $entityManager->persist($object);
            $entityManager->flush();
        } catch (\PDOException $e) {
            throw new ModelManagerException(
                sprintf('Failed to update object: %s', ClassUtils::getClass($object)),
                $e->getCode(),
                $e
            );
        } catch (DBALException $e) {
            throw new ModelManagerException(
                sprintf('Failed to update object: %s', ClassUtils::getClass($object)),
                $e->getCode(),
                $e
            );
        }
    }

    public function delete(object $object): void
    {
        try {
            $entityManager = $this->getEntityManager($object);
            $entityManager->remove($object);
            $entityManager->flush();
        } catch (\PDOException $e) {
            throw new ModelManagerException(
                sprintf('Failed to delete object: %s', ClassUtils::getClass($object)),
                $e->getCode(),
                $e
            );
        } catch (DBALException $e) {
            throw new ModelManagerException(
                sprintf('Failed to delete object: %s', ClassUtils::getClass($object)),
                $e->getCode(),
                $e
            );
        }
    }

    public function getLockVersion(object $object)
    {
        $metadata = $this->getMetadata(ClassUtils::getClass($object));

        if (!$metadata->isVersioned || !isset($metadata->reflFields[$metadata->versionField])) {
            return null;
        }

        return $metadata->reflFields[$metadata->versionField]->getValue($object);
    }

    public function lock(object $object, ?int $expectedVersion): void
    {
        $metadata = $this->getMetadata(ClassUtils::getClass($object));

        if (!$metadata->isVersioned) {
            return;
        }

        try {
            $entityManager = $this->getEntityManager($object);
            $entityManager->lock($object, LockMode::OPTIMISTIC, $expectedVersion);
        } catch (OptimisticLockException $e) {
            throw new LockException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function find(string $class, $id): ?object
    {
        $values = array_combine($this->getIdentifierFieldNames($class), explode(self::ID_SEPARATOR, (string) $id));

        return $this->getEntityManager($class)->getRepository($class)->find($values);
    }

    public function findBy(string $class, array $criteria = []): array
    {
        return $this->getEntityManager($class)->getRepository($class)->findBy($criteria);
    }

    public function findOneBy(string $class, array $criteria = []): ?object
    {
        return $this->getEntityManager($class)->getRepository($class)->findOneBy($criteria);
    }

    /**
     * @param string|object $class
     *
     * @phpstan-param class-string|object $class
     */
    public function getEntityManager($class): EntityManagerInterface
    {
        if (\is_object($class)) {
            $class = \get_class($class);
        }

        if (!isset($this->cache[$class])) {
            $em = $this->registry->getManagerForClass($class);

            if (!$em instanceof EntityManagerInterface) {
                throw new \RuntimeException(sprintf('No entity manager defined for class %s', $class));
            }

            $this->cache[$class] = $em;
        }

        return $this->cache[$class];
    }

    public function createQuery(string $class, string $alias = 'o'): BaseProxyQueryInterface
    {
        $repository = $this->getEntityManager($class)->getRepository($class);
        \assert($repository instanceof EntityRepository);

        return new ProxyQuery($repository->createQueryBuilder($alias));
    }

    public function supportsQuery(object $query): bool
    {
        return $query instanceof ProxyQuery || $query instanceof QueryBuilder;
    }

    public function executeQuery(object $query)
    {
        if ($query instanceof QueryBuilder) {
            return $query->getQuery()->execute();
        }

        if ($query instanceof ProxyQuery) {
            return $query->execute();
        }

        throw new \InvalidArgumentException(sprintf(
            'Argument 1 passed to %s() must be an instance of %s or %s',
            __METHOD__,
            QueryBuilder::class,
            ProxyQuery::class,
        ));
    }

    public function getIdentifierValues(object $entity): array
    {
        // Fix code has an impact on performance, so disable it ...
        //$entityManager = $this->getEntityManager($entity);
        //if (!$entityManager->getUnitOfWork()->isInIdentityMap($entity)) {
        //    throw new \RuntimeException('Entities passed to the choice field must be managed');
        //}

        $class = ClassUtils::getClass($entity);
        $metadata = $this->getMetadata($class);
        $platform = $this->getEntityManager($class)->getConnection()->getDatabasePlatform();

        $identifiers = [];

        foreach ($metadata->getIdentifierValues($entity) as $name => $value) {
            if (!\is_object($value)) {
                $identifiers[] = $value;

                continue;
            }

            $fieldType = $metadata->getTypeOfField($name);
            if (null !== $fieldType && Type::hasType($fieldType)) {
                $identifiers[] = $this->getValueFromType($value, Type::getType($fieldType), $fieldType, $platform);

                continue;
            }

            $identifierMetadata = $this->getMetadata(ClassUtils::getClass($value));

            foreach ($identifierMetadata->getIdentifierValues($value) as $identifierValue) {
                $identifiers[] = $identifierValue;
            }
        }

        return $identifiers;
    }

    public function getIdentifierFieldNames(string $class): array
    {
        return $this->getMetadata($class)->getIdentifierFieldNames();
    }

    public function getNormalizedIdentifier(object $entity): ?string
    {
        if (\in_array($this->getEntityManager($entity)->getUnitOfWork()->getEntityState($entity), [
            UnitOfWork::STATE_NEW,
            UnitOfWork::STATE_REMOVED,
        ], true)) {
            return null;
        }

        $values = $this->getIdentifierValues($entity);

        if (0 === \count($values)) {
            return null;
        }

        return implode(self::ID_SEPARATOR, $values);
    }

    /**
     * The ORM implementation does nothing special but you still should use
     * this method when using the id in a URL to allow for future improvements.
     */
    public function getUrlSafeIdentifier(object $entity): ?string
    {
        return $this->getNormalizedIdentifier($entity);
    }

    /**
     * @phpstan-param non-empty-array<string|int> $idx
     *
     * @throws \InvalidArgumentException if value passed as argument 3 is an empty array
     */
    public function addIdentifiersToQuery(string $class, BaseProxyQueryInterface $query, array $idx): void
    {
        if (!$query instanceof ProxyQueryInterface) {
            throw new \TypeError(sprintf('The query MUST implement %s.', ProxyQueryInterface::class));
        }

        if ([] === $idx) {
            throw new \InvalidArgumentException(sprintf(
                'Array passed as argument 3 to "%s()" must not be empty.',
                __METHOD__
            ));
        }

        $fieldNames = $this->getIdentifierFieldNames($class);
        $qb = $query->getQueryBuilder();

        $prefix = uniqid();
        $sqls = [];
        foreach ($idx as $pos => $id) {
            $ids = explode(self::ID_SEPARATOR, (string) $id);

            $ands = [];
            foreach ($fieldNames as $posName => $name) {
                $parameterName = sprintf('field_%s_%s_%d', $prefix, $name, $pos);
                $ands[] = sprintf('%s.%s = :%s', current($qb->getRootAliases()), $name, $parameterName);
                $qb->setParameter($parameterName, $ids[$posName]);
            }

            $sqls[] = implode(' AND ', $ands);
        }

        $qb->andWhere(sprintf('( %s )', implode(' OR ', $sqls)));
    }

    public function batchDelete(string $class, BaseProxyQueryInterface $query): void
    {
        if (!$query instanceof ProxyQueryInterface) {
            throw new \TypeError(sprintf('The query MUST implement %s.', ProxyQueryInterface::class));
        }

        $qb = $query->getQueryBuilder();
        $qb->select('DISTINCT '.current($qb->getRootAliases()));

        try {
            $entityManager = $this->getEntityManager($class);

            $i = 0;
            foreach ($qb->getQuery()->iterate() as $pos => $object) {
                $entityManager->remove($object[0]);

                if (0 === (++$i % 20)) {
                    $entityManager->flush();
                    $entityManager->clear();
                }
            }

            $entityManager->flush();
            $entityManager->clear();
        } catch (\PDOException | DBALException $e) {
            throw new ModelManagerException('', 0, $e);
        }
    }

    public function getExportFields(string $class): array
    {
        return $this->getMetadata($class)->getFieldNames();
    }

    public function reverseTransform(object $object, array $array = []): void
    {
        $metadata = $this->getMetadata(\get_class($object));

        foreach ($array as $name => $value) {
            $property = $this->getFieldName($metadata, $name);
            $this->propertyAccessor->setValue($object, $property, $value);
        }
    }

    /**
     * @phpstan-param class-string $class
     */
    private function getMetadata(string $class): ClassMetadata
    {
        return $this->getEntityManager($class)->getClassMetadata($class);
    }

    private function getFieldName(ClassMetadata $metadata, string $name): string
    {
        if (\array_key_exists($name, $metadata->fieldMappings)) {
            return $metadata->fieldMappings[$name]['fieldName'];
        }

        if (\array_key_exists($name, $metadata->associationMappings)) {
            return $metadata->associationMappings[$name]['fieldName'];
        }

        return $name;
    }

    private function getValueFromType(object $value, Type $type, string $fieldType, AbstractPlatform $platform): string
    {
        if ($platform->hasDoctrineTypeMappingFor($fieldType) &&
            'binary' === $platform->getDoctrineTypeMapping($fieldType)
        ) {
            return (string) $type->convertToPHPValue($value, $platform);
        }

        // some libraries may have `toString()` implementation
        if (\is_callable([$value, 'toString'])) {
            return $value->toString();
        }

        // final fallback to magic `__toString()` which may throw an exception in 7.4
        if (method_exists($value, '__toString')) {
            return $value->__toString();
        }

        return (string) $type->convertToDatabaseValue($value, $platform);
    }
}
