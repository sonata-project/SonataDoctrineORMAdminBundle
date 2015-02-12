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

class DateTimeRangeFilter extends AbstractDateRangeFilter
{
    /**
     * {@inheritdoc}
     */
    protected function getRenderName()
    {
        return 'sonata_type_filter_datetime_range';
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldType()
    {
        return $this->getOption('field_type', 'sonata_type_datetime_range');
    }
}
