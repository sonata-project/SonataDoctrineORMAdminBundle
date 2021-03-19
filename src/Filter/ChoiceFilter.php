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

use Sonata\AdminBundle\Form\Type\Filter\DefaultType;
use Sonata\AdminBundle\Form\Type\Operator\EqualOperatorType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;

final class ChoiceFilter extends Filter
{
    public function filter(ProxyQueryInterface $query, string $alias, string $field, array $data): void
    {
        if (!\array_key_exists('type', $data) || !\array_key_exists('value', $data)) {
            return;
        }

        if (\is_array($data['value'])) {
            $this->filterWithMultipleValues($query, $alias, $field, $data);
        } else {
            $this->filterWithSingleValue($query, $alias, $field, $data);
        }
    }

    public function getDefaultOptions(): array
    {
        return [
            'operator_type' => EqualOperatorType::class,
            'operator_options' => [],
        ];
    }

    public function getRenderSettings(): array
    {
        return [DefaultType::class, [
            'operator_type' => $this->getOption('operator_type'),
            'operator_options' => $this->getOption('operator_options'),
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label' => $this->getLabel(),
        ]];
    }

    /**
     * @param mixed[] $data
     *
     * @phpstan-param array{type?: string|int|null, value?: mixed} $data
     */
    private function filterWithMultipleValues(ProxyQueryInterface $query, string $alias, string $field, array $data = []): void
    {
        if (0 === \count($data['value'])) {
            return;
        }

        $isNullSelected = \in_array(null, $data['value'], true);
        $completeField = sprintf('%s.%s', $alias, $field);
        $parameterName = $this->getNewParameterName($query);

        $or = $query->getQueryBuilder()->expr()->orX();
        if (EqualOperatorType::TYPE_NOT_EQUAL === $data['type']) {
            $or->add($query->getQueryBuilder()->expr()->notIn($completeField, ':'.$parameterName));

            if (!$isNullSelected) {
                $or->add($query->getQueryBuilder()->expr()->isNull($completeField));
            }
        } else {
            $or->add($query->getQueryBuilder()->expr()->in($completeField, ':'.$parameterName));

            if ($isNullSelected) {
                $or->add($query->getQueryBuilder()->expr()->isNull($completeField));
            }
        }

        $this->applyWhere($query, $or);
        $query->getQueryBuilder()->setParameter($parameterName, $data['value']);
    }

    /**
     * @param mixed[] $data
     *
     * @phpstan-param array{type?: string|int|null, value?: mixed} $data
     */
    private function filterWithSingleValue(ProxyQueryInterface $query, string $alias, string $field, array $data = []): void
    {
        if ('' === $data['value'] || false === $data['value']) {
            return;
        }

        $parameterName = $this->getNewParameterName($query);
        $completeField = sprintf('%s.%s', $alias, $field);

        if (EqualOperatorType::TYPE_NOT_EQUAL === $data['type']) {
            if (null === $data['value']) {
                $this->applyWhere($query, $query->getQueryBuilder()->expr()->isNotNull($completeField));
            } else {
                $this->applyWhere(
                    $query,
                    sprintf('%s != :%s OR %s IS NULL', $completeField, $parameterName, $completeField)
                );
                $query->getQueryBuilder()->setParameter($parameterName, $data['value']);
            }
        } else {
            if (null === $data['value']) {
                $this->applyWhere($query, $query->getQueryBuilder()->expr()->isNull($completeField));
            } else {
                $this->applyWhere($query, sprintf('%s = :%s', $completeField, $parameterName));
                $query->getQueryBuilder()->setParameter($parameterName, $data['value']);
            }
        }
    }
}
