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
use Sonata\AdminBundle\Form\Type\Filter\DefaultType;
use Sonata\CoreBundle\Form\Type\EqualType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormTypeInterface;

class ClassFilter extends Filter
{
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

    public function getDefaultOptions()
    {
        return [];
    }

    public function getFieldType()
    {
        return $this->getOption('field_type', ChoiceType::class);
    }

    public function getFieldOptions()
    {
        $choiceOptions = [
            'required' => false,
            'choices' => $this->getOption('sub_classes'),
        ];

        if (method_exists(FormTypeInterface::class, 'setDefaultOptions')) {
            $choiceOptions['choices_as_values'] = true;
        }

        return $this->getOption('choices', $choiceOptions);
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
