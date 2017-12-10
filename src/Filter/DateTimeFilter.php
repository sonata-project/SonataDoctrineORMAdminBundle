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

use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class DateTimeFilter extends AbstractDateFilter
{
    /**
     * This filter has time.
     *
     * @var bool
     */
    protected $time = true;

    /**
     * This is not a rangle filter.
     *
     * @var bool
     */
    protected $range = false;

    public function getFieldType()
    {
        return $this->getOption('field_type', DateTimeType::class);
    }
}
