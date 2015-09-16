<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 * (c) Jonathan H. Wage <jonwage@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Datagrid;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;

/**
 * This class try to unify the query usage with Doctrine.
 */
class ProxyQuery implements ProxyQueryInterface
{
    protected $queryBuilder;

    protected $sortBy;

    protected $sortOrder;

    protected $uniqueParameterId;

    protected $entityJoinAliases;

    /**
     * @var bool
     */
    protected $simpleQueryEnabled;

    /**
     * @param QueryBuilder $queryBuilder
     */
    public function __construct($queryBuilder)
    {
        $this->queryBuilder       = $queryBuilder;
        $this->uniqueParameterId  = 0;
        $this->entityJoinAliases  = array();
        $this->simpleQueryEnabled = false;
    }

    /**
     * If set to true, the generated query will not contain any duplicate identifier check (e.g. DISTINCT keyword).
     * Enabling simple qurery will improve query performance, but can also return duplicate items. It depends on the query and the database schema.
     * Please enable the simple query only if you are sure that the duplicate identifier check in the query is useless.
     *
     * @param bool $simpleQueryEnabled
     */
    public function setSimpleQueryEnabled($simpleQueryEnabled)
    {
        $this->simpleQueryEnabled = $simpleQueryEnabled;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $params = array(), $hydrationMode = null)
    {
        // always clone the original queryBuilder
        $queryBuilder = clone $this->queryBuilder;

        // todo : check how doctrine behave, potential SQL injection here ...
        if ($this->getSortBy()) {
            $sortBy = $this->getSortBy();
            if (strpos($sortBy, '.') === false) { // add the current alias
                $sortBy = $queryBuilder->getRootAlias().'.'.$sortBy;
            }
            $queryBuilder->addOrderBy($sortBy, $this->getSortOrder());
        } else {
            $queryBuilder->resetDQLPart('orderBy');
        }

        $fixedQueryBuilder = $this->getFixedQueryBuilder($queryBuilder);

        // Query builder returns no result.
        // No more queries needed.
        if (null === $fixedQueryBuilder) {
            return array();
        }

        return $fixedQueryBuilder->getQuery()->execute($params, $hydrationMode);
    }

    /**
     * This method alters the query to return a clean set of object with a working
     * set of Object.
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return QueryBuilder|null
     */
    private function getFixedQueryBuilder(QueryBuilder $queryBuilder)
    {
        $queryBuilderId = clone $queryBuilder;

        // step 1 : retrieve the targeted class
        $from  = $queryBuilderId->getDQLPart('from');
        $class = $from[0]->getFrom();
        $metadata = $queryBuilderId->getEntityManager()->getMetadataFactory()->getMetadataFor($class);

        // step 2 : retrieve identifier columns
        $idNames = $metadata->getIdentifierFieldNames();

        // step 3 : retrieve the different subjects ids
        $selects = array();
        $idxSelect = '';
        foreach ($idNames as $idName) {
            $select = sprintf('%s.%s', $queryBuilderId->getRootAlias(), $idName);
            // Put the ID select on this array to use it on results QB
            $selects[$idName] = $select;
            // Use IDENTITY if id is a relation too. See: http://doctrine-orm.readthedocs.org/en/latest/reference/dql-doctrine-query-language.html
            // Should work only with doctrine/orm: ~2.2
            $idSelect = $select;
            if ($metadata->hasAssociation($idName)) {
                $idSelect = sprintf('IDENTITY(%s) as %s', $idSelect, $idName);
            }
            $idxSelect .= ($idxSelect !== '' ? ', ' : '').$idSelect;
        }
        $queryBuilderId->resetDQLPart('select');
        $distinct = (!$this->simpleQueryEnabled) ? 'DISTINCT ' : '';
        $queryBuilderId->add('select', $distinct.$idxSelect);

        // for SELECT DISTINCT, ORDER BY expressions must appear in idxSelect list
        /* Consider
            SELECT DISTINCT x FROM tab ORDER BY y;
        For any particular x-value in the table there might be many different y
        values.  Which one will you use to sort that x-value in the output?
        */
        // todo : check how doctrine behave, potential SQL injection here ...
        if ($this->getSortBy()) {
            $sortBy = $this->getSortBy();
            if (strpos($sortBy, '.') === false) { // add the current alias
                $sortBy = $queryBuilderId->getRootAlias().'.'.$sortBy;
            }
            $sortBy .= ' AS __order_by';
            $queryBuilderId->addSelect($sortBy);
        }

        $results = $queryBuilderId->getQuery()->execute(array(), Query::HYDRATE_ARRAY);

        // If no item available, no more queries needed.
        if (empty($results)) {
            return;
        }

        $idxMatrix  = array();
        foreach ($results as $id) {
            foreach ($idNames as $idName) {
                $idxMatrix[$idName][] = $id[$idName];
            }
        }

        // reset 'where' annd remove parameters to improve query performance
        $queryBuilder->resetDQLPart('where');
        $queryBuilder->setParameters(array());

        // step 4 : alter the query to match the targeted ids
        foreach ($idxMatrix as $idName => $idx) {
            if (count($idx) > 0) {
                $idxParamName = sprintf('%s_idx', $idName);
                $queryBuilder->andWhere(sprintf('%s IN (:%s)', $selects[$idName], $idxParamName));
                $queryBuilder->setParameter($idxParamName, $idx);
                $queryBuilder->setMaxResults(null);
                $queryBuilder->setFirstResult(null);
            }
        }

        return $queryBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function __call($name, $args)
    {
        return call_user_func_array(array($this->queryBuilder, $name), $args);
    }

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        return $this->queryBuilder->$name;
    }

    /**
     * {@inheritdoc}
     */
    public function setSortBy($parentAssociationMappings, $fieldMapping)
    {
        $alias        = $this->entityJoin($parentAssociationMappings);
        $this->sortBy = $alias.'.'.$fieldMapping['fieldName'];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortBy()
    {
        return $this->sortBy;
    }

    /**
     * {@inheritdoc}
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * {@inheritdoc}
     */
    public function getSingleScalarResult()
    {
        $query = $this->queryBuilder->getQuery();

        return $query->getSingleScalarResult();
    }

    /**
     * {@inheritdoc}
     */
    public function __clone()
    {
        $this->queryBuilder = clone $this->queryBuilder;
    }

    /**
     * @return mixed
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function setFirstResult($firstResult)
    {
        $this->queryBuilder->setFirstResult($firstResult);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstResult()
    {
        return $this->queryBuilder->getFirstResult();
    }

    /**
     * {@inheritdoc}
     */
    public function setMaxResults($maxResults)
    {
        $this->queryBuilder->setMaxResults($maxResults);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxResults()
    {
        return $this->queryBuilder->getMaxResults();
    }

    /**
     * {@inheritdoc}
     */
    public function getUniqueParameterId()
    {
        return $this->uniqueParameterId++;
    }

    /**
     * {@inheritdoc}
     */
    public function entityJoin(array $associationMappings)
    {
        $alias = $this->queryBuilder->getRootAlias();

        $newAlias = 's';

        $joinedEntities = $this->queryBuilder->getDQLPart('join');

        foreach ($associationMappings as $associationMapping) {

             // Do not add left join to already joined entities with custom query
             foreach ($joinedEntities as $joinExprList) {
                 foreach ($joinExprList as $joinExpr) {
                     $newAliasTmp = $joinExpr->getAlias();

                     if (sprintf('%s.%s', $alias, $associationMapping['fieldName']) === $joinExpr->getJoin()) {
                         $this->entityJoinAliases[] = $newAliasTmp;
                         $alias = $newAliasTmp;

                         continue 3;
                     }
                 }
             }

            $newAlias .= '_'.$associationMapping['fieldName'];
            if (!in_array($newAlias, $this->entityJoinAliases)) {
                $this->entityJoinAliases[] = $newAlias;
                $this->queryBuilder->leftJoin(sprintf('%s.%s', $alias, $associationMapping['fieldName']), $newAlias);
            }

            $alias = $newAlias;
        }

        return $alias;
    }
}
