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

namespace Sonata\DoctrineORMAdminBundle\Datagrid;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface as BaseProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Util\SmartPaginatorFactory;

/**
 * This class try to unify the query usage with Doctrine.
 *
 * @method Query\Expr           expr()
 * @method QueryBuilder         setCacheable($cacheable)
 * @method bool                 isCacheable()
 * @method QueryBuilder         setCacheRegion($cacheRegion)
 * @method string|null          getCacheRegion()
 * @method int                  getLifetime()
 * @method QueryBuilder         setLifetime($lifetime)
 * @method int                  getCacheMode()
 * @method QueryBuilder         setCacheMode($cacheMode)
 * @method int                  getType()
 * @method EntityManager        getEntityManager()
 * @method int                  getState()
 * @method string               getDQL()
 * @method Query                getQuery()
 * @method string               getRootAlias()
 * @method string[]             getRootAliases()
 * @method string[]             getAllAliases()
 * @method string[]             getRootEntities()
 * @method QueryBuilder         setParameter($key, $value, $type = null)
 * @method QueryBuilder         setParameters($parameters)
 * @method ArrayCollection      getParameters()
 * @method Query\Parameter|null getParameter($key)
 * @method QueryBuilder         add($dqlPartName, $dqlPart, $append = false)
 * @method QueryBuilder         select($select = null)
 * @method QueryBuilder         distinct($flag = true)
 * @method QueryBuilder         addSelect($select = null)
 * @method QueryBuilder         delete($delete = null, $alias = null)
 * @method QueryBuilder         update($update = null, $alias = null)
 * @method QueryBuilder         from($from, $alias, $indexBy = null)
 * @method QueryBuilder         indexBy($alias, $indexBy)
 * @method QueryBuilder         join($join, $alias, $conditionType = null, $condition = null, $indexBy = null)
 * @method QueryBuilder         innerJoin($join, $alias, $conditionType = null, $condition = null, $indexBy = null)
 * @method QueryBuilder         leftJoin($join, $alias, $conditionType = null, $condition = null, $indexBy = null)
 * @method QueryBuilder         set($key, $value)
 * @method QueryBuilder         where($where)
 * @method QueryBuilder         andWhere($where)
 * @method QueryBuilder         orWhere($where)
 * @method QueryBuilder         groupBy($groupBy)
 * @method QueryBuilder         addGroupBy($groupBy)
 * @method QueryBuilder         having($having)
 * @method QueryBuilder         andHaving($having)
 * @method QueryBuilder         orHaving($having)
 * @method QueryBuilder         orderBy($sort, $order = null)
 * @method QueryBuilder         addOrderBy($sort, $order = null)
 * @method QueryBuilder         addCriteria(Criteria $criteria)
 * @method mixed                getDQLPart($queryPartName)
 * @method array                getDQLParts()
 * @method QueryBuilder         resetDQLParts($parts = null)
 * @method QueryBuilder         resetDQLPart($part)
 */
final class ProxyQuery implements ProxyQueryInterface
{
    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var string|null
     */
    private $sortBy;

    /**
     * @var string|null
     */
    private $sortOrder;

    /**
     * @var int
     */
    private $uniqueParameterId;

    /**
     * @var string[]
     */
    private $entityJoinAliases;

    /**
     * The map of query hints.
     *
     * @var array<string,mixed>
     */
    private $hints = [];

    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
        $this->uniqueParameterId = 0;
        $this->entityJoinAliases = [];
    }

    /**
     * @param mixed[] $args
     *
     * @return mixed
     */
    public function __call(string $name, array $args)
    {
        return $this->queryBuilder->$name(...$args);
    }

    /**
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->queryBuilder->$name;
    }

    public function __clone()
    {
        $this->queryBuilder = clone $this->queryBuilder;
    }

    /**
     * @return Paginator<object>
     */
    public function execute()
    {
        return SmartPaginatorFactory::create($this, $this->hints);
    }

    /**
     * This method alters the query in order to
     *     - update the sortBy of the doctrine query in order to use the one provided
     *       by the ProxyQueryInterface Api.
     *     - add a sort on the identifier fields of the first used entity in the query,
     *       because RDBMS do not guarantee a particular order when no ORDER BY clause
     *       is specified, or when the field used for sorting is not unique.
     */
    public function getDoctrineQuery(): Query
    {
        // Always clone the original queryBuilder
        $queryBuilder = clone $this->queryBuilder;

        $rootAlias = current($queryBuilder->getRootAliases());

        $sortBy = $this->getSortBy();
        if (null !== $sortBy) {
            $orderByDQLPart = $queryBuilder->getDQLPart('orderBy');
            $queryBuilder->resetDQLPart('orderBy');

            if (false === strpos($sortBy, '.')) {
                $sortBy = $rootAlias.'.'.$sortBy;
            }

            $queryBuilder->addOrderBy($sortBy, $this->getSortOrder());
            foreach ($orderByDQLPart as $orderBy) {
                $queryBuilder->addOrderBy($orderBy);
            }
        }

        $identifierFields = $queryBuilder
            ->getEntityManager()
            ->getMetadataFactory()
            ->getMetadataFor(current($queryBuilder->getRootEntities()))
            ->getIdentifierFieldNames();

        $existingOrders = [];
        foreach ($queryBuilder->getDQLPart('orderBy') as $order) {
            foreach ($order->getParts() as $part) {
                $existingOrders[] = trim(str_replace([Criteria::DESC, Criteria::ASC], '', $part));
            }
        }

        foreach ($identifierFields as $identifierField) {
            $field = $rootAlias.'.'.$identifierField;

            if (!\in_array($field, $existingOrders, true)) {
                $queryBuilder->addOrderBy($field, $this->getSortOrder());
            }
        }

        return $queryBuilder->getQuery();
    }

    public function setSortBy(array $parentAssociationMappings, array $fieldMapping): BaseProxyQueryInterface
    {
        $alias = $this->entityJoin($parentAssociationMappings);
        $this->sortBy = $alias.'.'.$fieldMapping['fieldName'];

        return $this;
    }

    public function getSortBy(): ?string
    {
        return $this->sortBy;
    }

    public function setSortOrder(string $sortOrder): BaseProxyQueryInterface
    {
        if (!\in_array(strtoupper($sortOrder), $validSortOrders = ['ASC', 'DESC'], true)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid sort order, valid values are "%s"',
                $sortOrder,
                implode(', ', $validSortOrders)
            ));
        }
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function getSortOrder(): ?string
    {
        return $this->sortOrder;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function setFirstResult(?int $firstResult): BaseProxyQueryInterface
    {
        $this->queryBuilder->setFirstResult($firstResult);

        return $this;
    }

    public function getFirstResult(): ?int
    {
        return $this->queryBuilder->getFirstResult();
    }

    public function setMaxResults(?int $maxResults): BaseProxyQueryInterface
    {
        $this->queryBuilder->setMaxResults($maxResults);

        return $this;
    }

    public function getMaxResults(): ?int
    {
        return $this->queryBuilder->getMaxResults();
    }

    public function getUniqueParameterId(): int
    {
        return $this->uniqueParameterId++;
    }

    public function entityJoin(array $associationMappings): string
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
            if (!\in_array($newAlias, $this->entityJoinAliases, true)) {
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
     * @see \Doctrine\ORM\Query::setHint
     * @see \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER
     */
    public function setHint(string $name, $value): ProxyQueryInterface
    {
        $this->hints[$name] = $value;

        return $this;
    }
}
