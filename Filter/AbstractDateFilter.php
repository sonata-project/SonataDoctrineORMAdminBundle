<?php

namespace Sonata\DoctrineORMAdminBundle\Filter;

use Sonata\AdminBundle\Form\Type\Filter\DateType;
use Sonata\AdminBundle\Form\Type\Filter\DateRangeType;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToArrayTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer;

abstract class AbstractDateFilter extends Filter
{
    /**
     * Flag indicating that filter will have range
     * @var boolean
     */
    protected $range = false;

    /**
     * Flag indicating that filter will filter by datetime instead by date
     * @var boolean
     */
    protected $time = false;

    /**
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param string $alias
     * @param string $field
     * @param array $data
     * @return
     */
    public function filter($queryBuilder, $alias, $field, $data)
    {
        //check data sanity
        if (!$data || !is_array($data) || !array_key_exists('value', $data)) {
            return;
        }

        if ($this->range) {
            //additional data check for ranged items
            if (!array_key_exists('start', $data['value']) || !array_key_exists('end', $data['value'])) {
                return;
            }

            //default type for range filter
            $data['type'] = !isset($data['type']) || !is_numeric($data['type']) ?  DateRangeType::TYPE_BETWEEN : $data['type'];

            //this is a ranged query, we need to decide between 'between' and 'not between'
            $valueStart = $this->transformInput($data['value']['start']);
            $valueEnd = $this->transformInput($data['value']['end']);

            if (!$valueStart || !$valueEnd) {
                return;
            }

            if ($data['type'] == DateRangeType::TYPE_NOT_BETWEEN) {
                $this->applyWhere($queryBuilder, sprintf('%s.%s < :%s OR %s.%s > :%s', $alias, $field, $this->getName().'_start', $alias, $field, $this->getName().'_end'));
            } else {
                $this->applyWhere($queryBuilder, sprintf('%s.%s %s :%s', $alias, $field, '>=', $this->getName().'_start'));
                $this->applyWhere($queryBuilder, sprintf('%s.%s %s :%s', $alias, $field, '<=', $this->getName().'_end'));
            }

            $queryBuilder->setParameter($this->getName().'_start',  $valueStart);
            $queryBuilder->setParameter($this->getName().'_end',  $valueEnd);
        } else {
            //default type for simple filter
            $data['type'] = !isset($data['type']) || !is_numeric($data['type']) ? DateType::TYPE_EQUAL : $data['type'];

            //just find an operator and apply query
            $operator = $this->getOperator($data['type']);

            //null / not null only check for col
            if (in_array($operator, array('NULL', 'NOT NULL'))) {
                $this->applyWhere($queryBuilder, sprintf('%s.%s IS %s ', $alias, $field, $operator));
            } else {
                $value = $this->transformInput($data['value']);

                if (!$value) {
                    return;
                }

                $this->applyWhere($queryBuilder, sprintf('%s.%s %s :%s', $alias, $field, $operator, $this->getName()));
                $queryBuilder->setParameter($this->getName(),  $value);
            }
        }
    }

    /**
     * Transforms input based on value type and class attributes,
     * returns false on failure
     * @param mixed $input
     * @return mixed
     */
    protected function transformInput($input)
    {
        $outputFormat = $this->time ? 'Y-m-d H:i:s' : 'Y-m-d';

        if (is_array($input)) {
            if ($this->time) {
                $transformer = new DateTimeToArrayTransformer(null, null, array('year', 'month', 'day', 'hour', 'minute'));
                $input = array_merge($input['date'], $input['time']);
            } else {
                $transformer = new DateTimeToArrayTransformer(null, null, array('year', 'month', 'day'));
            }
        }

        if (is_string($input)) {
            $transformer = new DateTimeToStringTransformer(null, null, $this->getFieldFormat());
        }

        $transformedValue = $transformer->reverseTransform($input);

        if ($transformedValue) {
            return $transformedValue->format($outputFormat);
        }

        return false;
    }

    /**
     * Returns date format if passed to widget
     * @return string
     */
    protected function getFieldFormat()
    {
        $options = $this->getFieldOptions();

        if (array_key_exists('format', $options)) {
            return $options['format'];
        }

        return 'Y-m-d';
    }

    /**
     * Resolves DataType:: constants to SQL operators
     * @param integer $type
     * @return string
     */
    protected function getOperator($type)
    {
        $type = intval($type);

        $choices = array(
            DateType::TYPE_EQUAL            => '=',
            DateType::TYPE_GREATER_EQUAL    => '>=',
            DateType::TYPE_GREATER_THAN     => '>',
            DateType::TYPE_LESS_EQUAL       => '<=',
            DateType::TYPE_LESS_THAN        => '<',
            DateType::TYPE_NULL             => 'NULL',
            DateType::TYPE_NOT_NULL         => 'NOT NULL',
        );

        return isset($choices[$type]) ? $choices[$type] : '=';
    }

    /**
     * Gets default options
     * @return array
     */
    public function getDefaultOptions()
    {
        return array();
    }

    /**
     * Gets render settings
     * @return array
     */
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
