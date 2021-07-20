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
 * @final since sonata-project/doctrine-orm-admin-bundle 3.24
 */
class BooleanFilter extends Filter
{
    public function filter(BaseProxyQueryInterface $query, $alias, $field, $data)
    {
        /* NEXT_MAJOR: Remove this deprecation and update the typehint */
        if (!$query instanceof ProxyQueryInterface) {
            @trigger_error(sprintf(
                'Passing %s as argument 1 to "%s()" is deprecated since sonata-project/doctrine-orm-admin-bundle 3.27'
                .' and will throw a \TypeError error in version 4.0. You MUST pass an instance of %s instead.',
                \get_class($query),
                __METHOD__,
                ProxyQueryInterface::class
            ), \E_USER_DEPRECATED);
        }

        if (!$data || !\is_array($data) || !\array_key_exists('type', $data) || !\array_key_exists('value', $data)) {
            return;
        }

        if (\is_array($data['value'])) {
            $this->filterWithMultipleValues($query, $alias, $field, $data);
        } else {
            $this->filterWithSingleValue($query, $alias, $field, $data);
        }
    }

    public function getDefaultOptions()
    {
        return [
            'field_type' => BooleanType::class,
            'operator_type' => HiddenType::class,
            'operator_options' => [],
            'treat_null_as' => null,
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

    /**
     * NEXT_MAJOR: Change the typehint to ProxyQueryInterface.
     */
    private function filterWithMultipleValues(BaseProxyQueryInterface $query, string $alias, string $field, array $data = []): void
    {
        $values = [];
        foreach ($data['value'] as $v) {
            if (!\in_array($v, [BooleanType::TYPE_NO, BooleanType::TYPE_YES], true)) {
                continue;
            }

            $values[] = (BooleanType::TYPE_YES === $v) ? 1 : 0;
        }

        if (0 === \count($values)) {
            return;
        }

        $or = $query->getQueryBuilder()->expr()->orX();
        $treatNullAs = $this->getOption('treat_null_as');
        if (
            false === $treatNullAs && \in_array(0, $values, true)
            || true === $treatNullAs && \in_array(1, $values, true)
        ) {
            $or->add($query->getQueryBuilder()->expr()->isNull(sprintf('%s.%s', $alias, $field)));
        }

        $or->add($query->getQueryBuilder()->expr()->in(sprintf('%s.%s', $alias, $field), $values));

        $this->applyWhere($query, $or);
    }

    /**
     * NEXT_MAJOR: Change the typehint to ProxyQueryInterface.
     */
    private function filterWithSingleValue(BaseProxyQueryInterface $query, string $alias, string $field, array $data = []): void
    {
        $or = $query->getQueryBuilder()->expr()->orX();
        $treatNullAs = $this->getOption('treat_null_as');
        if (
            false === $treatNullAs && BooleanType::TYPE_NO === $data['value']
            || true === $treatNullAs && BooleanType::TYPE_YES === $data['value']
        ) {
            $or->add($query->getQueryBuilder()->expr()->isNull(sprintf('%s.%s', $alias, $field)));
        }

        $parameterName = $this->getNewParameterName($query);
        $or->add(sprintf('%s.%s = :%s', $alias, $field, $parameterName));
        $query->getQueryBuilder()->setParameter($parameterName, (BooleanType::TYPE_YES === $data['value']) ? 1 : 0);

        $this->applyWhere($query, $or);
    }
}
