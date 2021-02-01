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
     * Apply the filter to the QueryBuilder instance.
     */
    abstract public function filter(ProxyQueryInterface $query, string $alias, string $field, array $data): void;

    public function apply(BaseProxyQueryInterface $query, array $filterData): void
    {
        if (!$query instanceof ProxyQueryInterface) {
            throw new \TypeError(sprintf('The query MUST implement %s.', ProxyQueryInterface::class));
        }

        if (\array_key_exists('value', $filterData)) {
            [$alias, $field] = $this->association($query, $filterData);

            $this->filter($query, $alias, $field, $filterData);
        }
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    protected function association(ProxyQueryInterface $query, array $data): array
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
            $query->getQueryBuilder()->orWhere($parameter);
        } else {
            $query->getQueryBuilder()->andWhere($parameter);
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
}
