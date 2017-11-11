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
use Sonata\CoreBundle\Form\Type\BooleanType;

class BooleanFilter extends Filter
{
    /**
     * {@inheritdoc}
     */
    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $data)
    {
        if (!$data || !is_array($data) || !array_key_exists('type', $data) || !array_key_exists('value', $data)) {
            return;
        }

        if (is_array($data['value'])) {
            $values = [];
            foreach ($data['value'] as $v) {
                if (!in_array($v, [BooleanType::TYPE_NO, BooleanType::TYPE_YES])) {
                    continue;
                }

                $values[] = (BooleanType::TYPE_YES == $v) ? 1 : 0;
            }

            if (0 == count($values)) {
                return;
            }

            $this->applyWhere($queryBuilder, $queryBuilder->expr()->in(sprintf('%s.%s', $alias, $field), $values));
        } else {
            if (!in_array($data['value'], [BooleanType::TYPE_NO, BooleanType::TYPE_YES])) {
                return;
            }

            $parameterName = $this->getNewParameterName($queryBuilder);
            $this->applyWhere($queryBuilder, sprintf('%s.%s = :%s', $alias, $field, $parameterName));
            $queryBuilder->setParameter($parameterName, (BooleanType::TYPE_YES == $data['value']) ? 1 : 0);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions()
    {
        return ['field_type' => 'Sonata\CoreBundle\Form\Type\BooleanType'];
    }

    /**
     * {@inheritdoc}
     */
    public function getRenderSettings()
    {
        return ['Sonata\AdminBundle\Form\Type\Filter\DefaultType', [
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'operator_type' => 'Symfony\Component\Form\Extension\Core\Type\HiddenType',
            'operator_options' => [],
            'label' => $this->getLabel(),
        ]];
    }
}
