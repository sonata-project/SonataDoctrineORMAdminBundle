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

use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToArrayTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer;

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
        
        if(is_array($data['value']['start'])) {
            $transformer = new DateTimeToArrayTransformer(null, null, array('year', 'month', 'day'));
        } else {
            $transformer = new DateTimeToStringTransformer(null, null, 'Y-m-d');            
        }
        $startValueTransformed = $transformer->reverseTransform($data['value']['start']);
        $endValueTransformed = $transformer->reverseTransform($data['value']['end']);
                
        if($startValueTransformed && $endValueTransformed) {
            $startValue = $startValueTransformed->format('Y-m-d');
            $endValue = $endValueTransformed->format('Y-m-d');
        } else {
            return;
        }
        
        $data['type'] = !isset($data['type']) ?  DateRangeType::TYPE_BETWEEN : $data['type'];

        if($data['type'] == DateRangeType::TYPE_NOT_BETWEEN) {
            $this->applyWhere($queryBuilder, sprintf('%s.%s < :%s OR %s.%s > :%s', $alias, $field, $this->getName().'_start', $alias, $field, $this->getName().'_end'));
        } else {
            $this->applyWhere($queryBuilder, sprintf('%s.%s %s :%s', $alias, $field, '>=', $this->getName().'_start'));
            $this->applyWhere($queryBuilder, sprintf('%s.%s %s :%s', $alias, $field, '<=', $this->getName().'_end'));
        }
        
        $queryBuilder->setParameter($this->getName().'_start',  $startValue);
        $queryBuilder->setParameter($this->getName().'_end',  $endValue);
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