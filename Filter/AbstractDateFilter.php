<?php

namespace Sonata\DoctrineORMAdminBundle\Filter;

use Sonata\AdminBundle\Form\Type\Filter\DateRangeType;
use Sonata\AdminBundle\Form\Type\Filter\DateType;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;

abstract class AbstractDateFilter extends Filter
{
    /**
     * {@inheritdoc}
     */
    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $data)
    {
        /** @var \Doctrine\ORM\QueryBuilder|ProxyQueryInterface $queryBuilder */
        if (!is_array($data) || !array_key_exists('value', $data)) {
            return;
        }
        $value = $data['value'];
        if (!$this->isValidValue($value)) {
            return;
        }

        $aliasField = sprintf('%s.%s', $alias, $field);
        $filterType = isset($data['type']) && is_numeric($data['type']) ? $data['type'] : null;

        if($this->isRangedCondition($value, $filterType)) {
            $filterType = $this->normalizeFilterType($filterType, $value, true);

            list($start, $end) = $this->normalizeValue($value, true, $filterType);

            $this->addRangeCondition($queryBuilder, $aliasField, $filterType, $start, $end);
        } else {
            $filterType = $this->normalizeFilterType($filterType, $value, false);

            $value = $this->normalizeValue($value, false, $filterType);

            $this->addCondition($queryBuilder, $aliasField, $filterType, $value);
        }
    }

    /**
     * Indicates that the given value is valid.
     *
     * @param mixed $value The raw filter value
     * @return bool
     */
    protected function isValidValue($value)
    {
        return $value instanceof \DateTime || $value === null;
    }

    /**
     * Indicates if the filter will apply a ranged condition.
     * Beware that if you override this method `normalizeValue` needs to be override as well.
     *
     * @param mixed $value The raw filter value
     * @param int $filterType DateType::TYPE_* or DateRangeType::TYPE_*
     * @return bool
     */
    protected function isRangedCondition($value, $filterType)
    {
        return false;
    }

    /**
     * Normalize the data given to the filter.
     *
     * @param mixed $value The raw filter value
     * @param bool $ranged Is this a ranged condition
     * @param int $filterType DateType::TYPE_* or DateRangeType::TYPE_*
     * @return mixed
     */
    protected function normalizeValue($value, $ranged, $filterType)
    {
        if ($ranged) {
            throw new \LogicException('Did you override isRangedCondition and forget normalizeValue?');
        }
        if (in_array($filterType, array(DateType::TYPE_NULL, DateType::TYPE_NOT_NULL))) {
            return null;
        }
        if ($this->getOption('input_type') === 'timestamp') {
            return $value->getTimestamp();
        }
        return $value;
    }

    /**
     * Normalize the filter type.
     *
     * @param int $filterType DateType::TYPE_* or DateRangeType::TYPE_*
     * @param mixed $value The raw filter value
     * @param bool $ranged Is this a ranged condition
     * @return int A DateType::TYPE_*
     */
    protected function normalizeFilterType($filterType, $value, $ranged)
    {
        if ($value === null) {
            return $filterType === DateType::TYPE_NOT_NULL ? $filterType : DateType::TYPE_NULL;
        }
        if ($ranged) {
            return $filterType ?: DateRangeType::TYPE_BETWEEN;
        }
        return $filterType ?: DateType::TYPE_EQUAL;
    }

    /**
     * @param ProxyQueryInterface $queryBuilder
     * @param string $field
     * @param int $type DateType::TYPE_* or DateRangeType::TYPE_*
     * @param \DateTime|int $value
     */
    protected function addCondition(ProxyQueryInterface $queryBuilder, $field, $type, $value)
    {
        /** @var ProxyQueryInterface|\Doctrine\ORM\QueryBuilder $queryBuilder */
        if (in_array($type, array(DateType::TYPE_NULL, DateType::TYPE_NOT_NULL))) {
            $this->applyWhere($queryBuilder, sprintf('%s IS %s', $field, $this->getOperator($type)));
            return;
        }

        $parameterName = $this->getNewParameterName($queryBuilder);
        $this->applyWhere(
            $queryBuilder,
            sprintf('%s %s :%s', $field, $this->getOperator($type), $parameterName)
        );
        $queryBuilder->setParameter($parameterName, $value);
    }

    /**
     * @param ProxyQueryInterface $queryBuilder
     * @param string $field
     * @param int $type DateType::TYPE_* or DateRangeType::TYPE_*
     * @param \DateTime|int $start
     * @param \DateTime|int $end
     */
    protected function addRangeCondition(ProxyQueryInterface $queryBuilder, $field, $type, $start, $end)
    {
        /** @var ProxyQueryInterface|\Doctrine\ORM\QueryBuilder $queryBuilder */
        $startDateParameterName = $this->getNewParameterName($queryBuilder);
        $endDateParameterName = $this->getNewParameterName($queryBuilder);

        if ($type == DateRangeType::TYPE_NOT_BETWEEN) {
            $this->applyWhere(
                $queryBuilder,
                sprintf(
                    '(%s < :%s OR %s > :%s)',
                    $field,
                    $startDateParameterName,
                    $field,
                    $endDateParameterName
                )
            );
        } else {
            $this->applyWhere(
                $queryBuilder,
                sprintf('%s %s :%s', $field, '>=', $startDateParameterName)
            );
            $this->applyWhere(
                $queryBuilder,
                sprintf('%s %s :%s', $field, '<=', $endDateParameterName)
            );
        }
        $queryBuilder->setParameter($startDateParameterName,  $start);
        $queryBuilder->setParameter($endDateParameterName,  $end);
    }

    /**
     * Resolves DataType:: constants to SQL operators
     *
     * @param int $type DateType::TYPE_* or DateRangeType::TYPE_*
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
     * {@inheritdoc}
     */
    public function getDefaultOptions()
    {
        return array(
            'input_type' => 'datetime'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getRenderSettings()
    {
        return array(
            $this->getRenderName(),
            array(
                'field_type'    => $this->getFieldType(),
                'field_options' => $this->getFieldOptions(),
                'label'         => $this->getLabel(),
            )
        );
    }

    public function getFieldType()
    {
        return $this->getOption('field_type', 'datetime');
    }

    /**
     * @return null|string
     */
    protected abstract function getRenderName();
}
