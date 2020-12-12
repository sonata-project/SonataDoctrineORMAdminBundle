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
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * @final since sonata-project/doctrine-orm-admin-bundle 3.24
 */
class CallbackFilter extends Filter
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

        if (!\is_callable($this->getOption('callback'))) {
            throw new \RuntimeException(sprintf('Please provide a valid callback option "filter" for field "%s"', $this->getName()));
        }

        $isActive = \call_user_func($this->getOption('callback'), $query, $alias, $field, $data);
        if (!\is_bool($isActive)) {
            @trigger_error(
                'Using another return type than boolean for the callback option is deprecated'
                .' since sonata-project/doctrine-orm-admin-bundle 3.25 and will throw an exception in version 4.0.',
                E_USER_DEPRECATED
            );

            // NEXT_MAJOR: Uncomment the following code instead of the deprecation.
//            throw new \UnexpectedValueException(sprintf(
//                'The callback should return a boolean, %s returned',
//                \is_object($isActive) ? 'instance of "'.\get_class($isActive).'"' : '"'.\gettype($isActive).'"'
//            ));
        }

        $this->active = $isActive;
    }

    public function getDefaultOptions(): array
    {
        return [
            'callback' => null,
            'field_type' => TextType::class,
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

    /**
     * @param mixed[] $data
     */
    protected function association(BaseProxyQueryInterface $query, array $data): array
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

        $alias = $query->entityJoin($this->getParentAssociationMappings());

        return [$this->getOption('alias', $alias), $this->getFieldName()];
    }
}
