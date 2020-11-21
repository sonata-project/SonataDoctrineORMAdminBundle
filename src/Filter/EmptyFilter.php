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
use Sonata\Form\Type\BooleanType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

final class EmptyFilter extends Filter
{
    /**
     * @param string       $alias
     * @param string       $field
     * @param mixed[]|null $value
     */
    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $value): void
    {
        if (null === $value || !\is_array($value) || !\array_key_exists('value', $value)) {
            return;
        }

        $isYes = BooleanType::TYPE_YES === (int) $value['value'];
        $isNo = BooleanType::TYPE_NO === (int) $value['value'];

        if (!$this->getOption('inverse') && $isYes || $this->getOption('inverse') && $isNo) {
            $this->applyWhere(
                $queryBuilder,
                $queryBuilder->expr()->isNull(sprintf('%s.%s', $alias, $field))
            );
        } elseif (!$this->getOption('inverse') && $isNo || $this->getOption('inverse') && $isYes) {
            $this->applyWhere(
                $queryBuilder,
                $queryBuilder->expr()->isNotNull(sprintf('%s.%s', $alias, $field))
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
