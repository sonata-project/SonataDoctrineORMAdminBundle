<?php

namespace Sonata\DoctrineORMAdminBundle\Filter;

use Sonata\AdminBundle\Form\Type\Filter\DateType;

abstract class AbstractDateRangeFilter extends AbstractDateFilter
{
    /**
     * {@inheritdoc}
     */
    public function getFieldType()
    {
        return $this->getOption('field_type', 'sonata_type_datetime_range');
    }

    /**
     * {@inheritdoc}
     */
    protected function isValidValue($value)
    {
        return
            is_array($value) &&
            array_key_exists('start', $value) &&
            array_key_exists('end', $value) &&
            ($value['start'] instanceof \DateTime || $value['end'] instanceof \DateTime)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function isRangedCondition($value, $filterType)
    {
        return $value['start'] instanceof \DateTime && $value['end'] instanceof \DateTime;
    }

    /**
     * {@inheritdoc}
     */
    protected function normalizeValue($value, $ranged, $filterType)
    {
        if (!is_array($value)) {
            throw new \LogicException('Did you override isRangedCondition and forget normalizeValue?');
        }

        /** @var \DateTime[] $value */
        if (!$value['start']) {
            return parent::normalizeValue($value['end'], false, $filterType);
        }
        if (!$value['end']) {
            return parent::normalizeValue($value['start'], false, $filterType);
        }
        return array(
            parent::normalizeValue($value['start'], false, $filterType),
            parent::normalizeValue($value['end'], false, $filterType)
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function normalizeFilterType($filterType, $value, $ranged)
    {
        if ($ranged) {
            return parent::normalizeFilterType($filterType, $value, $ranged);
        }

        if (!$value['start']) {
            return DateType::TYPE_LESS_EQUAL;
        }
        return DateType::TYPE_GREATER_EQUAL;
    }
}
