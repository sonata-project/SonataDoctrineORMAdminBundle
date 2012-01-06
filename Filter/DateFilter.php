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

use Sonata\AdminBundle\Form\Type\Filter\DateType;

use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToArrayTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer;

class DateFilter extends Filter
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
        
        $data['type'] = !isset($data['type']) ?  DateType::TYPE_EQUAL : $data['type'];

        $operator = $this->getOperator((int) $data['type']);

        if (!$operator) {
            $operator = '=';
        }
       
        if(in_array($operator, array('NULL', 'NOT NULL'))) {
            $this->applyWhere($queryBuilder, sprintf('%s.%s IS %s ', $alias, $field, $operator));
        } else {
            if(is_array($data['value'])) {
                $transformer = new DateTimeToArrayTransformer(null, null, array('year', 'month', 'day'));
            } else {
                $transformer = new DateTimeToStringTransformer(null, null, 'Y-m-d');            
            }
            $valueTransformed = $transformer->reverseTransform($data['value']);

            if($valueTransformed) {
                $value = $valueTransformed->format('Y-m-d');
            } else {
                return;
            }
            
            $this->applyWhere($queryBuilder, sprintf('%s.%s %s :%s', $alias, $field, $operator, $this->getName()));
            $queryBuilder->setParameter($this->getName(),  $value);
        }
        
        
    }

    /**
     * @param $type
     * @return bool
     */
    private function getOperator($type)
    {
        $choices = array(
            DateType::TYPE_EQUAL            => '=',
            DateType::TYPE_GREATER_EQUAL    => '>=',
            DateType::TYPE_GREATER_THAN     => '>',
            DateType::TYPE_LESS_EQUAL       => '<=',
            DateType::TYPE_LESS_THAN        => '<',
            DateType::TYPE_NULL       => 'NULL',
            DateType::TYPE_NOT_NULL        => 'NOT NULL',
        );

        return isset($choices[$type]) ? $choices[$type] : false;
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
        return array('sonata_type_filter_date', array(
            'field_type'    => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label'         => $this->getLabel()
        ));
    }
}