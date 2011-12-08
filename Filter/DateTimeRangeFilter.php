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

class DateTimeRangeFilter extends Filter
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
        
        if(is_array($data['value']['start']['date'])) {
            if(!array_key_exists('year', $data['value']['start']['date']) || !array_key_exists('month', $data['value']['start']['date']) || !array_key_exists('day', $data['value']['start']['date'])
                    || !array_key_exists('hour', $data['value']['start']['time']) || !array_key_exists('minute', $data['value']['start']['time'])
                    || !array_key_exists('year', $data['value']['end']['date']) || !array_key_exists('month', $data['value']['end']['date']) || !array_key_exists('day', $data['value']['end']['date'])
                    || !array_key_exists('hour', $data['value']['end']['time']) || !array_key_exists('minute', $data['value']['end']['time'])) {
                return;
            }

            if(trim($data['value']['start']['date']['year']) == "" || trim($data['value']['start']['date']['month']) == "" || trim($data['value']['start']['date']['day']) == ""
                    || trim($data['value']['start']['time']['hour']) == "" || trim($data['value']['start']['time']['minute']) == ""
                    || trim($data['value']['end']['date']['year']) == "" || trim($data['value']['end']['date']['month']) == "" || trim($data['value']['end']['date']['day']) == "" 
                    || trim($data['value']['end']['time']['hour']) == "" || trim($data['value']['end']['time']['minute']) == "") {
                return;
            }
            
            $startDateTime = new \DateTime($data['value']['start']['date']['year'].'-'.$data['value']['start']['date']['month'].'-'.$data['value']['start']['date']['day']
                    .' '.$data['value']['start']['time']['hour'].':'.$data['value']['start']['time']['minute']);
            $endDateTime = new \DateTime($data['value']['end']['date']['year'].'-'.$data['value']['end']['date']['month'].'-'.$data['value']['end']['date']['day']
                    .' '.$data['value']['end']['time']['hour'].':'.$data['value']['end']['time']['minute']);
        } else {
            $startDateTime = new \DateTime($data['value']['start']['date'].' '.$data['value']['start']['time']);
            $endDateTime = new \DateTime($data['value']['end']['date'].' '.$data['value']['end']['time']);
        }
        
        $start = $startDateTime->format('Y-m-d H:i:s');
        $end = $endDateTime->format('Y-m-d H:i:s');
        
        $data['type'] = !isset($data['type']) ?  DateRangeType::TYPE_BETWEEN : $data['type'];

        if($data['type'] == DateRangeType::TYPE_NOT_BETWEEN) {
            $this->applyWhere($queryBuilder, sprintf('%s.%s < :%s OR %s.%s > :%s', $alias, $field, $this->getName().'_start', $alias, $field, $this->getName().'_end'));
        } else {
            $this->applyWhere($queryBuilder, sprintf('%s.%s %s :%s', $alias, $field, '>=', $this->getName().'_start'));
            $this->applyWhere($queryBuilder, sprintf('%s.%s %s :%s', $alias, $field, '<=', $this->getName().'_end'));
        }
        
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
        return array('sonata_type_filter_datetime_range', array(
            'field_type'    => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label'         => $this->getLabel()
        ));
    }
}