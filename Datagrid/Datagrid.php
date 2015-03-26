<?php

/*
 * This file is part of the Sonata package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Datagrid;

use Sonata\AdminBundle\Datagrid\Datagrid as BaseDatagrid;

class Datagrid extends BaseDatagrid
{
    /**
     * @author Sören Bernstein
     * @date   2015-03-26
     *
     * Fixing: Wrong search results when using custom query #2850
     *       by removing the where part from the query prior to appliing
     *       the filters and then adding it back.
     *
     *       This is probably not the best solution but seems to fix the problem.
     */
    protected function applyFilters()
    {
        $data = $this->form->getData();

        if ($this->getFilters()) {
            // Get the current where part
            $where = $this->query->getDqlPart('where');
            // If the where part is not empty, store it (clone) and reset where part in query builder
            if (!empty($where)) {
                $where = clone $where;
                $this->query->resetDQLPart('where');
            }

            foreach ($this->getFilters() as $name => $filter) {
                $this->values[$name] = isset($this->values[$name]) ? $this->values[$name] : null;
                $filter->apply($this->query, $data[$filter->getFormName()]);
            }

            // If there were a where part removed from the query, reapply it now, so the QueryBuilder will
            // surrond the filter part with parentheses.
            if (!empty($where)) {
                $this->query->andWhere($where);
            }
        }
    }

}
