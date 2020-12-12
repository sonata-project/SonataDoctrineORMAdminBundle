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
use Sonata\AdminBundle\Form\Type\Filter\DefaultType;
use Sonata\AdminBundle\Form\Type\Operator\EqualOperatorType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;

/**
 * @final since sonata-project/doctrine-orm-admin-bundle 3.24
 */
class ChoiceFilter extends Filter
{
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

        if (!$data || !\is_array($data) || !\array_key_exists('type', $data) || !\array_key_exists('value', $data)) {
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
     * NEXT_MAJOR: Change the typehint to ProxyQueryInterface.
     */
    private function filterWithMultipleValues(BaseProxyQueryInterface $query, string $alias, string $field, array $data = []): void
    {
        if (0 === \count($data['value'])) {
            return;
        }

        $isNullSelected = \in_array(null, $data['value'], true);
        $data['value'] = array_filter($data['value'], static function ($data): bool {
            return null !== $data;
        });

        // Have to pass IN array value as parameter. See: http://www.doctrine-project.org/jira/browse/DDC-3759
        $completeField = sprintf('%s.%s', $alias, $field);
        $parameterName = $this->getNewParameterName($query);
        if (EqualOperatorType::TYPE_NOT_EQUAL === $data['type']) {
            $andConditions = [$query->getQueryBuilder()->expr()->isNotNull($completeField)];
            if (0 !== \count($data['value'])) {
                $andConditions[] = $query->getQueryBuilder()->expr()->notIn($completeField, ':'.$parameterName);
                $query->getQueryBuilder()->setParameter($parameterName, $data['value']);
            }
            $this->applyWhere($query, $query->getQueryBuilder()->expr()->andX()->addMultiple($andConditions));
        } else {
            $orConditions = [$query->getQueryBuilder()->expr()->in($completeField, ':'.$parameterName)];
            if ($isNullSelected) {
                $orConditions[] = $query->getQueryBuilder()->expr()->isNull($completeField);
            }
            $this->applyWhere($query, $query->getQueryBuilder()->expr()->orX()->addMultiple($orConditions));
            $query->getQueryBuilder()->setParameter($parameterName, $data['value']);
        }
    }

    /**
     * NEXT_MAJOR: Change the typehint to ProxyQueryInterface.
     */
    private function filterWithSingleValue(BaseProxyQueryInterface $query, string $alias, string $field, array $data = []): void
    {
        if ('' === $data['value'] || false === $data['value']) {
            return;
        }

        $parameterName = $this->getNewParameterName($query);

        if (EqualOperatorType::TYPE_NOT_EQUAL === $data['type']) {
            if (null === $data['value']) {
                $this->applyWhere($query, $query->getQueryBuilder()->expr()->isNotNull(sprintf('%s.%s', $alias, $field)));
            } else {
                $this->applyWhere($query, sprintf('%s.%s != :%s', $alias, $field, $parameterName));
                $query->getQueryBuilder()->setParameter($parameterName, $data['value']);
            }
        } else {
            if (null === $data['value']) {
                $this->applyWhere($query, $query->getQueryBuilder()->expr()->isNull(sprintf('%s.%s', $alias, $field)));
            } else {
                $this->applyWhere($query, sprintf('%s.%s = :%s', $alias, $field, $parameterName));
                $query->getQueryBuilder()->setParameter($parameterName, $data['value']);
            }
        }
    }
}
