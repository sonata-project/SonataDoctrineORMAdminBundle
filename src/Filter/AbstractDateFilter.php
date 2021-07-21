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

use Doctrine\DBAL\Types\Types;
use Sonata\AdminBundle\Filter\Model\FilterData;
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

    final public function filter(ProxyQueryInterface $query, string $alias, string $field, FilterData $data): void
    {
        // check data sanity
        if (!$data->hasValue()) {
            return;
        }

        if ($this->range) {
            $this->filterRange($query, $alias, $field, $data);

            return;
        }

        $value = $data->getValue();
        if (!$value instanceof \DateTimeInterface) {
            return;
        }

        // default type for simple filter
        $type = $data->getType() ?? DateOperatorType::TYPE_EQUAL;

        // date filter should filter records for the whole day
        if (false === $this->time && DateOperatorType::TYPE_EQUAL === $type) {
            // the value comparison will be made with the '>=' operator
            $type = DateOperatorType::TYPE_GREATER_EQUAL;

            $endDateParameterName = $this->getNewParameterName($query);
            $this->applyWhere($query, sprintf('%s.%s %s :%s', $alias, $field, '<', $endDateParameterName));

            if ('timestamp' === $this->getOption('input_type')) {
                $endValue = strtotime('+1 day', $value->getTimestamp());
            } elseif ($value instanceof \DateTime) {
                $endValue = clone $value;
                $endValue->add(new \DateInterval('P1D'));
            } else {
                /** @var \DateTimeImmutable $value */
                $endValue = $value->add(new \DateInterval('P1D'));
            }

            $query->getQueryBuilder()->setParameter($endDateParameterName, $endValue, $this->getParameterType($endValue));
        }

        // just find an operator and apply query
        $operator = $this->getOperator($type);

        // transform types
        $value = 'timestamp' === $this->getOption('input_type') ? $value->getTimestamp() : $value;

        $parameterName = $this->getNewParameterName($query);
        $this->applyWhere($query, sprintf('%s.%s %s :%s', $alias, $field, $operator, $parameterName));
        $query->getQueryBuilder()->setParameter($parameterName, $value, $this->getParameterType($value));
    }

    final public function getRenderSettings(): array
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

    private function filterRange(ProxyQueryInterface $query, string $alias, string $field, FilterData $data): void
    {
        $value = $data->getValue();

        // additional data check for ranged items
        if (
            !\is_array($value)
            || !\array_key_exists('start', $value)
            || !\array_key_exists('end', $value)
        ) {
            return;
        }

        if (
            !$value['start'] instanceof \DateTimeInterface
            && !$value['end'] instanceof \DateTimeInterface
        ) {
            return;
        }

        // date filter should filter records for the whole days
        if (
            false === $this->time
            && ($value['end'] instanceof \DateTime || $value['end'] instanceof \DateTimeImmutable)
        ) {
            // since the received `\DateTime` object  uses the model timezone to represent
            // the value submitted by the view (which can use a different timezone) and this
            // value is intended to contain a time in the begining of a date (IE, if the model
            // object is configured to use UTC timezone, the view object "2020-11-07 00:00:00.0-03:00"
            // is transformed to "2020-11-07 03:00:00.0+00:00" in the model object), we increment
            // the time part by adding "23:59:59" in order to cover the whole end date and get proper
            // results from queries like "o.created_at <= :date_end".
            $value['end'] = $value['end']->modify('+23 hours 59 minutes 59 seconds');
        }

        // transform types
        if ('timestamp' === $this->getOption('input_type')) {
            $value['start'] = $value['start'] instanceof \DateTimeInterface ? $value['start']->getTimestamp() : 0;
            $value['end'] = $value['end'] instanceof \DateTimeInterface ? $value['end']->getTimestamp() : 0;
        }

        // default type for range filter
        $type = $data->getType() ?? DateRangeOperatorType::TYPE_BETWEEN;

        $startDateParameterName = $this->getNewParameterName($query);
        $endDateParameterName = $this->getNewParameterName($query);

        if (DateRangeOperatorType::TYPE_NOT_BETWEEN === $type) {
            $this->applyWhere($query, sprintf('%s.%s < :%s OR %s.%s > :%s', $alias, $field, $startDateParameterName, $alias, $field, $endDateParameterName));
        } else {
            if ($value['start']) {
                $this->applyWhere($query, sprintf('%s.%s %s :%s', $alias, $field, '>=', $startDateParameterName));
            }

            if ($value['end']) {
                $this->applyWhere($query, sprintf('%s.%s %s :%s', $alias, $field, '<=', $endDateParameterName));
            }
        }

        if ($value['start']) {
            $query->getQueryBuilder()->setParameter(
                $startDateParameterName,
                $value['start'],
                $this->getParameterType($value['start'])
            );
        }

        if ($value['end']) {
            $query->getQueryBuilder()->setParameter(
                $endDateParameterName,
                $value['end'],
                $this->getParameterType($value['end'])
            );
        }
    }

    /**
     * @param \DateTimeInterface|int $parameter
     */
    private function getParameterType($parameter): string
    {
        if ($parameter instanceof \DateTime) {
            return Types::DATETIME_MUTABLE;
        }
        if ($parameter instanceof \DateTimeImmutable) {
            return Types::DATETIME_IMMUTABLE;
        }

        return Types::INTEGER;
    }

    private function getOperator(int $type): string
    {
        if (!isset(self::CHOICES[$type])) {
            throw new \OutOfRangeException(sprintf(
                'The type "%s" is not supported, allowed one are "%s".',
                $type,
                implode('", "', array_keys(self::CHOICES))
            ));
        }

        return self::CHOICES[$type];
    }
}
