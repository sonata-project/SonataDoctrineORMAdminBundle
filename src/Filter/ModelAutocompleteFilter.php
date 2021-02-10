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
use Sonata\AdminBundle\Form\Type\Filter\DefaultType;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Sonata\AdminBundle\Form\Type\Operator\EqualOperatorType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;

final class ModelAutocompleteFilter extends Filter
{
    public function filter(ProxyQueryInterface $query, string $alias, string $field, array $data): void
    {
        if (!\array_key_exists('value', $data)) {
            return;
        }

        if ($data['value'] instanceof Collection) {
            $data['value'] = $data['value']->toArray();
        }

        if (\is_array($data['value'])) {
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
     *
     * @param mixed[] $data
     */
    protected function handleMultiple(ProxyQueryInterface $query, string $alias, array $data): void
    {
        if (0 === \count($data['value'])) {
            return;
        }

        $parameterName = $this->getNewParameterName($query);

        if (isset($data['type']) && EqualOperatorType::TYPE_NOT_EQUAL === $data['type']) {
            $this->applyWhere($query, $query->getQueryBuilder()->expr()->notIn($alias, ':'.$parameterName));
        } else {
            $this->applyWhere($query, $query->getQueryBuilder()->expr()->in($alias, ':'.$parameterName));
        }

        $query->getQueryBuilder()->setParameter($parameterName, $data['value']);
    }

    /**
     * @param mixed[] $data
     */
    protected function handleModel(ProxyQueryInterface $query, string $alias, array $data): void
    {
        if (empty($data['value'])) {
            return;
        }

        $parameterName = $this->getNewParameterName($query);

        if (isset($data['type']) && EqualOperatorType::TYPE_NOT_EQUAL === $data['type']) {
            $this->applyWhere($query, sprintf('%s != :%s', $alias, $parameterName));
        } else {
            $this->applyWhere($query, sprintf('%s = :%s', $alias, $parameterName));
        }

        $query->getQueryBuilder()->setParameter($parameterName, $data['value']);
    }

    protected function association(ProxyQueryInterface $query, array $data): array
    {
        $associationMappings = $this->getParentAssociationMappings();
        $associationMappings[] = $this->getAssociationMapping();
        $alias = $query->entityJoin($associationMappings);

        return [$alias, ''];
    }
}
