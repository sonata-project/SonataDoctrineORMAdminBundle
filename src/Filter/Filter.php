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

namespace Sonata\DoctrineORMAdminBundle\Filter;

use Doctrine\ORM\Query\Expr\Orx;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface as BaseProxyQueryInterface;
use Sonata\AdminBundle\Filter\Filter as BaseFilter;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;

abstract class Filter extends BaseFilter
{
    /**
     * @var bool
     */
    protected $active = false;

    /**
     * Holds an array of `orX` expressions used by each admin when the condition
     * equals the value on `FilterInterface::CONDITION_OR`, using the admin code
     * as index.
     *
     * @var array<string, Orx>
     */
    private static $orExpressionsByAdmin = [];

    /**
     * NEXT_MAJOR change $query type for Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface.
     *
     * Apply the filter to the QueryBuilder instance.
     *
     * @param string  $alias
     * @param string  $field
     * @param mixed[] $data
     *
     * @phpstan-param array{type?: string|int|null, value?: mixed} $data
     */
    abstract public function filter(BaseProxyQueryInterface $query, $alias, $field, $data);

    public function apply($query, $filterData)
    {
        $this->value = $filterData;
        if (\is_array($filterData) && \array_key_exists('value', $filterData)) {
            [$alias, $field] = $this->association($query, $filterData);

            $this->filter($query, $alias, $field, $filterData);
        }
    }

    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param mixed[] $data
     *
     * @return string[]
     *
     * @phpstan-param array{type?: string|int|null, value?: mixed} $data
     */
    protected function association(BaseProxyQueryInterface $query, array $data)
    {
        /* NEXT_MAJOR: Remove this deprecation and update the typehint */
        if (!$query instanceof ProxyQueryInterface) {
            @trigger_error(sprintf(
                'Passing %s as argument 1 to %s() is deprecated since sonata-project/doctrine-orm-admin-bundle 3.27'
                .' and will throw a %s error in version 4.0. You MUST pass an instance of %s instead.',
                \get_class($query),
                __METHOD__,
                \TypeError::class,
                ProxyQueryInterface::class
            ), \E_USER_DEPRECATED);
        }

        $alias = $query->entityJoin($this->getParentAssociationMappings());

        return [$alias, $this->getFieldName()];
    }

    /**
     * @param mixed $parameter
     */
    protected function applyWhere(BaseProxyQueryInterface $query, $parameter)
    {
        /* NEXT_MAJOR: Remove this deprecation and update the typehint */
        if (!$query instanceof ProxyQueryInterface) {
            @trigger_error(sprintf(
                'Passing %s as argument 1 to %s() is deprecated since sonata-project/doctrine-orm-admin-bundle 3.27'
                .' and will throw a %s error in version 4.0. You MUST pass an instance of %s instead.',
                \get_class($query),
                __METHOD__,
                \TypeError::class,
                ProxyQueryInterface::class
            ), \E_USER_DEPRECATED);
        }

        if (self::CONDITION_OR === $this->getCondition()) {
            $this->addOrParameter($query, $parameter);
        } else {
            $query->getQueryBuilder()->andWhere($parameter);
        }

        // filter is active since it's added to the queryBuilder
        $this->active = true;
    }

    /**
     * @param mixed $parameter
     */
    protected function applyHaving(ProxyQueryInterface $query, $parameter): void
    {
        if (self::CONDITION_OR === $this->getCondition()) {
            $query->getQueryBuilder()->orHaving($parameter);
        } else {
            $query->getQueryBuilder()->andHaving($parameter);
        }

        // filter is active since it's added to the queryBuilder
        $this->active = true;
    }

    /**
     * @return string
     */
    protected function getNewParameterName(BaseProxyQueryInterface $query)
    {
        /* NEXT_MAJOR: Remove this deprecation and update the typehint */
        if (!$query instanceof ProxyQueryInterface) {
            @trigger_error(sprintf(
                'Passing %s as argument 1 to %s() is deprecated since sonata-project/doctrine-orm-admin-bundle 3.27'
                .' and will throw a %s error in version 4.0. You MUST pass an instance of %s instead.',
                \get_class($query),
                __METHOD__,
                \TypeError::class,
                ProxyQueryInterface::class
            ), \E_USER_DEPRECATED);
        }

        // dots are not accepted in a DQL identifier so replace them
        // by underscores.
        return str_replace('.', '_', $this->getName()).'_'.$query->getUniqueParameterId();
    }

    /**
     * Adds the parameter to the corresponding `Orx` expression used in the `where` clause.
     * If it doesn't exist, a new one is created.
     * This method groups the filter "OR" conditions based on the "admin_code" option. If this
     * option is not set, it uses a marker (":sonata_admin_datagrid_filter_query_marker") in
     * the resulting DQL in order to identify the corresponding "WHERE (...)" condition
     * group each time it is required.
     * It allows to get queries like "WHERE previous_condition = previous_value AND (filter_1 = value OR filter_2 = value OR ...)",
     * where the logical "OR" operators added by the filters are grouped inside a condition,
     * instead of having unfolded "WHERE ..." clauses like "WHERE previous_condition = previous_value OR filter_1 = value OR filter_2 = value OR ...",
     * which will produce undesired results.
     *
     * TODO: Remove the logic related to the ":sonata_admin_datagrid_filter_query_marker" marker when
     * the constraint for "sonata-project/admin-bundle" guarantees that the "admin_code" option is set.
     *
     * @param mixed $parameter
     */
    private function addOrParameter(BaseProxyQueryInterface $query, $parameter): void
    {
        $adminCode = $this->getOption('admin_code');
        $orExpression = self::$orExpressionsByAdmin[$adminCode] ?? null;
        if ($orExpression instanceof Orx) {
            $orExpression->add($parameter);

            return;
        }

        $qb = $query->getQueryBuilder();
        $where = $qb->getDQLPart('where');

        // Search for the ":sonata_admin_datagrid_filter_query_marker" marker in order to
        // get the `Orx` expression.
        if (null === $adminCode && null !== $where) {
            foreach ($where->getParts() as $expression) {
                if (!$expression instanceof Orx) {
                    continue;
                }

                $expressionParts = $expression->getParts();

                if (isset($expressionParts[0]) && \is_string($expressionParts[0]) &&
                    0 === strpos($expressionParts[0], ':sonata_admin_datagrid_filter_query_marker')
                ) {
                    $expression->add($parameter);

                    return;
                }
            }
        }

        // Create a new `Orx` expression.
        $orExpression = $qb->expr()->orX();

        if (null === $adminCode) {
            // Add the ":sonata_admin_datagrid_filter_query_marker" parameter as marker for the `Orx` expression.
            $orExpression->add($qb->expr()->isNull(':sonata_admin_datagrid_filter_query_marker'));
            $qb->setParameter('sonata_admin_datagrid_filter_query_marker', 'sonata_admin.datagrid.filter_query.marker');
        } else {
            self::$orExpressionsByAdmin[$adminCode] = $orExpression;
        }

        $orExpression->add($parameter);

        // Add the `Orx` expression to the `where` clause.
        $qb->andWhere($orExpression);
    }
}
