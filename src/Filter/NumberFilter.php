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
use Sonata\AdminBundle\Form\Type\Filter\NumberType;
use Sonata\AdminBundle\Form\Type\Operator\NumberOperatorType;

class NumberFilter extends Filter
{
    public const CHOICES = [
        NumberOperatorType::TYPE_EQUAL => '=',
        NumberOperatorType::TYPE_GREATER_EQUAL => '>=',
        NumberOperatorType::TYPE_GREATER_THAN => '>',
        NumberOperatorType::TYPE_LESS_EQUAL => '<=',
        NumberOperatorType::TYPE_LESS_THAN => '<',
    ];

    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $data): void
    {
        if (!$data || !\is_array($data) || !\array_key_exists('value', $data) || !is_numeric($data['value'])) {
            return;
        }

        $type = $data['type'] ?? NumberOperatorType::TYPE_EQUAL;
        $operator = $this->getOperator((int) $type);

        // c.name > '1' => c.name OPERATOR :FIELDNAME
        $parameterName = $this->getNewParameterName($queryBuilder);
        $this->applyWhere($queryBuilder, sprintf('%s.%s %s :%s', $alias, $field, $operator, $parameterName));
        $queryBuilder->setParameter($parameterName, $data['value']);
    }

    public function getDefaultOptions()
    {
        return [];
    }

    public function getRenderSettings()
    {
        return [NumberType::class, [
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label' => $this->getLabel(),
        ]];
    }

    private function getOperator(int $type): string
    {
        return self::CHOICES[$type] ?? self::CHOICES[NumberOperatorType::TYPE_EQUAL];
    }
}
