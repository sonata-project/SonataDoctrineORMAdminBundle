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

use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Form\Type\Filter\ChoiceType;
use Sonata\AdminBundle\Form\Type\Filter\DefaultType;
use Sonata\CoreBundle\Form\Type\EqualType;

class ChoiceFilter extends Filter
{
    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $data)
    {
        if (!$data || !is_array($data) || !array_key_exists('type', $data) || !array_key_exists('value', $data)) {
            return;
        }

        if (is_array($data['value'])) {
            if (0 == count($data['value'])) {
                return;
            }

            if (in_array('all', $data['value'], true)) {
                return;
            }

            // Have to pass IN array value as parameter. See: http://www.doctrine-project.org/jira/browse/DDC-3759
            $completeField = sprintf('%s.%s', $alias, $field);
            $parameterName = $this->getNewParameterName($queryBuilder);
            if (ChoiceType::TYPE_NOT_CONTAINS == $data['type']) {
                $this->applyWhere($queryBuilder, $queryBuilder->expr()->notIn($completeField, ':'.$parameterName));
            } else {
                $this->applyWhere($queryBuilder, $queryBuilder->expr()->in($completeField, ':'.$parameterName));
            }
            $queryBuilder->setParameter($parameterName, $data['value']);
        } else {
            if ('' === $data['value'] || null === $data['value'] || false === $data['value'] || 'all' === $data['value']) {
                return;
            }

            $parameterName = $this->getNewParameterName($queryBuilder);

            if (ChoiceType::TYPE_NOT_CONTAINS == $data['type']) {
                $this->applyWhere($queryBuilder, sprintf('%s.%s <> :%s', $alias, $field, $parameterName));
            } else {
                $this->applyWhere($queryBuilder, sprintf('%s.%s = :%s', $alias, $field, $parameterName));
            }

            $queryBuilder->setParameter($parameterName, $data['value']);
        }
    }

    public function getDefaultOptions()
    {
        return [];
    }

    public function getRenderSettings()
    {
        return [DefaultType::class, [
            'operator_type' => EqualType::class,
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label' => $this->getLabel(),
        ]];
    }
}
