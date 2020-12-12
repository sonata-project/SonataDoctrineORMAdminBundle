<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Filter;

use Sonata\AdminBundle\Datagrid\ProxyQueryInterface as BaseProxyQueryInterface;
use Sonata\AdminBundle\Form\Type\Filter\DateRangeType;
use Sonata\AdminBundle\Form\Type\Filter\DateTimeRangeType;
use Sonata\AdminBundle\Form\Type\Filter\DateTimeType;
use Sonata\AdminBundle\Form\Type\Filter\DateType;
use Sonata\AdminBundle\Form\Type\Operator\DateOperatorType;
use Sonata\AdminBundle\Form\Type\Operator\DateRangeOperatorType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;

abstract class AbstractDateFilter extends Filter
{
    public const CHOICES = [
        DateOperatorType::TYPE_EQUAL => '=',
        DateOperatorType::TYPE_GREATER_EQUAL => '>=',
        DateOperatorType::TYPE_GREATER_THAN => '>',
        DateOperatorType::TYPE_LESS_EQUAL => '<=',
        DateOperatorType::TYPE_LESS_THAN => '<',
        DateOperatorType::TYPE_NULL => 'NULL',
        DateOperatorType::TYPE_NOT_NULL => 'NOT NULL',
    ];

    /**
     * Flag indicating that filter will have range.
     *
     * @var bool
     */
    protected $range = false;

    /**
     * Flag indicating that filter will filter by datetime instead by date.
     *
     * @var bool
     */
    protected $time = false;

    public function filter(BaseProxyQueryInterface $query, $alias, $field, $data): void
    {
        /* NEXT_MAJOR: Remove this deprecation and update the typehint */
        if (!$query instanceof ProxyQueryInterface) {
            @trigger_error(sprintf(
                'Passing %s as argument 1 to %s() is deprecated since sonata-project/doctrine-orm-admin-bundle 3.x'
                .' and will throw a \TypeError error in version 4.0. You MUST pass an instance of %s instead.',
                \get_class($query),
                __METHOD__,
                ProxyQueryInterface::class
            ));
        }

        // check data sanity
        if (!$data || !\is_array($data) || !\array_key_exists('value', $data)) {
            return;
        }

        if ($this->range) {
            // additional data check for ranged items
            if (!\array_key_exists('start', $data['value']) || !\array_key_exists('end', $data['value'])) {
                return;
            }

            if (!$data['value']['start'] && !$data['value']['end']) {
                return;
            }

            // date filter should filter records for the whole days
            if (false === $this->time && ($data['value']['end'] instanceof \DateTime || $data['value']['end'] instanceof \DateTimeImmutable)) {
                // since the received `\DateTime` object  uses the model timezone to represent
                // the value submitted by the view (which can use a different timezone) and this
                // value is intended to contain a time in the begining of a date (IE, if the model
                // object is configured to use UTC timezone, the view object "2020-11-07 00:00:00.0-03:00"
                // is transformed to "2020-11-07 03:00:00.0+00:00" in the model object), we increment
                // the time part by adding "23:59:59" in order to cover the whole end date and get proper
                // results from queries like "o.created_at <= :date_end".
                $data['value']['end'] = $data['value']['end']->modify('+23 hours 59 minutes 59 seconds');
            }

            // transform types
            if ('timestamp' === $this->getOption('input_type')) {
                $data['value']['start'] = $data['value']['start'] instanceof \DateTimeInterface ? $data['value']['start']->getTimestamp() : 0;
                $data['value']['end'] = $data['value']['end'] instanceof \DateTimeInterface ? $data['value']['end']->getTimestamp() : 0;
            }

            // default type for range filter
            $data['type'] = !isset($data['type']) || !is_numeric($data['type']) ? DateRangeOperatorType::TYPE_BETWEEN : $data['type'];

            $startDateParameterName = $this->getNewParameterName($query);
            $endDateParameterName = $this->getNewParameterName($query);

            if (DateRangeOperatorType::TYPE_NOT_BETWEEN === $data['type']) {
                $this->applyWhere($query, sprintf('%s.%s < :%s OR %s.%s > :%s', $alias, $field, $startDateParameterName, $alias, $field, $endDateParameterName));
            } else {
                if ($data['value']['start']) {
                    $this->applyWhere($query, sprintf('%s.%s %s :%s', $alias, $field, '>=', $startDateParameterName));
                }

                if ($data['value']['end']) {
                    $this->applyWhere($query, sprintf('%s.%s %s :%s', $alias, $field, '<=', $endDateParameterName));
                }
            }

            if ($data['value']['start']) {
                $query->getQueryBuilder()->setParameter($startDateParameterName, $data['value']['start']);
            }

            if ($data['value']['end']) {
                $query->getQueryBuilder()->setParameter($endDateParameterName, $data['value']['end']);
            }
        } else {
            if (!$data['value']) {
                return;
            }

            // default type for simple filter
            $data['type'] = !isset($data['type']) || !is_numeric($data['type']) ? DateOperatorType::TYPE_EQUAL : $data['type'];

            // just find an operator and apply query
            $operator = $this->getOperator($data['type']);

            // transform types
            if ('timestamp' === $this->getOption('input_type')) {
                $data['value'] = $data['value'] instanceof \DateTimeInterface ? $data['value']->getTimestamp() : 0;
            }

            // null / not null only check for col
            if (\in_array($operator, ['NULL', 'NOT NULL'], true)) {
                $this->applyWhere($query, sprintf('%s.%s IS %s ', $alias, $field, $operator));

                return;
            }

            $parameterName = $this->getNewParameterName($query);

            // date filter should filter records for the whole day
            if (false === $this->time && DateOperatorType::TYPE_EQUAL === $data['type']) {
                $this->applyWhere($query, sprintf('%s.%s %s :%s', $alias, $field, '>=', $parameterName));
                $query->getQueryBuilder()->setParameter($parameterName, $data['value']);

                $endDateParameterName = $this->getNewParameterName($query);
                $this->applyWhere($query, sprintf('%s.%s %s :%s', $alias, $field, '<', $endDateParameterName));
                if ('timestamp' === $this->getOption('input_type')) {
                    $endValue = strtotime('+1 day', $data['value']);
                } else {
                    $endValue = clone $data['value'];
                    $endValue->add(new \DateInterval('P1D'));
                }
                $query->getQueryBuilder()->setParameter($endDateParameterName, $endValue);

                return;
            }

            $this->applyWhere($query, sprintf('%s.%s %s :%s', $alias, $field, $operator, $parameterName));
            $query->getQueryBuilder()->setParameter($parameterName, $data['value']);
        }
    }

    public function getDefaultOptions(): array
    {
        return [
            'input_type' => 'datetime',
        ];
    }

    public function getRenderSettings(): array
    {
        $name = DateType::class;

        if ($this->time && $this->range) {
            $name = DateTimeRangeType::class;
        } elseif ($this->time) {
            $name = DateTimeType::class;
        } elseif ($this->range) {
            $name = DateRangeType::class;
        }

        return [$name, [
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label' => $this->getLabel(),
        ]];
    }

    /**
     * NEXT_MAJOR: Change the visibility for private.
     *
     * Resolves DateOperatorType:: constants to SQL operators.
     *
     * @param int $type
     *
     * @return string
     */
    protected function getOperator($type)
    {
        $type = (int) $type;

        return self::CHOICES[$type] ?? self::CHOICES[DateOperatorType::TYPE_EQUAL];
    }
}
