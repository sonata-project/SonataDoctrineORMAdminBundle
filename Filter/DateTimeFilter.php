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

class DateTimeFilter extends AbstractDateFilter
{
    /**
     * This filter has time
     *
     * @var boolean
     */
    protected $time = true;

    /**
     * This is not a rangle filter
     *
     * @var boolean
     */
    protected $range = false;
}
