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
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\Form\Type\BooleanType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

final class BooleanFilter extends Filter
{
    public function filter(ProxyQueryInterface $query, string $alias, string $field, FilterData $data): void
    {
        if (!$data->hasValue()) {
            return;
        }

        $value = $data->getValue();

        if (\is_array($value)) {
            $values = [];
            foreach ($value as $v) {
                if (!\in_array($v, [BooleanType::TYPE_NO, BooleanType::TYPE_YES], true)) {
                    continue;
                }

                $values[] = (BooleanType::TYPE_YES === $v) ? 1 : 0;
            }

            if (0 === \count($values)) {
                return;
            }

            $this->applyWhere($query, $query->getQueryBuilder()->expr()->in(sprintf('%s.%s', $alias, $field), $values));
        } else {
            if (!\in_array($value, [BooleanType::TYPE_NO, BooleanType::TYPE_YES], true)) {
                return;
            }

            $parameterName = $this->getNewParameterName($query);
            $this->applyWhere($query, sprintf('%s.%s = :%s', $alias, $field, $parameterName));
            $query->getQueryBuilder()->setParameter($parameterName, (BooleanType::TYPE_YES === $value) ? 1 : 0);
        }
    }

    public function getDefaultOptions(): array
    {
        return [
            'field_type' => BooleanType::class,
            'operator_type' => HiddenType::class,
            'operator_options' => [],
        ];
    }

    public function getRenderSettings(): array
    {
        return [DefaultType::class, [
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'operator_type' => $this->getOption('operator_type'),
            'operator_options' => $this->getOption('operator_options'),
            'label' => $this->getLabel(),
        ]];
    }
}
