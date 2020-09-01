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

use Doctrine\Common\Collections\Collection;
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

    public function getMetadata(string $class): ClassMetadata
    {
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
     * @return array(
     *                \Doctrine\ORM\Mapping\ClassMetadata $parentMetadata,
     *                string $lastPropertyName,
     *                array $parentAssociationMappings
     *                )
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

        return [$this->getMetadata($class), implode('.', $properties), $parentAssociationMappings];
    }

    /**
     * @param string $class
     *
     * @return bool
     */
    public function hasMetadata($class)
    {
        return $this->getEntityManager($class)->getMetadataFactory()->hasMetadataFor($class);
    }

    public function getNewFieldDescriptionInstance(string $class, string $name, array $options = []): FieldDescriptionInterface
    {
        if (!\is_string($name)) {
            throw new \RuntimeException('The name argument must be a string');
        }

        if (!isset($options['route']['name'])) {
            $options['route']['name'] = 'edit';
        }

        if (!isset($options['route']['parameters'])) {
            $options['route']['parameters'] = [];
        }

        [$metadata, $propertyName, $parentAssociationMappings] = $this->getParentMetadataForProperty($class, $name);

        $fieldDescription = new FieldDescription();
        $fieldDescription->setName($name);
        $fieldDescription->setOptions($options);
        $fieldDescription->setParentAssociationMappings($parentAssociationMappings);

        if (isset($metadata->associationMappings[$propertyName])) {
            $fieldDescription->setAssociationMapping($metadata->associationMappings[$propertyName]);
        }

        if (isset($metadata->fieldMappings[$propertyName])) {
            $fieldDescription->setFieldMapping($metadata->fieldMappings[$propertyName]);
        }

        return $fieldDescription;
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

    public function executeQuery(object $query)
    {
        if ($query instanceof QueryBuilder) {
            return $query->getQuery()->execute();
        }

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

    public function getNormalizedIdentifier(object $entity): string
    {
        if (\in_array($this->getEntityManager($entity)->getUnitOfWork()->getEntityState($entity), [
            UnitOfWork::STATE_NEW,
            UnitOfWork::STATE_REMOVED,
        ], true)) {
            throw new \InvalidArgumentException(sprintf(
                'Can not get the normalized identifier for %s since it is in state %u.',
                ClassUtils::getClass($entity),
                $this->getEntityManager($entity)->getUnitOfWork()->getEntityState($entity)
            ));
        }

        $values = $this->getIdentifierValues($entity);

        return implode(self::ID_SEPARATOR, $values);
    }

    /**
     * {@inheritdoc}
     *
     * The ORM implementation does nothing special but you still should use
     * this method when using the id in a URL to allow for future improvements.
     */
    public function getUrlSafeIdentifier(object $entity): string
    {
        return $this->getNormalizedIdentifier($entity);
    }

    public function addIdentifiersToQuery(string $class, ProxyQueryInterface $queryProxy, array $idx): void
    {
        $fieldNames = $this->getIdentifierFieldNames($class);
        $qb = $queryProxy->getQueryBuilder();

        $prefix = uniqid();
        $sqls = [];
        foreach ($idx as $pos => $id) {
            $ids = explode(self::ID_SEPARATOR, $id);

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

    public function batchDelete(string $class, ProxyQueryInterface $queryProxy): void
    {
        $queryProxy->select('DISTINCT '.current($queryProxy->getRootAliases()));

        try {
            $entityManager = $this->getEntityManager($class);

            $i = 0;
            foreach ($queryProxy->getQuery()->iterate() as $pos => $object) {
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

    public function getDataSourceIterator(
        DatagridInterface $datagrid,
        array $fields,
        ?int $firstResult = null,
        ?int $maxResult = null
    ): SourceIteratorInterface {
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

    public function getSortParameters(FieldDescriptionInterface $fieldDescription, DatagridInterface $datagrid)
    {
        $values = $datagrid->getValues();

        if ($this->isFieldAlreadySorted($fieldDescription, $datagrid)) {
            if ('ASC' === $values['_sort_order']) {
                $values['_sort_order'] = 'DESC';
            } else {
                $values['_sort_order'] = 'ASC';
            }
        } else {
            $values['_sort_order'] = 'ASC';
        }

        $values['_sort_by'] = \is_string($fieldDescription->getOption('sortable')) ? $fieldDescription->getOption('sortable') : $fieldDescription->getName();

        return ['filter' => $values];
    }

    public function getPaginationParameters(DatagridInterface $datagrid, $page)
    {
        $values = $datagrid->getValues();

        if (isset($values['_sort_by']) && $values['_sort_by'] instanceof FieldDescriptionInterface) {
            $values['_sort_by'] = $values['_sort_by']->getName();
        }
        $values['_page'] = $page;

        return ['filter' => $values];
    }

    public function getDefaultSortValues(string $class): array
    {
        return [
            '_page' => 1,
            '_per_page' => 25,
        ];
    }

    public function getDefaultPerPageOptions(string $class): array
    {
        return [10, 25, 50, 100, 250];
    }

    public function modelTransform(string $class, object $instance): object
    {
        return $instance;
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

    public function getModelCollectionInstance(string $class): Collection
    {
        return new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function collectionClear(Collection $collection): void
    {
        $collection->clear();
    }

    public function collectionHasElement(Collection $collection, object $element): bool
    {
        return $collection->contains($element);
    }

    public function collectionAddElement(Collection $collection, object $element): void
    {
        $collection->add($element);
    }

    public function collectionRemoveElement(Collection $collection, object $element): void
    {
        $collection->removeElement($element);
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
