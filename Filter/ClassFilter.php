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

        if (0 == strlen($data['value'])) {
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
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldType()
    {
        return $this->getOption('field_type', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType');
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldOptions()
    {
        $choiceOptions = [
            'required' => false,
            'choices' => $this->getOption('sub_classes'),
        ];

        // NEXT_MAJOR: Remove (when requirement of Symfony is >= 2.7)
        if (!method_exists('Symfony\Component\Form\AbstractType', 'configureOptions')) {
            $choiceOptions['choices'] = array_flip($this->getOption('sub_classes'));
            // NEXT_MAJOR: Remove (when requirement of Symfony is >= 3.0)
        } elseif (method_exists('Symfony\Component\Form\FormTypeInterface', 'setDefaultOptions')) {
            $choiceOptions['choices_as_values'] = true;
        }

        return $this->getOption('choices', $choiceOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function getRenderSettings()
    {
        return ['Sonata\AdminBundle\Form\Type\Filter\DefaultType', [
            'operator_type' => 'Sonata\CoreBundle\Form\Type\EqualType',
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label' => $this->getLabel(),
        ]];
    }

    /**
     * @param int $type
     *
     * @return mixed
     */
    private function getOperator($type)
    {
        $choices = [
            EqualType::TYPE_IS_EQUAL => 'INSTANCE OF',
            EqualType::TYPE_IS_NOT_EQUAL => 'NOT INSTANCE OF',
        ];

        return isset($choices[$type]) ? $choices[$type] : false;
    }
}
