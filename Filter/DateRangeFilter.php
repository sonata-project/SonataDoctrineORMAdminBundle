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

use Sonata\AdminBundle\Form\Type\Filter\DateRangeType;

class DateRangeFilter extends Filter
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param string $alias
     * @param string $field
     * @param string $data
     * @return
     */
    public function filter($queryBuilder, $alias, $field, $data)
    {
        if (!$data || !is_array($data) || !array_key_exists('value', $data)) {
            return;
        }

        if(!array_key_exists('start', $data['value']) || !array_key_exists('end', $data['value'])) {
            return;
        }
        
        if(!array_key_exists('year', $data['value']['start']) || !array_key_exists('month', $data['value']['start']) || !array_key_exists('day', $data['value']['start'])
                || !array_key_exists('year', $data['value']['end']) || !array_key_exists('month', $data['value']['end']) || !array_key_exists('day', $data['value']['end'])) {
            return;
        }
    
        if(trim($data['value']['start']['year']) == "" && trim($data['value']['start']['month']) == "" && trim($data['value']['start']['day']) == ""
                && trim($data['value']['end']['year']) == "" && trim($data['value']['end']['month']) == "" && trim($data['value']['end']['day']) == "") {
            return;
        }
        
        $start = $data['value']['start']['year'].'-'.$data['value']['start']['month'].'-'.$data['value']['start']['day'];
        $end = $data['value']['end']['year'].'-'.$data['value']['end']['month'].'-'.$data['value']['end']['day'];
                
        $this->applyWhere($queryBuilder, sprintf('%s.%s %s :%s', $alias, $field, '>=', $this->getName().'_start'));
        $this->applyWhere($queryBuilder, sprintf('%s.%s %s :%s', $alias, $field, '<=', $this->getName().'_end'));
        $queryBuilder->setParameter($this->getName().'_start',  $start);
        $queryBuilder->setParameter($this->getName().'_end',  $end);
    }

    /**
     * @return array
     */
    public function getDefaultOptions()
    {
        return array();
    }

    public function getRenderSettings()
    {
        return array('sonata_type_filter_date_range', array(
            'field_type'    => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label'         => $this->getLabel()
        ));
    }
}