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

use Sonata\CoreBundle\Form\Type\DateRangeType;

class DateRangeFilter extends AbstractDateFilter
{
    /**
     * This is a range filter.
     *
     * @var bool
     */
    protected $range = true;

    /**
     * This filter has time.
     *
     * @var bool
     */
    protected $time = false;

    public function getFieldType()
    {
        return $this->getOption('field_type', DateRangeType::class);
    }
}
