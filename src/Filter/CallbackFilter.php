<?php

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
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class CallbackFilter extends Filter
{
    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $data)
    {
        if (!\is_callable($this->getOption('callback'))) {
            throw new \RuntimeException(sprintf('Please provide a valid callback option "filter" for field "%s"', $this->getName()));
        }

        $this->active = \call_user_func($this->getOption('callback'), $queryBuilder, $alias, $field, $data);
    }

    public function getDefaultOptions()
    {
        return [
            'callback' => null,
            'field_type' => TextType::class,
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

    protected function association(ProxyQueryInterface $queryBuilder, $data)
    {
        $alias = $queryBuilder->entityJoin($this->getParentAssociationMappings());

        return [$this->getOption('alias', $alias), $this->getFieldName()];
    }
}
