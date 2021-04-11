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
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;

abstract class Filter extends BaseFilter
{
    /**
     * @var bool
     */
    protected $active = false;

    /**
     * Holds an array of grouped `orX` filter expressions that must be used within
     * the same query builder.
     *
     * @var array<string, Orx>
     */
    private static $groupedOrExpressions = [];

    /**
     * Apply the filter to the QueryBuilder instance.
     */
    abstract public function filter(ProxyQueryInterface $query, string $alias, string $field, FilterData $data): void;

    public function apply(BaseProxyQueryInterface $query, FilterData $filterData): void
    {
        if (!$query instanceof ProxyQueryInterface) {
            throw new \TypeError(sprintf('The query MUST implement %s.', ProxyQueryInterface::class));
        }

        if ($filterData->hasValue()) {
            [$alias, $field] = $this->association($query, $filterData);

            $this->filter($query, $alias, $field, $filterData);
        }
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @return string[]
     * @phpstan-return array{string, string}
     */
    protected function association(ProxyQueryInterface $query, FilterData $data): array
    {
        $alias = $query->entityJoin($this->getParentAssociationMappings());

        return [$alias, $this->getFieldName()];
    }

    /**
     * @param mixed $parameter
     */
    protected function applyWhere(ProxyQueryInterface $query, $parameter): void
    {
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

    protected function getNewParameterName(ProxyQueryInterface $query): string
    {
        // dots are not accepted in a DQL identifier so replace them
        // by underscores.
        return str_replace('.', '_', $this->getName()).'_'.$query->getUniqueParameterId();
    }

    /**
     * Adds the parameter to the corresponding `Orx` expression used in the `where` clause.
     * If it doesn't exist, a new one is created.
     * This method groups the filter "OR" conditions based on the "or_group" option. If this
     * option is not set, it uses a marker (":sonata_admin_datagrid_filter_query_marker") in
     * the resulting DQL in order to identify the corresponding "WHERE (...)" condition
     * group each time it is required.
     * It allows to get queries like "WHERE previous_condition = previous_value AND (filter_1 = value OR filter_2 = value OR ...)",
     * where the logical "OR" operators added by the filters are grouped inside a condition,
     * instead of having unfolded "WHERE ..." clauses like "WHERE previous_condition = previous_value OR filter_1 = value OR filter_2 = value OR ...",
     * which will produce undesired results.
     *
     * @param mixed $parameter
     */
    private function addOrParameter(ProxyQueryInterface $query, $parameter): void
    {
        $groupName = $this->getOption('or_group');
        $orExpression = self::$groupedOrExpressions[$groupName] ?? null;
        if ($orExpression instanceof Orx) {
            $orExpression->add($parameter);

            return;
        }

        $qb = $query->getQueryBuilder();
        $where = $qb->getDQLPart('where');

        // Search for the ":sonata_admin_datagrid_filter_query_marker" marker in order to
        // get the `Orx` expression.
        if (null === $groupName && null !== $where) {
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

        if (null === $groupName) {
            // Add the ":sonata_admin_datagrid_filter_query_marker" parameter as marker for the `Orx` expression.
            $orExpression->add($qb->expr()->isNull(':sonata_admin_datagrid_filter_query_marker'));
            $qb->setParameter('sonata_admin_datagrid_filter_query_marker', 'sonata_admin.datagrid.filter_query.marker');
        } else {
            self::$groupedOrExpressions[$groupName] = $orExpression;
        }

        $orExpression->add($parameter);

        // Add the `Orx` expression to the `where` clause.
        $qb->andWhere($orExpression);
    }
}
