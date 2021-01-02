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
use Sonata\AdminBundle\Form\Type\Filter\DefaultType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\Form\Type\BooleanType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * NEXT_MAJOR: Declare this class as final.
 *
 * @final
 */
class NullFilter extends Filter
{
    /**
     * @param string       $alias
     * @param string       $field
     * @param mixed[]|null $data
     */
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

        if (null === $data || !\is_array($data) || !\array_key_exists('value', $data)) {
            return;
        }

        $isYes = BooleanType::TYPE_YES === (int) $data['value'];
        $isNo = BooleanType::TYPE_NO === (int) $data['value'];

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
