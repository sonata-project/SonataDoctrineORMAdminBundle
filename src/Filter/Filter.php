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

use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\Query\Expr\Orx;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface as BaseProxyQueryInterface;
use Sonata\AdminBundle\Filter\Filter as BaseFilter;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;

abstract class Filter extends BaseFilter implements GroupableConditionAwareInterface
{
    /**
     * @var bool
     */
    protected $active = false;

    /**
     * @var Composite|null
     */
    private $conditionGroup;

    /**
     * Holds an array of grouped `orX` filter expressions that must be used within
     * the same query builder.
     *
     * @deprecated since sonata-project/doctrine-orm-admin-bundle 3.x, will be removed in version 4.0.
     *
     * @var array<string, Orx>
     */
    private static $groupedOrExpressions = [];

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

    public function setConditionGroup(Composite $conditionGroup): void
    {
        $this->conditionGroup = $conditionGroup;
    }

    public function getConditionGroup(): ?Composite
    {
        return $this->conditionGroup;
    }

    public function hasConditionGroup(): bool
    {
        return null !== $this->conditionGroup;
    }

    /**
     * @param mixed[] $data
     *
     * @return string[]
     *
     * @phpstan-param array{type?: int|null, value?: mixed} $data
     * @phpstan-return array{string, string}
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
     * This method groups the filter "OR" conditions based on the "or_group" option.
     * It allows to get queries like "WHERE previous_condition = previous_value AND (filter_1 = value OR filter_2 = value OR ...)",
     * where the logical "OR" operators added by the filters are grouped inside a condition,
     * instead of having unfolded "WHERE ..." clauses like "WHERE previous_condition = previous_value OR filter_1 = value OR filter_2 = value OR ...",
     * which will produce undesired results.
     *
     * @param mixed $parameter
     */
    private function addOrParameter(BaseProxyQueryInterface $query, $parameter): void
    {
        $conditionGroup = null;

        if ($this->hasPreviousFilter()) {
            $previousFilter = $this->getPreviousFilter();

            if ($previousFilter->hasConditionGroup()) {
                $conditionGroup = $previousFilter->getConditionGroup();
                $conditionGroup->add($parameter);

                $this->setConditionGroup($conditionGroup);

                return;
            }
        }

        $groupName = $this->getOption('or_group');
        // NEXT_MAJOR: Remove the previous assignment and the next conditional block.
        if (null !== $groupName) {
            @trigger_error(sprintf(
                'Option "or_group" is deprecated since sonata-project/doctrine-orm-admin-bundle 3.x and will be removed in version 4.0.'
                .' Use "%s::setConditionGroup()" instead.',
                static::class
            ), \E_USER_DEPRECATED);

            $conditionGroup = self::$groupedOrExpressions[$groupName] ?? null;
            if ($conditionGroup instanceof Orx) {
                $conditionGroup->add($parameter);

                $this->setConditionGroup($conditionGroup);

                return;
            }
        }

        $qb = $query->getQueryBuilder();

        // Create a new `Orx` expression.
        $conditionGroup = $qb->expr()->orX();

        $conditionGroup->add($parameter);

        // Add the `Orx` expression to the `where` clause.
        $qb->andWhere($conditionGroup);

        $this->setConditionGroup($conditionGroup);

        // NEXT_MAJOR: Remove the following block.
        if (null !== $groupName) {
            self::$groupedOrExpressions[$groupName] = $conditionGroup;
        }
    }
}
