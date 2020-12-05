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
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface as BaseProxyQueryInterface;
use Sonata\AdminBundle\Form\Type\Filter\DefaultType;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Sonata\AdminBundle\Form\Type\Operator\EqualOperatorType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;

/**
 * @final since sonata-project/doctrine-orm-admin-bundle 3.24
 */
class ModelAutocompleteFilter extends Filter
{
    public function filter(BaseProxyQueryInterface $query, $alias, $field, $value)
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

        if (!$value || !\is_array($value) || !\array_key_exists('value', $value)) {
            return;
        }

        if ($value['value'] instanceof Collection) {
            $value['value'] = $value['value']->toArray();
        }

        if (\is_array($value['value'])) {
            $this->handleMultiple($query, $alias, $value);
        } else {
            $this->handleModel($query, $alias, $value);
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
     * @param string  $alias
     * @param mixed[] $value
     *
     * @return void
     */
    protected function handleMultiple(BaseProxyQueryInterface $query, $alias, $value)
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

        if (0 === \count($value['value'])) {
            return;
        }

        $parameterName = $this->getNewParameterName($query);

        if (isset($value['type']) && EqualOperatorType::TYPE_NOT_EQUAL === $value['type']) {
            $this->applyWhere($query, $query->getQueryBuilder()->expr()->notIn($alias, ':'.$parameterName));
        } else {
            $this->applyWhere($query, $query->getQueryBuilder()->expr()->in($alias, ':'.$parameterName));
        }

        $query->getQueryBuilder()->setParameter($parameterName, $value['value']);
    }

    /**
     * @param string  $alias
     * @param mixed[] $value
     *
     * @return void
     */
    protected function handleModel(BaseProxyQueryInterface $query, $alias, $value)
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

        if (empty($value['value'])) {
            return;
        }

        $parameterName = $this->getNewParameterName($query);

        if (isset($value['type']) && EqualOperatorType::TYPE_NOT_EQUAL === $value['type']) {
            $this->applyWhere($query, sprintf('%s != :%s', $alias, $parameterName));
        } else {
            $this->applyWhere($query, sprintf('%s = :%s', $alias, $parameterName));
        }

        $query->getQueryBuilder()->setParameter($parameterName, $value['value']);
    }

    /**
     * @param mixed[] $value
     *
     * @return array
     *
     * @phpstan-return array{string, bool}
     */
    protected function association(BaseProxyQueryInterface $query, $value)
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

        $associationMappings = $this->getParentAssociationMappings();
        $associationMappings[] = $this->getAssociationMapping();
        $alias = $query->entityJoin($associationMappings);

        return [$alias, false];
    }
}
