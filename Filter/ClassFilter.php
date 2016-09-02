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
use Sonata\CoreBundle\Form\Type\EqualType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;

class ClassFilter extends Filter
{
    /**
     * {@inheritdoc}
     */
    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $data)
    {
        if (!$data || !is_array($data) || !array_key_exists('value', $data)) {
            return;
        }

        if (strlen($data['value']) == 0) {
            return;
        }

        $data['type'] = !isset($data['type']) ? EqualType::TYPE_IS_EQUAL : $data['type'];

        $operator = $this->getOperator((int) $data['type']);

        if (!$operator) {
            $operator = 'INSTANCE OF';
        }

        $this->applyWhere($queryBuilder, sprintf('%s %s %s', $alias, $operator, $data['value']));
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
    public function getFieldType()
    {
        // NEXT_MAJOR: Remove ternary (when requirement of Symfony is >= 2.8)
        return $this->getOption('field_type', method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')
            ? 'Symfony\Component\Form\Extension\Core\Type\ChoiceType'
            : 'choice'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldOptions()
    {
        return $this->getOption('choices', array(
            'required' => false,
            'choice_list' => new ChoiceList(
                \array_values($this->getOption('sub_classes')),
                \array_keys($this->getOption('sub_classes'))
            ),
        ));
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

    /**
     * @param int $type
     *
     * @return mixed
     */
    private function getOperator($type)
    {
        $choices = array(
            EqualType::TYPE_IS_EQUAL => 'INSTANCE OF',
            EqualType::TYPE_IS_NOT_EQUAL => 'NOT INSTANCE OF',
        );

        return isset($choices[$type]) ? $choices[$type] : false;
    }
}
