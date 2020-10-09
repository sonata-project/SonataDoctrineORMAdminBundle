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
use Doctrine\ORM\QueryBuilder;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Form\Type\Filter\DefaultType;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Sonata\AdminBundle\Form\Type\Operator\EqualOperatorType;

/**
 * @final since sonata-project/doctrine-orm-admin-bundle 3.24
 */
class ModelAutocompleteFilter extends Filter
{
    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $value)
    {
        if (!$value || !\is_array($value) || !\array_key_exists('value', $value)) {
            return;
        }

        if ($value['value'] instanceof Collection) {
            $value['value'] = $value['value']->toArray();
        }

        if (\is_array($value['value'])) {
            $this->handleMultiple($queryBuilder, $alias, $value);
        } else {
            $this->handleModel($queryBuilder, $alias, $value);
        }
    }

    public function getDefaultOptions()
    {
        return [
            'field_name' => false,
            'field_type' => ModelAutocompleteType::class,
            'field_options' => [],
            'operator_type' => EqualOperatorType::class,
            'operator_options' => [],
        ];
    }

    public function getRenderSettings()
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
     * @param ProxyQueryInterface|QueryBuilder $queryBuilder
     * @param string                           $alias
     * @param mixed[]                          $value
     *
     * @return void
     */
    protected function handleMultiple(ProxyQueryInterface $queryBuilder, $alias, $value)
    {
        if (0 === \count($value['value'])) {
            return;
        }

        $parameterName = $this->getNewParameterName($queryBuilder);

        if (isset($value['type']) && EqualOperatorType::TYPE_NOT_EQUAL === $value['type']) {
            $this->applyWhere($queryBuilder, $queryBuilder->expr()->notIn($alias, ':'.$parameterName));
        } else {
            $this->applyWhere($queryBuilder, $queryBuilder->expr()->in($alias, ':'.$parameterName));
        }

        $queryBuilder->setParameter($parameterName, $value['value']);
    }

    /**
     * @param ProxyQueryInterface|QueryBuilder $queryBuilder
     * @param string                           $alias
     * @param mixed[]                          $value
     *
     * @return void
     */
    protected function handleModel(ProxyQueryInterface $queryBuilder, $alias, $value)
    {
        if (empty($value['value'])) {
            return;
        }

        $parameterName = $this->getNewParameterName($queryBuilder);

        if (isset($value['type']) && EqualOperatorType::TYPE_NOT_EQUAL === $value['type']) {
            $this->applyWhere($queryBuilder, sprintf('%s != :%s', $alias, $parameterName));
        } else {
            $this->applyWhere($queryBuilder, sprintf('%s = :%s', $alias, $parameterName));
        }

        $queryBuilder->setParameter($parameterName, $value['value']);
    }

    /**
     * @param mixed[] $value
     *
     * @return array
     *
     * @phpstan-return array{string, bool}
     */
    protected function association(ProxyQueryInterface $queryBuilder, $value)
    {
        $associationMappings = $this->getParentAssociationMappings();
        $associationMappings[] = $this->getAssociationMapping();
        $alias = $queryBuilder->entityJoin($associationMappings);

        return [$alias, false];
    }
}
