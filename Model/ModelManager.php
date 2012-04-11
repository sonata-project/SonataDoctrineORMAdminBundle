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
     * Returns the related model's metadata
     *
     * @param $class
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    public function getMetadata($class)
    {
        return $this->getEntityManager($class)->getMetadataFactory()->getMetadataFor($class);
    }

    /**
     * Returns true is the model has some metadata
     *
     * @param $class
     * @return boolean
     */
    public function hasMetadata($class)
    {
        return $this->getEntityManager($class)->getMetadataFactory()->hasMetadataFor($class);
    }

    /**
     * Returns a new FieldDescription
     *
     * @throws \RunTimeException
     * @param $class
     * @param $name
     * @param array $options
     * @return \Sonata\AdminBundle\Admin\ORM\FieldDescription
     */
    public function getNewFieldDescriptionInstance($class, $name, array $options = array())
    {
        if (!is_string($name)) {
            throw new \RunTimeException('The name argument must be a string');
        }

        $metadata = $this->getMetadata($class);

        $fieldDescription = new FieldDescription;
        $fieldDescription->setName($name);
        $fieldDescription->setOptions($options);

        if (isset($metadata->associationMappings[$name])) {
            $fieldDescription->setAssociationMapping($metadata->associationMappings[$name]);
        }

        if (isset($metadata->fieldMappings[$name])) {
            $fieldDescription->setFieldMapping($metadata->fieldMappings[$name]);
        }

        return $fieldDescription;
    }

    public function create($object)
    {
        try {
            $entityManager = $this->getEntityManager($object);
            $entityManager->persist($object);
            $entityManager->flush();
        } catch ( \PDOException $e ) {
            throw new ModelManagerException('', 0, $e);
        }
    }

    public function update($object)
    {
        try {
            $entityManager = $this->getEntityManager($object);
            $entityManager->persist($object);
            $entityManager->flush();
        } catch ( \PDOException $e ) {
            throw new ModelManagerException('', 0, $e);
        }
    }

    public function delete($object)
    {
        try {
            $entityManager = $this->getEntityManager($object);
            $entityManager->remove($object);
            $entityManager->flush();
        } catch ( \PDOException $e ) {
            throw new ModelManagerException('', 0, $e);
        }
    }

    /**
     * Find one object from the given class repository.
     *
     * @param string $class Class name
     * @param string|int $id Identifier. Can be a string with several IDs concatenated, separated by '-'.
     * @return Object
     */
    public function find($class, $id)
    {
        if ( !isset($id) ) {
            return null;
        }

        $values = array_combine($this->getIdentifierFieldNames($class), explode(self::ID_SEPARATOR, $id));
        return $this->getEntityManager($class)->getRepository($class)->find($values);
    }

    /**
     * @param $class
     * @param array $criteria
     * @return array
     */
    public function findBy($class, array $criteria = array())
    {
        return $this->getEntityManager($class)->getRepository($class)->findBy($criteria);
    }

    /**
     * @param $class
     * @param array $criteria
     * @return array
     */
    public function findOneBy($class, array $criteria = array())
    {
        return $this->getEntityManager($class)->getRepository($class)->findOneBy($criteria);
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        return $this->registry->getEntityManagerForClass($class);
    }

    /**
     * @param string $parentAssociationMapping
     * @param string $class
     * @return \Sonata\AdminBundle\Admin\ORM\FieldDescription
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
     * @param $class
     * @param string $alias
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function createQuery($class, $alias = 'o')
    {
        $repository = $this->getEntityManager($class)->getRepository($class);

        return new ProxyQuery($repository->createQueryBuilder($alias));
    }

    /**
     * @param $query
     * @return mixed
     */
    public function executeQuery($query)
    {
        if ($query instanceof QueryBuilder) {
          return $query->getQuery()->execute();
        }

        return $query->execute();
    }

    /**
     * @param string $class
     * @return string
     */
    public function getModelIdentifier($class)
    {
        return $this->getMetadata($class)->identifier;
    }

    /**
     * @throws \RuntimeException
     * @param $entity
     * @return mixed
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
     * @param $class
     * @return mixed
     */
    public function getIdentifierFieldNames($class)
    {
        return $this->getMetadata($class)->getIdentifierFieldNames();
    }

    /**
     * @throws \RunTimeException
     * @param $entity
     * @return null|string
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
     * @param $class
     * @param \Sonata\AdminBundle\Datagrid\ProxyQueryInterface $queryProxy
     * @param array $idx
     * @return void
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
     * Deletes a set of $class identified by the provided $idx array
     *
     * @param $class
     * @param \Sonata\AdminBundle\Datagrid\ProxyQueryInterface $queryProxy
     * @return void
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
        } catch ( \PDOException $e ) {
            throw new ModelManagerException('', 0, $e);
        }
    }

    /**
     * @param \Sonata\AdminBundle\Datagrid\DatagridInterface $datagrid
     * @param array $fields
     * @param null $firstResult
     * @param null $maxResult
     * @return \Exporter\Source\DoctrineORMQuerySourceIterator
     */
    public function getDataSourceIterator(DatagridInterface $datagrid, array $fields, $firstResult = null, $maxResult = null)
    {
        $datagrid->buildPager();
        $query = $datagrid->getQuery();

        $query->select('DISTINCT '.$query->getRootAlias());
        $query->setFirstResult($firstResult);
        $query->setMaxResults($maxResult);

        return new DoctrineORMQuerySourceIterator($query instanceof ProxyQuery ? $query->getQuery() : $query, $fields);
    }

    /**
     * @param $class
     * @return array
     */
    public function getExportFields($class)
    {
        $metadata = $this->registry->getEntityManager()->getClassMetadata($class);

        return $metadata->getFieldNames();
    }

    /**
     * Returns a new model instance
     * @param string $class
     * @return
     */
    public function getModelInstance($class)
    {
        return new $class;
    }

    /**
     * Returns the parameters used in the columns header
     *
     * @param \Sonata\AdminBundle\Admin\FieldDescriptionInterface $fieldDescription
     * @param \Sonata\AdminBundle\Datagrid\DatagridInterface $datagrid
     * @return array
     */
    public function getSortParameters(FieldDescriptionInterface $fieldDescription, DatagridInterface $datagrid)
    {
        $values = $datagrid->getValues();

        if ($fieldDescription->getOption('sortable') == $values['_sort_by']) {
            if ($values['_sort_order'] == 'ASC') {
                $values['_sort_order'] = 'DESC';
            } else {
                $values['_sort_order'] = 'ASC';
            }
        } else {
            $values['_sort_order']  = 'ASC';
            $values['_sort_by']     = $fieldDescription->getOption('sortable');
        }

        return array('filter' => $values);
    }

    /**
     * @param \Sonata\AdminBundle\Datagrid\DatagridInterface $datagrid
     * @param $page
     * @return array
     */
    public function getPaginationParameters(DatagridInterface $datagrid, $page)
    {
        $values = $datagrid->getValues();

        $values['_page'] = $page;

        return array('filter' => $values);
    }

    /**
     * @param sring $class
     * @return array
     */
    public function getDefaultSortValues($class)
    {
        return array(
            '_sort_order' => 'ASC',
            '_sort_by'    => implode(',', $this->getModelIdentifier($class)),
            '_page'       => 1
        );
    }

    /**
     * @param string $class
     * @param object $instance
     * @return mixed
     */
    public function modelTransform($class, $instance)
    {
        return $instance;
    }

    /**
     * @param string $class
     * @param array $array
     * @return object
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

            } else if (array_key_exists($name, $metadata->associationMappings)) {
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
            } else if ($reflClass->hasMethod('__set')) {
                // needed to support magic method __set
                $instance->$property = $value;
            } else if ($reflClass->hasProperty($property)) {
                if (!$reflClass->getProperty($property)->isPublic()) {
                    throw new PropertyAccessDeniedException(sprintf('Property "%s" is not public in class "%s". Maybe you should create the method "set%s()"?', $property, $reflClass->getName(), ucfirst($property)));
                }

                $instance->$property = $value;
            } else if ($reflection_property) {
                $reflection_property->setValue($instance, $value);
            }
        }

        return $instance;
    }

    /**
     * method taken from PropertyPath
     *
     * @param  $property
     * @return mixed
     */
    protected function camelize($property)
    {
        return preg_replace(array('/(^|_)+(.)/e', '/\.(.)/e'), array("strtoupper('\\2')", "'_'.strtoupper('\\1')"), $property);
    }

    /**
     * @param string $class
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getModelCollectionInstance($class)
    {
        return new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function collectionClear(&$collection)
    {
        return $collection->clear();
    }

    public function collectionHasElement(&$collection, &$element)
    {
        return $collection->contains($element);
    }

    public function collectionAddElement(&$collection, &$element)
    {
        return $collection->add($element);
    }

    public function collectionRemoveElement(&$collection, &$element)
    {
        return $collection->removeElement($element);
    }
}
