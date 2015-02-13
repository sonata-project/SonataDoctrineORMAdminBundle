<?php

/*
 * This file is part of the Sonata package.
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
            $values = array();
            foreach ($data['value'] as $v) {
                if (!in_array($v, array(BooleanType::TYPE_NO, BooleanType::TYPE_YES))) {
                    continue;
                }

                $values[] = ($v == BooleanType::TYPE_YES) ? 1 : 0;
            }

            if (count($values) == 0) {
                return;
            }

            $this->applyWhere($queryBuilder, $queryBuilder->expr()->in(sprintf('%s.%s', $alias, $field), $values));
        } else {

            if (!in_array($data['value'], array(BooleanType::TYPE_NO, BooleanType::TYPE_YES))) {
                return;
            }

            $parameterName = $this->getNewParameterName($queryBuilder);
            $this->applyWhere($queryBuilder, sprintf('%s.%s = :%s', $alias, $field, $parameterName));
            $queryBuilder->setParameter($parameterName, ($data['value'] == BooleanType::TYPE_YES) ? 1 : 0);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions()
    {
        return array(
            'field_type'  => 'sonata_type_boolean'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getRenderSettings()
    {
        return array('sonata_type_filter_default', array(
            'field_type'    => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'operator_type' => 'hidden',
            'operator_options' => array(),
            'label'         => $this->getLabel()
        ));
    }
}
