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

use Doctrine\ORM\QueryBuilder;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Form\Type\Filter\ChoiceType;

class ChoiceFilter extends Filter
{
    /**
     * @param ProxyQueryInterface|QueryBuilder $queryBuilder
     *
     * {@inheritdoc}
     */
    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $data)
    {
        if (!$data || !is_array($data) || !array_key_exists('type', $data) || !array_key_exists('value', $data)) {
            return;
        }

        if (is_array($data['value'])) {
            if (count($data['value']) == 0) {
                return;
            }

            if (in_array('all', $data['value'], true)) {
                return;
            }

            // Have to pass IN array value as parameter. See: http://www.doctrine-project.org/jira/browse/DDC-3759
            $completeField = sprintf('%s.%s', $alias, $field);
            $parameterName = $this->getNewParameterName($queryBuilder);
            if ($data['type'] == ChoiceType::TYPE_NOT_CONTAINS) {
                $this->applyWhere($queryBuilder, $queryBuilder->expr()->notIn($completeField, ':'.$parameterName));
            } else {
                $this->applyWhere($queryBuilder, $queryBuilder->expr()->in($completeField, ':'.$parameterName));
            }
            $queryBuilder->setParameter($parameterName, $data['value']);
        } else {
            if ($data['value'] === '' || $data['value'] === null || $data['value'] === false || $data['value'] === 'all') {
                return;
            }

            $parameterName = $this->getNewParameterName($queryBuilder);

            if ($data['type'] == ChoiceType::TYPE_NOT_CONTAINS) {
                $this->applyWhere($queryBuilder, sprintf('%s.%s <> :%s', $alias, $field, $parameterName));
            } else {
                $this->applyWhere($queryBuilder, sprintf('%s.%s = :%s', $alias, $field, $parameterName));
            }

            $queryBuilder->setParameter($parameterName, $data['value']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getRenderSettings()
    {
        // NEXT_MAJOR: Remove this line when drop Symfony <2.8 support
        $type = method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')
            ? 'Sonata\AdminBundle\Form\Type\Filter\DefaultType'
            : 'sonata_type_filter_default';

        return array($type, array(
            'operator_type' => method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')
                ? 'Sonata\CoreBundle\Form\Type\EqualType'
                : 'sonata_type_equal', // NEXT_MAJOR: Remove ternary (when requirement of Symfony is >= 2.8)
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label' => $this->getLabel(),
        ));
    }
}
