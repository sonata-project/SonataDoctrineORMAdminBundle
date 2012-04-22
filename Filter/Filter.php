<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Filter;

use Sonata\AdminBundle\Filter\Filter as BaseFilter;

abstract class Filter extends BaseFilter
{
    protected $active = false;

    public function apply($queryBuilder, $value)
    {
        $this->value = $value;

        list($alias, $field) = $this->association($queryBuilder, $value);

        $this->filter($queryBuilder, $alias, $field, $value);
    }

    protected function association($queryBuilder, $value)
    {
        $alias = $queryBuilder->entityJoin($this->getParentAssociationMappings());

        return array($alias, $this->getFieldName());
    }

    protected function applyWhere($queryBuilder, $parameter)
    {
        if ($this->getCondition() == self::CONDITION_OR) {
            $queryBuilder->orWhere($parameter);
        } else {
            $queryBuilder->andWhere($parameter);
        }

        // filter is active since it's added to the queryBuilder
        $this->active = true;
    }

    protected function getNewParameterName($queryBuilder)
    {
        // dots are not accepted in a DQL identifier so replace them
        // by underscores.
        return str_replace('.', '_', $this->getName()).'_'.$queryBuilder->getUniqueParameterId();
    }

    public function isActive()
    {
        return $this->active;
    }
}
