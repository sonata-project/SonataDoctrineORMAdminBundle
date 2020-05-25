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
use Sonata\AdminBundle\Form\Type\Filter\ChoiceType;
use Sonata\AdminBundle\Form\Type\Operator\ContainsOperatorType;

final class StringListFilter extends Filter
{
    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $data): void
    {
        if (!$data || !\is_array($data) || !\array_key_exists('type', $data) || !\array_key_exists('value', $data)) {
            return;
        }

        if (!\is_array($data['value'])) {
            $data['value'] = [$data['value']];
        }

        $operator = ContainsOperatorType::TYPE_NOT_CONTAINS === $data['type'] ? 'NOT LIKE' : 'LIKE';

        $andConditions = $queryBuilder->expr()->andX();
        foreach ($data['value'] as $value) {
            $parameterName = $this->getNewParameterName($queryBuilder);
            $andConditions->add(sprintf('%s.%s %s :%s', $alias, $field, $operator, $parameterName));

            $queryBuilder->setParameter($parameterName, '%'.serialize($value).'%');
        }

        if (ContainsOperatorType::TYPE_EQUAL === $data['type']) {
            $andConditions->add(sprintf("%s.%s LIKE 'a:%s:%%'", $alias, $field, \count($data['value'])));
        }

        $this->applyWhere($queryBuilder, $andConditions);
    }

    public function getDefaultOptions(): array
    {
        return [];
    }

    public function getRenderSettings(): array
    {
        return [ChoiceType::class, [
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label' => $this->getLabel(),
        ]];
    }
}
