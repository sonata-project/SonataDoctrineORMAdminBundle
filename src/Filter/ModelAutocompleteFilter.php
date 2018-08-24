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

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Form\Type\Filter\DefaultType;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Sonata\CoreBundle\Form\Type\EqualType;

class ModelAutocompleteFilter extends Filter
{
    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $data)
    {
        if (!$data || !\is_array($data) || !array_key_exists('value', $data)) {
            return;
        }

        if ($data['value'] instanceof Collection) {
            $data['value'] = $data['value']->toArray();
        }

        if (\is_array($data['value'])) {
            $this->handleMultiple($queryBuilder, $alias, $data);
        } else {
            $this->handleModel($queryBuilder, $alias, $data);
        }
    }

    public function getDefaultOptions()
    {
        return [
            'field_name' => false,
            'field_type' => ModelAutocompleteType::class,
            'field_options' => [],
            'operator_type' => EqualType::class,
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
     * @param mixed                            $data
     *
     * @return mixed
     */
    protected function handleMultiple(ProxyQueryInterface $queryBuilder, $alias, $data)
    {
        if (0 == \count($data['value'])) {
            return;
        }

        $parameterName = $this->getNewParameterName($queryBuilder);

        if (isset($data['type']) && EqualType::TYPE_IS_NOT_EQUAL == $data['type']) {
            $this->applyWhere($queryBuilder, $queryBuilder->expr()->notIn($alias, ':'.$parameterName));
        } else {
            $this->applyWhere($queryBuilder, $queryBuilder->expr()->in($alias, ':'.$parameterName));
        }

        $queryBuilder->setParameter($parameterName, $data['value']);
    }

    /**
     * @param ProxyQueryInterface|QueryBuilder $queryBuilder
     * @param string                           $alias
     * @param mixed                            $data
     *
     * @return mixed
     */
    protected function handleModel(ProxyQueryInterface $queryBuilder, $alias, $data)
    {
        if (empty($data['value'])) {
            return;
        }

        $parameterName = $this->getNewParameterName($queryBuilder);

        if (isset($data['type']) && EqualType::TYPE_IS_NOT_EQUAL == $data['type']) {
            $this->applyWhere($queryBuilder, sprintf('%s != :%s', $alias, $parameterName));
        } else {
            $this->applyWhere($queryBuilder, sprintf('%s = :%s', $alias, $parameterName));
        }

        $queryBuilder->setParameter($parameterName, $data['value']);
    }

    protected function association(ProxyQueryInterface $queryBuilder, $data)
    {
        $associationMappings = $this->getParentAssociationMappings();
        $associationMappings[] = $this->getAssociationMapping();
        $alias = $queryBuilder->entityJoin($associationMappings);

        return [$alias, false];
    }
}
