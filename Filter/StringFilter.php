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

class StringFilter extends Filter
{
    /**
     * {@inheritdoc}
     */
    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $data)
    {
        if (!$data || !is_array($data) || !array_key_exists('value', $data)) {
            return;
        }

        if (is_array($data['value'])) {
            $data['value'] = array_shift($data['value']);
        }

        $data['value'] = trim($data['value']);

        if (strlen($data['value']) == 0) {
            return;
        }

        $data['type'] = !isset($data['type']) ? ChoiceType::TYPE_CONTAINS : $data['type'];

        $operator = $this->getOperator((int) $data['type']);

        if (!$operator) {
            $operator = 'LIKE';
        }

        // c.name > '1' => c.name OPERATOR :FIELDNAME
        $parameterName = $this->getNewParameterName($queryBuilder);

        $or = $queryBuilder->expr()->orX();

        $or->add(sprintf('%s.%s %s :%s', $alias, $field, $operator, $parameterName));

        if ($data['type'] == ChoiceType::TYPE_NOT_CONTAINS) {
            $or->add($queryBuilder->expr()->isNull(sprintf('%s.%s', $alias, $field)));
        }

        $this->applyWhere($queryBuilder, $or);

        if ($data['type'] == ChoiceType::TYPE_EQUAL) {
            $queryBuilder->setParameter($parameterName, $data['value']);
        } else {
            $queryBuilder->setParameter($parameterName, sprintf($this->getOption('format'), $data['value']));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions()
    {
        return array(
            'format' => '%%%s%%',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getRenderSettings()
    {
        return array('Sonata\AdminBundle\Form\Type\Filter\ChoiceType', array(
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label' => $this->getLabel(),
        ));
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    private function getOperator($type)
    {
        $choices = array(
            ChoiceType::TYPE_CONTAINS => 'LIKE',
            ChoiceType::TYPE_NOT_CONTAINS => 'NOT LIKE',
            ChoiceType::TYPE_EQUAL => '=',
        );

        return isset($choices[$type]) ? $choices[$type] : false;
    }
}
