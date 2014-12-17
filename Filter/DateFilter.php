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

class DateFilter extends AbstractDateFilter
{
    /**
     * {@inheritdoc}
     */
    public function getFieldType()
    {
        return $this->getOption('field_type', 'date');
    }

    /**
     * {@inheritdoc}
     */
    protected function getRenderName()
    {
        return 'sonata_type_filter_date';
    }

    /**
     * {@inheritdoc}
     */
    protected function isRangedCondition($value, $filterType)
    {
        return
            !empty($this->options['field_mapping']['type']) &&
            in_array($this->options['field_mapping']['type'], array('datetime' , 'datetimetz'), true)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function normalizeValue($value, $ranged, $filterType)
    {
        if ($ranged && !is_array($value)) {
            /** @var \DateTime $start */
            $start = $value->setTime(0, 0, 0);

            $end = clone $start;
            $end->setTime(23, 59, 59);
            return array($start, $end);
        }
        return parent::normalizeValue($value, $ranged, $filterType);
    }
}
