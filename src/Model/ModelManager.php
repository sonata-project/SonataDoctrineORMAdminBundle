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
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface as BaseProxyQueryInterface;
use Sonata\AdminBundle\Exception\LockException;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Sonata\AdminBundle\Model\LockInterface;
use Sonata\AdminBundle\Model\ModelManagerInterface;
use Sonata\DoctrineORMAdminBundle\Admin\FieldDescription;
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

    /**
     * Returns the model's metadata holding the fully qualified property, and the last
     * property name.
     *
     * @param string $baseClass        The base class of the model holding the fully qualified property
     * @param string $propertyFullName The name of the fully qualified property (dot ('.') separated
     *                                 property string)
     *
     * @phpstan-param class-string $baseClass
     * @phpstan-return array{\Doctrine\ORM\Mapping\ClassMetadataInfo, string, array}
     */
    public function getParentMetadataForProperty(string $baseClass, string $propertyFullName): array
    {
        $nameElements = explode('.', $propertyFullName);
        $lastPropertyName = array_pop($nameElements);
        $class = $baseClass;
        $parentAssociationMappings = [];

        foreach ($nameElements as $nameElement) {
            $metadata = $this->getMetadata($class);

            if (isset($metadata->associationMappings[$nameElement])) {
                $parentAssociationMappings[] = $metadata->associationMappings[$nameElement];
                $class = $metadata->getAssociationTargetClass($nameElement);

                continue;
            }

            break;
        }

        $properties = \array_slice($nameElements, \count($parentAssociationMappings));
        $properties[] = $lastPropertyName;

        return [
            $this->getMetadata($class),
            implode('.', $properties),
            $parentAssociationMappings,
        ];
    }

    public function getNewFieldDescriptionInstance(string $class, string $name, array $options = []): FieldDescriptionInterface
    {
        if (!isset($options['route']['name'])) {
            $options['route']['name'] = 'show';
        }

        if (!isset($options['route']['parameters'])) {
            $options['route']['parameters'] = [];
        }

        [$metadata, $propertyName, $parentAssociationMappings] = $this->getParentMetadataForProperty($class, $name);

        return new FieldDescription(
            $name,
            $options,
            $metadata->fieldMappings[$propertyName] ?? [],
            $metadata->associationMappings[$propertyName] ?? [],
            $parentAssociationMappings,
            $propertyName
        );
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

        if (!$metadata->isVersioned) {
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
        if (null === $id) {
            @trigger_error(sprintf(
                'Passing null as argument 1 for %s() is deprecated since sonata-project/doctrine-orm-admin-bundle 3.20 and will be not allowed in version 4.0.',
                __METHOD__
            ), \E_USER_DEPRECATED);

            return null;
        }

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

            if (!$em) {
                throw new \RuntimeException(sprintf('No entity manager defined for class %s', $class));
            }

            $this->cache[$class] = $em;
        }

        return $this->cache[$class];
    }

    public function createQuery(string $class, $alias = 'o'): BaseProxyQueryInterface
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
            $type = $fieldType && Type::hasType($fieldType) ? Type::getType($fieldType) : null;
            if ($type) {
                $identifiers[] = $this->getValueFromType($value, $type, $fieldType, $platform);

                continue;
            }

            $identifierMetadata = $this->getMetadata(ClassUtils::getClass($value));

            foreach ($identifierMetadata->getIdentifierValues($value) as $value) {
                $identifiers[] = $value;
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
     * {@inheritdoc}
     *
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
        /* NEXT_MAJOR: Remove this deprecation and update the typehint */
        if (!$query instanceof ProxyQueryInterface) {
            @trigger_error(sprintf(
                'Passing %s as argument 2 to "%s()" is deprecated since sonata-project/doctrine-orm-admin-bundle 3.x'
                .' and will throw a \TypeError error in version 4.0. You MUST pass an instance of %s instead.',
                \get_class($query),
                __METHOD__,
                ProxyQueryInterface::class
            ), \E_USER_DEPRECATED);
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
        /* NEXT_MAJOR: Remove this deprecation and update the typehint */
        if (!$query instanceof ProxyQueryInterface) {
            @trigger_error(sprintf(
                'Passing %s as argument 2 to "%s()" is deprecated since sonata-project/doctrine-orm-admin-bundle 3.x'
                .' and will throw a \TypeError error in version 4.0. You MUST pass an instance of %s instead.',
                \get_class($query),
                __METHOD__,
                ProxyQueryInterface::class
            ), \E_USER_DEPRECATED);
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

    public function getModelInstance(string $class): object
    {
        $r = new \ReflectionClass($class);
        if ($r->isAbstract()) {
            throw new \RuntimeException(sprintf('Cannot initialize abstract class: %s', $class));
        }

        $constructor = $r->getConstructor();

        if (null !== $constructor && (!$constructor->isPublic() || $constructor->getNumberOfRequiredParameters() > 0)) {
            return $r->newInstanceWithoutConstructor();
        }

        return new $class();
    }

    public function modelReverseTransform(string $class, array $array = []): object
    {
        $instance = $this->getModelInstance($class);
        $metadata = $this->getMetadata($class);

        foreach ($array as $name => $value) {
            $property = $this->getFieldName($metadata, $name);
            $this->propertyAccessor->setValue($instance, $property, $value);
        }

        return $instance;
    }

    /**
     * @phpstan-param class-string $class
     */
    private function getMetadata(string $class): ClassMetadataInfo
    {
        return $this->getEntityManager($class)->getClassMetadata($class);
    }

    private function getFieldName(ClassMetadataInfo $metadata, string $name): string
    {
        if (\array_key_exists($name, $metadata->fieldMappings)) {
            return $metadata->fieldMappings[$name]['fieldName'];
        }

        if (\array_key_exists($name, $metadata->associationMappings)) {
            return $metadata->associationMappings[$name]['fieldName'];
        }

        return $name;
    }

    private function isFieldAlreadySorted(FieldDescriptionInterface $fieldDescription, DatagridInterface $datagrid): bool
    {
        $values = $datagrid->getValues();

        if (!isset($values['_sort_by']) || !$values['_sort_by'] instanceof FieldDescriptionInterface) {
            return false;
        }

        return $values['_sort_by']->getName() === $fieldDescription->getName()
            || $values['_sort_by']->getName() === $fieldDescription->getOption('sortable');
    }

    /**
     * @param mixed $value
     */
    private function getValueFromType($value, Type $type, string $fieldType, AbstractPlatform $platform): string
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
