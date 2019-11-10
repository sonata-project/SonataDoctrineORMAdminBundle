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

use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Form\Type\Filter\DefaultType;
use Sonata\Form\Type\EqualType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ClassFilter extends Filter
{
    public const CHOICES = [
        EqualType::TYPE_IS_EQUAL => 'INSTANCE OF',
        EqualType::TYPE_IS_NOT_EQUAL => 'NOT INSTANCE OF',
    ];

    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $data)
    {
        if (!$data || !\is_array($data) || !\array_key_exists('value', $data)) {
            return;
        }

        if (0 === \strlen($data['value'])) {
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
        return [
            'operator_type' => EqualType::class,
            'operator_options' => [],
        ];
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

        return $this->getOption('choices', $choiceOptions);
    }

    public function getRenderSettings()
    {
        return [DefaultType::class, [
            'operator_type' => $this->getOption('operator_type'),
            'operator_options' => $this->getOption('operator_options'),
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
        return self::CHOICES[$type] ?? false;
    }
}
