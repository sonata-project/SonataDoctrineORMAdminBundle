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

use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Form\Type\Filter\DefaultType;
use Sonata\AdminBundle\Form\Type\Operator\EqualOperatorType;

class ChoiceFilter extends Filter
{
    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $data)
    {
        if (!$data || !\is_array($data) || !\array_key_exists('type', $data) || !\array_key_exists('value', $data)) {
            return;
        }

        if (\is_array($data['value'])) {
            $this->filterWithMultipleValues($queryBuilder, $alias, $field, $data);
        } else {
            $this->filterWithSingleValue($queryBuilder, $alias, $field, $data);
        }
    }

    public function getDefaultOptions()
    {
        return [
            'operator_type' => EqualOperatorType::class,
            'operator_options' => [],
        ];
    }

    public function getRenderSettings()
    {
        return [DefaultType::class, [
            'operator_type' => $this->getOption('operator_type'),
            'operator_options' => $this->getOption('operator_options'),
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label' => $this->getLabel(),
        ]];
    }

    private function filterWithMultipleValues(ProxyQueryInterface $queryBuilder, string $alias, string $field, array $data = []): void
    {
        if (0 === \count($data['value'])) {
            return;
        }

        if (\in_array('all', $data['value'], true)) {
            return;
        }

        $isNullSelected = \in_array(null, $data['value'], true);
        $data['value'] = array_filter($data['value'], static function ($value): bool {
            return null !== $value;
        });

        // Have to pass IN array value as parameter. See: http://www.doctrine-project.org/jira/browse/DDC-3759
        $completeField = sprintf('%s.%s', $alias, $field);
        $parameterName = $this->getNewParameterName($queryBuilder);
        if (EqualOperatorType::TYPE_NOT_EQUAL === $data['type']) {
            $andConditions = [$queryBuilder->expr()->isNotNull($completeField)];
            if (0 !== \count($data['value'])) {
                $andConditions[] = $queryBuilder->expr()->notIn($completeField, ':'.$parameterName);
                $queryBuilder->setParameter($parameterName, $data['value']);
            }
            $this->applyWhere($queryBuilder, $queryBuilder->expr()->andX()->addMultiple($andConditions));
        } else {
            $orConditions = [$queryBuilder->expr()->in($completeField, ':'.$parameterName)];
            if ($isNullSelected) {
                $orConditions[] = $queryBuilder->expr()->isNull($completeField);
            }
            $this->applyWhere($queryBuilder, $queryBuilder->expr()->orX()->addMultiple($orConditions));
            $queryBuilder->setParameter($parameterName, $data['value']);
        }
    }

    private function filterWithSingleValue(ProxyQueryInterface $queryBuilder, string $alias, string $field, array $data = []): void
    {
        if ('' === $data['value'] || false === $data['value'] || 'all' === $data['value']) {
            return;
        }

        $parameterName = $this->getNewParameterName($queryBuilder);

        if (EqualOperatorType::TYPE_NOT_EQUAL === $data['type']) {
            if (null === $data['value']) {
                $this->applyWhere($queryBuilder, $queryBuilder->expr()->isNotNull(sprintf('%s.%s', $alias, $field)));
            } else {
                $this->applyWhere($queryBuilder, sprintf('%s.%s != :%s', $alias, $field, $parameterName));
                $queryBuilder->setParameter($parameterName, $data['value']);
            }
        } else {
            if (null === $data['value']) {
                $this->applyWhere($queryBuilder, $queryBuilder->expr()->isNull(sprintf('%s.%s', $alias, $field)));
            } else {
                $this->applyWhere($queryBuilder, sprintf('%s.%s = :%s', $alias, $field, $parameterName));
                $queryBuilder->setParameter($parameterName, $data['value']);
            }
        }
    }
}
