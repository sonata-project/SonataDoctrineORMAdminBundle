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

class DateFilter extends AbstractDateFilter
{
    /**
     * This filter has no range.
     *
     * @var bool
     */
    protected $range = false;

    /**
     * This filter does not allow filtering by time.
     *
     * @var bool
     */
    protected $time = false;

    /**
     * {@inheritdoc}
     */
    public function getFieldType()
    {
        // NEXT_MAJOR: Remove ternary (when requirement of Symfony is >= 2.8)
        return $this->getOption('field_type', method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')
            ? 'Symfony\Component\Form\Extension\Core\Type\DateType'
            : 'date'
        );
    }
}
