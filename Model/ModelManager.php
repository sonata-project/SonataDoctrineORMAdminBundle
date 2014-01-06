<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Model;

use Sonata\DoctrineORMAdminBundle\Admin\FieldDescription;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;

use Sonata\AdminBundle\Model\ModelManagerInterface;
use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Exception\ModelManagerException;

use Doctrine\ORM\QueryBuilder;
use Doctrine\DBAL\DBALException;

use Symfony\Component\Form\Exception\PropertyAccessDeniedException;
use Symfony\Bridge\Doctrine\RegistryInterface;

use Exporter\Source\DoctrineORMQuerySourceIterator;

class ModelManager implements ModelManagerInterface
{
    protected $registry;

    const ID_SEPARATOR = '~';

    /**
     * @param \Symfony\Bridge\Doctrine\RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($class)
    {
        return $this->getEntityManager($class)->getMetadataFactory()->getMetadataFor($class);
    }

    /**
     * Returns the model's metadata holding the fully qualified property, and the last
     * property name
     *
     * @param string $baseClass        The base class of the model holding the fully qualified property.
     * @param string $propertyFullName The name of the fully qualified property (dot ('.') separated
     *                                 property string)
     *
     * @return array(
     *     \Doctrine\ORM\Mapping\ClassMetadata $parentMetadata,
     *     string $lastPropertyName,
     *     array $parentAssociationMappings
     * )
     */
    public function getParentMetadataForProperty($baseClass, $propertyFullName)
    {
        $nameElements = explode('.', $propertyFullName);
        $lastPropertyName = array_pop($nameElements);
        $class = $baseClass;
        $parentAssociationMappings = array();

        foreach ($nameElements as $nameElement) {
            $metadata = $this->getMetadata($class);
            $parentAssociationMappings[] = $metadata->associationMappings[$nameElement];
            $class = $metadata->getAssociationTargetClass($nameElement);
        }

        return array($this->getMetadata($class), $lastPropertyName, $parentAssociationMappings);
    }

    /**
     * {@inheritdoc}
     */
    public function hasMetadata($class)
    {
        return $this->getEntityManager($class)->getMetadataFactory()->hasMetadataFor($class);
    }

    /**
     * {@inheritdoc}
     */
    public function getNewFieldDescriptionInstance($class, $name, array $options = array())
    {
        if (!is_string($name)) {
            throw new \RunTimeException('The name argument must be a string');
        }

        if (!isset($options['route']['name'])) {
            $options['route']['name'] = 'edit';
        }

        if (!isset($options['route']['parameters'])) {
            $options['route']['parameters'] = array();
        }

        list($metadata, $propertyName, $parentAssociationMappings) = $this->getParentMetadataForProperty($class, $name);

        $fieldDescription = new FieldDescription;
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

    /**
     * {@inheritdoc}
     */
    public function create($object)
    {
        try {
            $entityManager = $this->getEntityManager($object);
            $entityManager->persist($object);
            $entityManager->flush();
        } catch (\PDOException $e) {
            throw new ModelManagerException('', 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function update($object)
    {
        try {
            $entityManager = $this->getEntityManager($object);
            $entityManager->persist($object);
            $entityManager->flush();
        } catch (\PDOException $e) {
            throw new ModelManagerException('', 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($object)
    {
        try {
            $entityManager = $this->getEntityManager($object);
            $entityManager->remove($object);
            $entityManager->flush();
        } catch (\PDOException $e) {
            throw new ModelManagerException('', 0, $e);
        } catch (DBALException $e) {
            throw new ModelManagerException('', 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function find($class, $id)
    {
        if (!isset($id)) {
            return null;
        }

        $values = array_combine($this->getIdentifierFieldNames($class), explode(self::ID_SEPARATOR, $id));

        return $this->getEntityManager($class)->getRepository($class)->find($values);
    }

    /**
     * {@inheritdoc}
     */
    public function findBy($class, array $criteria = array())
    {
        return $this->getEntityManager($class)->getRepository($class)->findBy($criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy($class, array $criteria = array())
    {
        return $this->getEntityManager($class)->getRepository($class)->findOneBy($criteria);
    }

    /**
     * @param string $class
     *
     * @return null|\Symfony\Bridge\Doctrine\EntityManager
     */
    public function getEntityManager($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $em = $this->registry->getManagerForClass($class);

        if (!$em) {
            throw new \RuntimeException(sprintf('No entity manager defined for class %s', $class));
        }

        return $em;
    }

    /**
     * {@inheritdoc}
     */
    public function getParentFieldDescription($parentAssociationMapping, $class)
    {
        $fieldName = $parentAssociationMapping['fieldName'];

        $metadata = $this->getMetadata($class);

        $associatingMapping = $metadata->associationMappings[$parentAssociationMapping];

        $fieldDescription = $this->getNewFieldDescriptionInstance($class, $fieldName);
        $fieldDescription->setName($parentAssociationMapping);
        $fieldDescription->setAssociationMapping($associatingMapping);

        return $fieldDescription;
    }

    /**
     * {@inheritdoc}
     */
    public function createQuery($class, $alias = 'o')
    {
        $repository = $this->getEntityManager($class)->getRepository($class);

        return new ProxyQuery($repository->createQueryBuilder($alias));
    }

    /**
     * {@inheritdoc}
     */
    public function executeQuery($query)
    {
        if ($query instanceof QueryBuilder) {
            return $query->getQuery()->execute();
        }

        return $query->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function getModelIdentifier($class)
    {
        return $this->getMetadata($class)->identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierValues($entity)
    {
        $entityManager = $this->getEntityManager($entity);

        if (!$entityManager->getUnitOfWork()->isInIdentityMap($entity)) {
            throw new \RuntimeException('Entities passed to the choice field must be managed');
        }

        return $entityManager->getUnitOfWork()->getEntityIdentifier($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierFieldNames($class)
    {
        return $this->getMetadata($class)->getIdentifierFieldNames();
    }

    /**
     * {@inheritdoc}
     */
    public function getNormalizedIdentifier($entity)
    {
        if (is_scalar($entity)) {
            throw new \RunTimeException('Invalid argument, object or null required');
        }

        // the entities is not managed
        if (!$entity || !$this->getEntityManager($entity)->getUnitOfWork()->isInIdentityMap($entity)) {
            return null;
        }

        $values = $this->getIdentifierValues($entity);

        return implode(self::ID_SEPARATOR, $values);
    }

    /**
     * {@inheritDoc}
     *
     * The ORM implementation does nothing special but you still should use
     * this method when using the id in a URL to allow for future improvements.
     */
    public function getUrlsafeIdentifier($entity)
    {
        return $this->getNormalizedIdentifier($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function addIdentifiersToQuery($class, ProxyQueryInterface $queryProxy, array $idx)
    {
        $fieldNames = $this->getIdentifierFieldNames($class);
        $qb = $queryProxy->getQueryBuilder();

        $prefix = uniqid();
        $sqls = array();
        foreach ($idx as $pos => $id) {
            $ids     = explode(self::ID_SEPARATOR, $id);

            $ands = array();
            foreach ($fieldNames as $posName => $name) {
                $parameterName = sprintf('field_%s_%s_%d', $prefix, $name, $pos);
                $ands[] = sprintf('%s.%s = :%s', $qb->getRootAlias(), $name, $parameterName);
                $qb->setParameter($parameterName, $ids[$posName]);
            }

            $sqls[] = implode(' AND ', $ands);
        }

        $qb->andWhere(sprintf('( %s )', implode(' OR ', $sqls)));
    }

    /**
     * {@inheritdoc}
     */
    public function batchDelete($class, ProxyQueryInterface $queryProxy)
    {
        $queryProxy->select('DISTINCT '.$queryProxy->getRootAlias());

        try {
            $entityManager = $this->getEntityManager($class);

            $i = 0;
            foreach ($queryProxy->getQuery()->iterate() as $pos => $object) {
                $entityManager->remove($object[0]);

                if ((++$i % 20) == 0) {
                    $entityManager->flush();
                    $entityManager->clear();
                }
            }

            $entityManager->flush();
            $entityManager->clear();
        } catch (\PDOException $e) {
            throw new ModelManagerException('', 0, $e);
        } catch (DBALException $e) {
            throw new ModelManagerException('', 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDataSourceIterator(DatagridInterface $datagrid, array $fields, $firstResult = null, $maxResult = null)
    {
        $datagrid->buildPager();
        $query = $datagrid->getQuery();

        $query->select('DISTINCT ' . $query->getRootAlias());
        $query->setFirstResult($firstResult);
        $query->setMaxResults($maxResult);

        if ($query instanceof ProxyQueryInterface) {
            $query->addOrderBy($query->getSortBy(), $query->getSortOrder());

            $query = $query->getQuery();
        }

        return new DoctrineORMQuerySourceIterator($query, $fields);
    }

    /**
     * {@inheritdoc}
     */
    public function getExportFields($class)
    {
        $metadata = $this->getEntityManager($class)->getClassMetadata($class);

        return $metadata->getFieldNames();
    }

    /**
     * {@inheritdoc}
     */
    public function getModelInstance($class)
    {
        $r = new \ReflectionClass($class);
        if ($r->isAbstract()) {
            throw new \RuntimeException(sprintf('Cannot initialize abstract class: %s', $class));
        }

        return new $class;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortParameters(FieldDescriptionInterface $fieldDescription, DatagridInterface $datagrid)
    {
        $values = $datagrid->getValues();

        if ($fieldDescription->getName() == $values['_sort_by']->getName() || $values['_sort_by']->getName() === $fieldDescription->getOption('sortable')) {
            if ($values['_sort_order'] == 'ASC') {
                $values['_sort_order'] = 'DESC';
            } else {
                $values['_sort_order'] = 'ASC';
            }
        } else {
            $values['_sort_order'] = 'ASC';
        }

        $values['_sort_by'] = is_string($fieldDescription->getOption('sortable')) ? $fieldDescription->getOption('sortable') :  $fieldDescription->getName();

        return array('filter' => $values);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginationParameters(DatagridInterface $datagrid, $page)
    {
        $values = $datagrid->getValues();

        $values['_sort_by'] = $values['_sort_by']->getName();
        $values['_page'] = $page;

        return array('filter' => $values);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSortValues($class)
    {
        return array(
            '_sort_order' => 'ASC',
            '_sort_by'    => implode(',', $this->getModelIdentifier($class)),
            '_page'       => 1,
            '_per_page'   => 25,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function modelTransform($class, $instance)
    {
        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function modelReverseTransform($class, array $array = array())
    {
        $instance = $this->getModelInstance($class);
        $metadata = $this->getMetadata($class);

        $reflClass = $metadata->reflClass;
        foreach ($array as $name => $value) {

            $reflection_property = false;
            // property or association ?
            if (array_key_exists($name, $metadata->fieldMappings)) {

                $property = $metadata->fieldMappings[$name]['fieldName'];
                $reflection_property = $metadata->reflFields[$name];

            } elseif (array_key_exists($name, $metadata->associationMappings)) {
                $property = $metadata->associationMappings[$name]['fieldName'];
            } else {
                $property = $name;
            }

            $setter = 'set'.$this->camelize($name);

            if ($reflClass->hasMethod($setter)) {
                if (!$reflClass->getMethod($setter)->isPublic()) {
                    throw new PropertyAccessDeniedException(sprintf('Method "%s()" is not public in class "%s"', $setter, $reflClass->getName()));
                }

                $instance->$setter($value);
            } elseif ($reflClass->hasMethod('__set')) {
                // needed to support magic method __set
                $instance->$property = $value;
            } elseif ($reflClass->hasProperty($property)) {
                if (!$reflClass->getProperty($property)->isPublic()) {
                    throw new PropertyAccessDeniedException(sprintf('Property "%s" is not public in class "%s". Maybe you should create the method "set%s()"?', $property, $reflClass->getName(), ucfirst($property)));
                }

                $instance->$property = $value;
            } elseif ($reflection_property) {
                $reflection_property->setValue($instance, $value);
            }
        }

        return $instance;
    }

    /**
     * method taken from PropertyPath
     *
     * @param string $property
     *
     * @return mixed
     */
    protected function camelize($property)
    {
        return preg_replace(array('/(^|_)+(.)/e', '/\.(.)/e'), array("strtoupper('\\2')", "'_'.strtoupper('\\1')"), $property);
    }

    /**
     * {@inheritdoc}
     */
    public function getModelCollectionInstance($class)
    {
        return new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function collectionClear(&$collection)
    {
        return $collection->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function collectionHasElement(&$collection, &$element)
    {
        return $collection->contains($element);
    }

    /**
     * {@inheritdoc}
     */
    public function collectionAddElement(&$collection, &$element)
    {
        return $collection->add($element);
    }

    /**
     * {@inheritdoc}
     */
    public function collectionRemoveElement(&$collection, &$element)
    {
        return $collection->removeElement($element);
    }
}
