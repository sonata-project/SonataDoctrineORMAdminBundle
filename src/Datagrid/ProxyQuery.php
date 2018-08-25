<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Datagrid;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;

/**
 * This class try to unify the query usage with Doctrine.
 */
class ProxyQuery implements ProxyQueryInterface
{
    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var string
     */
    protected $sortBy;

    /**
     * @var mixed
     */
    protected $sortOrder;

    /**
     * @var int
     */
    protected $uniqueParameterId;

    /**
     * @var string[]
     */
    protected $entityJoinAliases;

    /**
     * For BC reasons, this property is true by default.
     *
     * @var bool
     */
    private $distinct = true;

    /**
     * The map of query hints.
     *
     * @var array<string,mixed>
     */
    private $hints = [];

    /**
     * @param QueryBuilder $queryBuilder
     */
    public function __construct($queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
        $this->uniqueParameterId = 0;
        $this->entityJoinAliases = [];
    }

    public function __call($name, $args)
    {
        return \call_user_func_array([$this->queryBuilder, $name], $args);
    }

    public function __get($name)
    {
        return $this->queryBuilder->$name;
    }

    public function __clone()
    {
        $this->queryBuilder = clone $this->queryBuilder;
    }

    /**
     * Optimize queries with a lot of rows.
     * It is not recommended to use "false" with left joins.
     *
     * @param bool $distinct
     *
     * @return self
     */
    final public function setDistinct($distinct)
    {
        if (!\is_bool($distinct)) {
            throw new \InvalidArgumentException('$distinct is not a boolean');
        }

        $this->distinct = $distinct;

        return $this;
    }

    /**
     * @return bool
     */
    final public function isDistinct()
    {
        return $this->distinct;
    }

    public function execute(array $params = [], $hydrationMode = null)
    {
        // always clone the original queryBuilder
        $queryBuilder = clone $this->queryBuilder;

        $rootAlias = current($queryBuilder->getRootAliases());

        // todo : check how doctrine behave, potential SQL injection here ...
        if ($this->getSortBy()) {
            $sortBy = $this->getSortBy();
            if (false === strpos($sortBy, '.')) { // add the current alias
                $sortBy = $rootAlias.'.'.$sortBy;
            }
            $queryBuilder->addOrderBy($sortBy, $this->getSortOrder());
        } else {
            $queryBuilder->resetDQLPart('orderBy');
        }

        /* By default, always add a sort on the identifier fields of the first
         * used entity in the query, because RDBMS do not guarantee a
         * particular order when no ORDER BY clause is specified, or when
         * the field used for sorting is not unique.
         */

        $identifierFields = $queryBuilder
            ->getEntityManager()
            ->getMetadataFactory()
            ->getMetadataFor(current($queryBuilder->getRootEntities()))
            ->getIdentifierFieldNames();

        $existingOrders = [];
        /** @var Query\Expr\OrderBy $order */
        foreach ($queryBuilder->getDQLPart('orderBy') as $order) {
            foreach ($order->getParts() as $part) {
                $existingOrders[] = trim(str_replace([Criteria::DESC, Criteria::ASC], '', $part));
            }
        }

        foreach ($identifierFields as $identifierField) {
            $order = $rootAlias.'.'.$identifierField;
            if (!\in_array($order, $existingOrders)) {
                $queryBuilder->addOrderBy(
                    $order,
                    $this->getSortOrder() // reusing the sort order is the most natural way to go
                );
            }
        }

        $query = $this->getFixedQueryBuilder($queryBuilder)->getQuery();
        foreach ($this->hints as $name => $value) {
            $query->setHint($name, $value);
        }

        return $query->execute($params, $hydrationMode);
    }

    public function setSortBy($parentAssociationMappings, $fieldMapping)
    {
        $alias = $this->entityJoin($parentAssociationMappings);
        $this->sortBy = $alias.'.'.$fieldMapping['fieldName'];

        return $this;
    }

    public function getSortBy()
    {
        return $this->sortBy;
    }

    public function setSortOrder($sortOrder)
    {
        if (!\in_array(strtoupper($sortOrder), $validSortOrders = ['ASC', 'DESC'])) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid sort order, valid values are "%s"',
                $sortOrder,
                implode(', ', $validSortOrders)
            ));
        }
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    public function getSingleScalarResult()
    {
        $query = $this->queryBuilder->getQuery();

        return $query->getSingleScalarResult();
    }

    /**
     * @return mixed
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    public function setFirstResult($firstResult)
    {
        $this->queryBuilder->setFirstResult($firstResult);

        return $this;
    }

    public function getFirstResult()
    {
        return $this->queryBuilder->getFirstResult();
    }

    public function setMaxResults($maxResults)
    {
        $this->queryBuilder->setMaxResults($maxResults);

        return $this;
    }

    public function getMaxResults()
    {
        return $this->queryBuilder->getMaxResults();
    }

    public function getUniqueParameterId()
    {
        return $this->uniqueParameterId++;
    }

    public function entityJoin(array $associationMappings)
    {
        $alias = current($this->queryBuilder->getRootAliases());

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
            if (!\in_array($newAlias, $this->entityJoinAliases)) {
                $this->entityJoinAliases[] = $newAlias;
                $this->queryBuilder->leftJoin(sprintf('%s.%s', $alias, $associationMapping['fieldName']), $newAlias);
            }

            $alias = $newAlias;
        }

        return $alias;
    }

    /**
     * Sets a {@see \Doctrine\ORM\Query} hint. If the hint name is not recognized, it is silently ignored.
     *
     * @param string $name  the name of the hint
     * @param mixed  $value the value of the hint
     *
     * @return ProxyQueryInterface
     *
     * @see \Doctrine\ORM\Query::setHint
     * @see \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER
     */
    final public function setHint($name, $value)
    {
        $this->hints[$name] = $value;

        return $this;
    }

    /**
     * This method alters the query to return a clean set of object with a working
     * set of Object.
     *
     * @return QueryBuilder
     */
    protected function getFixedQueryBuilder(QueryBuilder $queryBuilder)
    {
        $queryBuilderId = clone $queryBuilder;
        $rootAlias = current($queryBuilderId->getRootAliases());

        // step 1 : retrieve the targeted class
        $from = $queryBuilderId->getDQLPart('from');
        $class = $from[0]->getFrom();
        $metadata = $queryBuilderId->getEntityManager()->getMetadataFactory()->getMetadataFor($class);

        // step 2 : retrieve identifier columns
        $idNames = $metadata->getIdentifierFieldNames();

        // step 3 : retrieve the different subjects ids
        $selects = [];
        $idxSelect = '';
        foreach ($idNames as $idName) {
            $select = sprintf('%s.%s', $rootAlias, $idName);
            // Put the ID select on this array to use it on results QB
            $selects[$idName] = $select;
            // Use IDENTITY if id is a relation too.
            // See: http://doctrine-orm.readthedocs.org/en/latest/reference/dql-doctrine-query-language.html
            // Should work only with doctrine/orm: ~2.2
            $idSelect = $select;
            if ($metadata->hasAssociation($idName)) {
                $idSelect = sprintf('IDENTITY(%s) as %s', $idSelect, $idName);
            }
            $idxSelect .= ('' !== $idxSelect ? ', ' : '').$idSelect;
        }
        $queryBuilderId->select($idxSelect);
        $queryBuilderId->distinct($this->isDistinct());

        // for SELECT DISTINCT, ORDER BY expressions must appear in idxSelect list
        /* Consider
            SELECT DISTINCT x FROM tab ORDER BY y;
        For any particular x-value in the table there might be many different y
        values.  Which one will you use to sort that x-value in the output?
        */
        $queryId = $queryBuilderId->getQuery();
        $queryId->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [OrderByToSelectWalker::class]);
        $results = $queryId->execute([], Query::HYDRATE_ARRAY);
        $platform = $queryBuilderId->getEntityManager()->getConnection()->getDatabasePlatform();
        $idxMatrix = [];
        foreach ($results as $id) {
            foreach ($idNames as $idName) {
                // Convert ids to database value in case of custom type, if provided.
                $fieldType = $metadata->getTypeOfField($idName);
                $idxMatrix[$idName][] = $fieldType && Type::hasType($fieldType)
                    ? Type::getType($fieldType)->convertToDatabaseValue($id[$idName], $platform)
                    : $id[$idName];
            }
        }

        // step 4 : alter the query to match the targeted ids
        foreach ($idxMatrix as $idName => $idx) {
            if (\count($idx) > 0) {
                $idxParamName = sprintf('%s_idx', $idName);
                $idxParamName = preg_replace('/[^\w]+/', '_', $idxParamName);
                $queryBuilder->andWhere(sprintf('%s IN (:%s)', $selects[$idName], $idxParamName));
                $queryBuilder->setParameter($idxParamName, $idx);
                $queryBuilder->setMaxResults(null);
                $queryBuilder->setFirstResult(null);
            }
        }

        return $queryBuilder;
    }
}
