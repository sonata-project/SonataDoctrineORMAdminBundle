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

use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\Type\Filter\DefaultType;
use Sonata\AdminBundle\Form\Type\Operator\EqualOperatorType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

final class ClassFilter extends Filter
{
    public const CHOICES = [
        EqualOperatorType::TYPE_EQUAL => 'INSTANCE OF',
        EqualOperatorType::TYPE_NOT_EQUAL => 'NOT INSTANCE OF',
    ];

    public function filter(ProxyQueryInterface $query, string $alias, string $field, FilterData $data): void
    {
        if (!$data->hasValue()) {
            return;
        }

        if (0 === \strlen($data->getValue())) {
            return;
        }

        $type = $data->getType() ?? EqualOperatorType::TYPE_EQUAL;
        $operator = $this->getOperator($type);

        $this->applyWhere($query, sprintf('%s %s %s', $alias, $operator, $data->getValue()));
    }

    public function getDefaultOptions(): array
    {
        return [
            'field_type' => ChoiceType::class,
            'operator_type' => EqualOperatorType::class,
            'operator_options' => [],
        ];
    }

    public function getRenderSettings(): array
    {
        return [DefaultType::class, [
            'operator_type' => $this->getOption('operator_type'),
            'operator_options' => $this->getOption('operator_options'),
            'field_type' => $this->getFieldType(),
            'field_options' => [
                'required' => false,
                'choices' => $this->getOption('sub_classes'),
            ],
            'label' => $this->getLabel(),
        ]];
    }

    private function getOperator(int $type): string
    {
        if (!isset(self::CHOICES[$type])) {
            throw new \OutOfRangeException(sprintf(
                'The type "%s" is not supported, allowed one are "%s".',
                $type,
                implode('", "', array_keys(self::CHOICES))
            ));
        }

        return self::CHOICES[$type];
    }
}
