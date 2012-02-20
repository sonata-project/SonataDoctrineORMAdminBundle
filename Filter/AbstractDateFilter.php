<?php

namespace Sonata\DoctrineORMAdminBundle\Filter;

abstract class AbstractDateFilter extends Filter
{
    protected $range = false;
    protected $time = false;

    public function filter($queryBuilder, $alias, $field, $data)
    {
        //check data sanity
        if (!$data || !is_array($data) || !array_key_exists('value', $data)) {
            return;
        }

        if ($this->range) {
            //this is a ranged query, we need to decide between 'between' and 'not between'
        } else {
            //just find an operator and apply query
        }
    }

    public function getDefaultOptions()
    {
        return array();
    }

    public function getRenderSettings()
    {
        $name = 'sonata_type_filter_date';

        if ($this->time) {
            $name .= 'time';
        }

        if ($this->range) {
            $name .= '_range';
        }

        return array($name, array(
            'field_type'    => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label'         => $this->getLabel()
        ));
    }
}
