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

final class NullFilter extends Filter
{
    public function filter(ProxyQueryInterface $query, string $alias, string $field, FilterData $data): void
    {
        if (!$data->hasValue()) {
            return;
        }

        $isYes = BooleanType::TYPE_YES === (int) $data->getValue();
        $isNo = BooleanType::TYPE_NO === (int) $data->getValue();

        if (!$this->getOption('inverse') && $isYes || $this->getOption('inverse') && $isNo) {
            $this->applyWhere(
                $query,
                $query->getQueryBuilder()->expr()->isNull(sprintf('%s.%s', $alias, $field))
            );
        } elseif (!$this->getOption('inverse') && $isNo || $this->getOption('inverse') && $isYes) {
            $this->applyWhere(
                $query,
                $query->getQueryBuilder()->expr()->isNotNull(sprintf('%s.%s', $alias, $field))
            );
        }
    }

    public function getDefaultOptions(): array
    {
        return [
            'field_type' => BooleanType::class,
            'operator_type' => HiddenType::class,
            'operator_options' => [],
            'inverse' => false,
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
