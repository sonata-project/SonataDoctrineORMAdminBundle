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

class DateRangeFilter extends AbstractDateRangeFilter
{
    /**
     * {@inheritdoc}
     */
    protected function getRenderName()
    {
        return 'sonata_type_filter_date_range';
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldType()
    {
        return $this->getOption('field_type', 'sonata_type_date_range');
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
        if ($value['start']) {
            $value['start'] = clone $value['start'];
            $value['start']->setTime(0, 0, 0);
        }
        if ($value['end']) {
            $value['end'] = clone $value['end'];
            $value['end']->setTime(23, 59, 59);
        }

        return parent::normalizeValue($value, $ranged, $filterType);
    }
}
