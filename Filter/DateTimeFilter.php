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

use Sonata\AdminBundle\Form\Type\Filter\DateTimeType;

use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToArrayTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer;

class DateTimeFilter extends Filter
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
            if(!array_key_exists('date', $data['value']) || !array_key_exists('time', $data['value'])) {
                return;
            }
            
            if(is_array($data['value']['date'])) {
                $transformer = new DateTimeToArrayTransformer(null, null, array('year', 'month', 'day', 'hour', 'minute'));
                $valueRaw = array_merge($data['value']['date'], $data['value']['time']);
            } else {
                $transformer = new DateTimeToStringTransformer(null, null, 'Y-m-d H:i');            
                $valueRaw = $data['value']['date'].' '.$data['value']['time'];
            }
            $valueTransformed = $transformer->reverseTransform($valueRaw);

            if($valueTransformed) $value = $valueTransformed->format('Y-m-d H:i:s');
            else return;
                
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
            DateTimeType::TYPE_EQUAL            => '=',
            DateTimeType::TYPE_GREATER_EQUAL    => '>=',
            DateTimeType::TYPE_GREATER_THAN     => '>',
            DateTimeType::TYPE_LESS_EQUAL       => '<=',
            DateTimeType::TYPE_LESS_THAN        => '<',
            DateTimeType::TYPE_NULL       => 'NULL',
            DateTimeType::TYPE_NOT_NULL        => 'NOT NULL',
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
        return array('sonata_type_filter_datetime', array(
            'field_type'    => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label'         => $this->getLabel()
        ));
    }
}