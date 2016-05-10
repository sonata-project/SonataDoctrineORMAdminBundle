<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Filter;

class DateTimeRangeFilter extends AbstractDateFilter
{
    /**
     * This Filter allows filtering by time.
     *
     * @var bool
     */
    protected $time = true;

    /**
     * This is a range filter.
     *
     * @var bool
     */
    protected $range = true;

    /**
     * {@inheritdoc}
     */
    public function getFieldType()
    {
        return $this->getOption('field_type', 'sonata_type_datetime_range');
    }
}
