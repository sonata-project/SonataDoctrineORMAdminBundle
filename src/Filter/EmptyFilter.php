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
     * @param mixed[]|null $data
     */
    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $data): void
    {
        if (null === $data || !\is_array($data) || !\array_key_exists('value', $data)) {
            return;
        }

        if (BooleanType::TYPE_YES === (int) $data['value']) {
            $this->applyWhere(
                $queryBuilder,
                $queryBuilder
                ->expr()
                ->isNull(sprintf('%s.%s', $alias, $field))
            );
        } elseif (BooleanType::TYPE_NO === (int) $data['value']) {
            $this->applyWhere(
                $queryBuilder,
                $queryBuilder
                ->expr()
                ->isNotNull(sprintf('%s.%s', $alias, $field))
            );
        }
    }

    public function getDefaultOptions()
    {
        return [
            'field_type' => BooleanType::class,
            'operator_type' => HiddenType::class,
            'operator_options' => [],
        ];
    }

    public function getRenderSettings()
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
