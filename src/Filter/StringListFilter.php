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

use Sonata\AdminBundle\Datagrid\ProxyQueryInterface as BaseProxyQueryInterface;
use Sonata\AdminBundle\Form\Type\Filter\ChoiceType;
use Sonata\AdminBundle\Form\Type\Operator\ContainsOperatorType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;

final class StringListFilter extends Filter
{
    public function filter(BaseProxyQueryInterface $query, $alias, $field, $data): void
    {
        /* NEXT_MAJOR: Remove this deprecation and update the typehint */
        if (!$query instanceof ProxyQueryInterface) {
            @trigger_error(sprintf(
                'Passing %s as argument 1 to %s() is deprecated since sonata-project/doctrine-orm-admin-bundle 3.x'
                .' and will throw a \TypeError error in version 4.0. You MUST pass an instance of %s instead.',
                \get_class($query),
                __METHOD__,
                ProxyQueryInterface::class
            ));
        }

        if (!$data || !\is_array($data) || !\array_key_exists('type', $data) || !\array_key_exists('value', $data)) {
            return;
        }

        if (!\is_array($data['value'])) {
            $data['value'] = [$data['value']];
        }

        $operator = ContainsOperatorType::TYPE_NOT_CONTAINS === $data['type'] ? 'NOT LIKE' : 'LIKE';

        $andConditions = $query->getQueryBuilder()->expr()->andX();
        foreach ($data['value'] as $item) {
            $parameterName = $this->getNewParameterName($query);
            $andConditions->add(sprintf('%s.%s %s :%s', $alias, $field, $operator, $parameterName));

            $query->getQueryBuilder()->setParameter($parameterName, '%'.serialize($item).'%');
        }

        if (ContainsOperatorType::TYPE_EQUAL === $data['type']) {
            $andConditions->add(sprintf("%s.%s LIKE 'a:%s:%%'", $alias, $field, \count($data['value'])));
        }

        $this->applyWhere($query, $andConditions);
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
