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
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Exception\LockException;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Sonata\AdminBundle\Model\LockInterface;
use Sonata\AdminBundle\Model\ModelManagerInterface;
use Sonata\DoctrineORMAdminBundle\Admin\FieldDescription;
use Sonata\DoctrineORMAdminBundle\Datagrid\OrderByToSelectWalker;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\Exporter\Source\DoctrineORMQuerySourceIterator;
use Sonata\Exporter\Source\SourceIteratorInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @final since sonata-project/doctrine-orm-admin-bundle 3.24
 */
class ModelManager implements ModelManagerInterface, LockInterface
{
    public const ID_SEPARATOR = '~';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var PropertyAccessorInterface
     */
    protected $propertyAccessor;

    /**
     * @var EntityManagerInterface[]
     */
    protected $cache = [];

    /**
     * NEXT_MAJOR: Make $propertyAccessor mandatory.
     */
    public function __construct(ManagerRegistry $registry, ?PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->registry = $registry;

        // NEXT_MAJOR: Remove this block.
        if (null === $propertyAccessor) {
            @trigger_error(sprintf(
                'Constructing "%s" without passing an instance of "%s" as second argument is deprecated since'
                .' sonata-project/doctrine-orm-admin-bundle 3.22 and will be mandatory in 4.0.',
                __CLASS__,
                PropertyAccessorInterface::class
            ), E_USER_DEPRECATED);

            $propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * NEXT_MAJOR: Change visibility to private.
     *
     * @deprecated since sonata-project/doctrine-orm-admin-bundle 3.x and will be private in version 4.0
     *
     * @phpstan-param class-string $class
     */
    public function getMetadata(string $class): ClassMetadata
    {
        // NEXT_MAJOR: Remove this block.
        if ('sonata_deprecation_mute' !== (\func_get_args()[1] ?? null)) {
            @trigger_error(sprintf(
                'The "%s()" method is deprecated since sonata-project/doctrine-orm-admin-bundle 3.x and'
                .' will be private in version 4.0.',
                __METHOD__
            ), E_USER_DEPRECATED);
        }

        return $this->getEntityManager($class)->getMetadataFactory()->getMetadataFor($class);
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
     * @phpstan-return array{\Doctrine\ORM\Mapping\ClassMetadata, string, array}
     */
    public function getParentMetadataForProperty(string $baseClass, string $propertyFullName): array
    {
        $nameElements = explode('.', $propertyFullName);
        $lastPropertyName = array_pop($nameElements);
        $class = $baseClass;
        $parentAssociationMappings = [];

        foreach ($nameElements as $nameElement) {
            // NEXT_MAJOR: Remove `sonata_deprecation_mute`.
            $metadata = $this->getMetadata($class, 'sonata_deprecation_mute');

            if (isset($metadata->associationMappings[$nameElement])) {
                $parentAssociationMappings[] = $metadata->associationMappings[$nameElement];
                $class = $metadata->getAssociationTargetClass($nameElement);

                continue;
            }

            break;
        }

        $properties = \array_slice($nameElements, \count($parentAssociationMappings));
        $properties[] = $lastPropertyName;

        // NEXT_MAJOR: Remove `sonata_deprecation_mute`.
        return [
            $this->getMetadata($class, 'sonata_deprecation_mute'),
            implode('.', $properties),
            $parentAssociationMappings,
        ];
    }

    /**
     * NEXT_MAJOR: Remove this method.
     *
     * @deprecated since sonata-project/doctrine-orm-admin-bundle 3.x and will be removed in version 4.0
     *
     * @param string $class
     *
     * @return bool
     *
     * @phpstan-param class-string $class
     */
    public function hasMetadata($class)
    {
        if ('sonata_deprecation_mute' !== (\func_get_args()[1] ?? null)) {
            @trigger_error(sprintf(
                'The "%s()" method is deprecated since sonata-project/doctrine-orm-admin-bundle 3.x and'
                .' will be removed in version 4.0.',
                __METHOD__
            ), E_USER_DEPRECATED);
        }

        return $this->getEntityManager($class)->getMetadataFactory()->hasMetadataFor($class);
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
            $parentAssociationMappings
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
        // NEXT_MAJOR: Remove `sonata_deprecation_mute`.
        $metadata = $this->getMetadata(ClassUtils::getClass($object), 'sonata_deprecation_mute');

        if (!$metadata->isVersioned) {
            return null;
        }

        return $metadata->reflFields[$metadata->versionField]->getValue($object);
    }

    public function lock(object $object, ?int $expectedVersion): void
    {
        // NEXT_MAJOR: Remove `sonata_deprecation_mute`.
        $metadata = $this->getMetadata(ClassUtils::getClass($object), 'sonata_deprecation_mute');

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
            ), E_USER_DEPRECATED);

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

    public function createQuery(string $class, $alias = 'o'): ProxyQueryInterface
    {
        $repository = $this->getEntityManager($class)->getRepository($class);

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

        // NEXT_MAJOR: Throw an InvalidArgumentException instead.
        @trigger_error(sprintf(
            'Not passing an instance of %s or %s as param 1 of %s() is deprecated since'
            .' sonata-project/doctrine-orm-admin-bundle 3.24 and will throw an exception in 4.0.',
            QueryBuilder::class,
            ProxyQuery::class,
            __METHOD__
        ), E_USER_DEPRECATED);

        return $query->execute();
    }

    public function getIdentifierValues(object $entity): array
    {
        // Fix code has an impact on performance, so disable it ...
        //$entityManager = $this->getEntityManager($entity);
        //if (!$entityManager->getUnitOfWork()->isInIdentityMap($entity)) {
        //    throw new \RuntimeException('Entities passed to the choice field must be managed');
        //}

        $class = ClassUtils::getClass($entity);
        // NEXT_MAJOR: Remove `sonata_deprecation_mute`
        $metadata = $this->getMetadata($class, 'sonata_deprecation_mute');
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

            // NEXT_MAJOR: Remove `sonata_deprecation_mute`
            $identifierMetadata = $this->getMetadata(ClassUtils::getClass($value), 'sonata_deprecation_mute');

            foreach ($identifierMetadata->getIdentifierValues($value) as $value) {
                $identifiers[] = $value;
            }
        }

        return $identifiers;
    }

    public function getIdentifierFieldNames(string $class): array
    {
        // NEXT_MAJOR: Remove `sonata_deprecation_mute`
        return $this->getMetadata($class, 'sonata_deprecation_mute')->getIdentifierFieldNames();
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
    public function addIdentifiersToQuery(string $class, ProxyQueryInterface $query, array $idx): void
    {
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

    public function batchDelete(string $class, ProxyQueryInterface $query): void
    {
        $query->select('DISTINCT '.current($query->getRootAliases()));

        try {
            $entityManager = $this->getEntityManager($class);

            $i = 0;
            foreach ($query->getQuery()->iterate() as $pos => $object) {
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

    /**
     * NEXT_MAJOR: Remove this method.
     *
     * @deprecated since sonata-admin/doctrine-orm-admin-bundle 3.x and will be removed in 4.0.
     *
     * @return DoctrineORMQuerySourceIterator
     */
    public function getDataSourceIterator(
        DatagridInterface $datagrid,
        array $fields,
        ?int $firstResult = null,
        ?int $maxResult = null
    ): SourceIteratorInterface {
        @trigger_error(sprintf(
            'Method %s() is deprecated since sonata-project/doctrine-orm-admin-bundle 3.x and will be removed in 4.0.',
            __METHOD__
        ), E_USER_DEPRECATED);

        $datagrid->buildPager();
        $query = $datagrid->getQuery();

        $query->select('DISTINCT '.current($query->getRootAliases()));
        $query->setFirstResult($firstResult);
        $query->setMaxResults($maxResult);

        if ($query instanceof ProxyQueryInterface) {
            $sortBy = $query->getSortBy();

            if (!empty($sortBy)) {
                $query->addOrderBy($sortBy, $query->getSortOrder());
                $query = $query->getQuery();
                $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [OrderByToSelectWalker::class]);
            } else {
                $query = $query->getQuery();
            }
        }

        return new DoctrineORMQuerySourceIterator($query, $fields);
    }

    public function getExportFields(string $class): array
    {
        $metadata = $this->getEntityManager($class)->getClassMetadata($class);

        return $metadata->getFieldNames();
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

    /**
     * NEXT_MAJOR: Remove this method.
     *
     * @deprecated since sonata-project/doctrine-orm-admin-bundle 3.x and will be removed in version 4.0.
     */
    public function getDefaultSortValues(string $class): array
    {
        @trigger_error(sprintf(
            'Method %s() is deprecated since sonata-project/doctrine-orm-admin-bundle 3.x and will be removed in 4.0.',
            __METHOD__
        ), E_USER_DEPRECATED);

        return [
            '_page' => 1,
            '_per_page' => 25,
        ];
    }

    /**
     * NEXT_MAJOR: Remove this method.
     *
     * @deprecated since sonata-project/doctrine-orm-admin-bundle 3.x and will be removed in version 4.0.
     */
    public function getDefaultPerPageOptions(string $class): array
    {
        @trigger_error(sprintf(
            'Method %s() is deprecated since sonata-project/doctrine-orm-admin-bundle 3.x and will be removed in 4.0.',
            __METHOD__
        ), E_USER_DEPRECATED);

        return [10, 25, 50, 100, 250];
    }

    /**
     * NEXT_MAJOR: Remove this method.
     *
     * @deprecated since sonata-project/doctrine-orm-admin-bundle 3.x and will be removed in version 4.0.
     */
    public function modelTransform(string $class, object $instance): object
    {
        @trigger_error(sprintf(
            'Method %s() is deprecated since sonata-project/doctrine-orm-admin-bundle 3.x and will be removed in version 4.0.',
            __METHOD__
        ), E_USER_DEPRECATED);

        return $instance;
    }

    public function modelReverseTransform(string $class, array $array = []): object
    {
        $instance = $this->getModelInstance($class);
        // NEXT_MAJOR: Remove `sonata_deprecation_mute`
        $metadata = $this->getMetadata($class, 'sonata_deprecation_mute');

        foreach ($array as $name => $value) {
            $property = $this->getFieldName($metadata, $name);
            $this->propertyAccessor->setValue($instance, $property, $value);
        }

        return $instance;
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
