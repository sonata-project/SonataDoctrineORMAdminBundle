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

use Doctrine\Common\Collections\Collection;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\Type\Filter\DefaultType;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Sonata\AdminBundle\Form\Type\Operator\EqualOperatorType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;

final class ModelAutocompleteFilter extends Filter
{
    public function filter(ProxyQueryInterface $query, string $alias, string $field, FilterData $data): void
    {
        if (!$data->hasValue()) {
            return;
        }

        $value = $data->getValue();

        if ($value instanceof Collection) {
            $data = $data->changeValue($value->toArray());
        }

        if (\is_array($data->getValue())) {
            $this->handleMultiple($query, $alias, $data);
        } else {
            $this->handleModel($query, $alias, $data);
        }
    }

    public function getDefaultOptions(): array
    {
        return [
            'field_name' => false,
            'field_type' => ModelAutocompleteType::class,
            'field_options' => [],
            'operator_type' => EqualOperatorType::class,
            'operator_options' => [],
        ];
    }

    public function getRenderSettings(): array
    {
        return [DefaultType::class, [
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'operator_type' => $this->getOption('operator_type'),
            'operator_options' => $this->getOption('operator_options'),
            'label' => $this->getLabel(),
        ]];
    }

    /**
     * For the record, the $alias value is provided by the association method (and the entity join method)
     *  so the field value is not used here.
     */
    protected function handleMultiple(ProxyQueryInterface $query, string $alias, FilterData $data): void
    {
        if (0 === \count($data->getValue())) {
            return;
        }

        $parameterName = $this->getNewParameterName($query);

        if ($data->isType(EqualOperatorType::TYPE_NOT_EQUAL)) {
            $this->applyWhere($query, $query->getQueryBuilder()->expr()->notIn($alias, ':'.$parameterName));
        } else {
            $this->applyWhere($query, $query->getQueryBuilder()->expr()->in($alias, ':'.$parameterName));
        }

        $query->getQueryBuilder()->setParameter($parameterName, $data->getValue());
    }

    protected function handleModel(ProxyQueryInterface $query, string $alias, FilterData $data): void
    {
        $parameterName = $this->getNewParameterName($query);

        if ($data->isType(EqualOperatorType::TYPE_NOT_EQUAL)) {
            $this->applyWhere($query, sprintf('%s != :%s', $alias, $parameterName));
        } else {
            $this->applyWhere($query, sprintf('%s = :%s', $alias, $parameterName));
        }

        $query->getQueryBuilder()->setParameter($parameterName, $data->getValue());
    }

    protected function association(ProxyQueryInterface $query, FilterData $data): array
    {
        $associationMappings = $this->getParentAssociationMappings();
        $associationMappings[] = $this->getAssociationMapping();
        $alias = $query->entityJoin($associationMappings);

        return [$alias, ''];
    }
}
