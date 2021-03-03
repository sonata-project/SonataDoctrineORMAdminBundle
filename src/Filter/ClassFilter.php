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
use Sonata\AdminBundle\Form\Type\Operator\EqualOperatorType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * @final since sonata-project/doctrine-orm-admin-bundle 3.24
 */
class ClassFilter extends Filter
{
    public const CHOICES = [
        EqualOperatorType::TYPE_EQUAL => 'INSTANCE OF',
        EqualOperatorType::TYPE_NOT_EQUAL => 'NOT INSTANCE OF',
    ];

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

        if (!$data || !\is_array($data) || !\array_key_exists('value', $data)) {
            return;
        }

        if (0 === \strlen($data['value'])) {
            return;
        }

        $type = $data['type'] ?? EqualOperatorType::TYPE_EQUAL;
        // NEXT_MAJOR: Remove this if and the (int) cast.
        if (!\is_int($type)) {
            @trigger_error(
                'Passing a non integer type is deprecated since sonata-project/doctrine-orm-admin-bundle 3.30'
                .' and will throw a \TypeError error in version 4.0.',
            );
        }
        $operator = $this->getOperator((int) $type);

        $this->applyWhere($query, sprintf('%s %s %s', $alias, $operator, $data['value']));
    }

    public function getDefaultOptions()
    {
        return [
            'operator_type' => EqualOperatorType::class,
            'operator_options' => [],
        ];
    }

    public function getFieldType()
    {
        return $this->getOption('field_type', ChoiceType::class);
    }

    public function getFieldOptions()
    {
        $choiceOptions = [
            'required' => false,
            'choices' => $this->getOption('sub_classes'),
        ];

        return $this->getOption('choices', $choiceOptions);
    }

    public function getRenderSettings()
    {
        return [DefaultType::class, [
            'operator_type' => $this->getOption('operator_type'),
            'operator_options' => $this->getOption('operator_options'),
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label' => $this->getLabel(),
        ]];
    }

    private function getOperator(int $type): string
    {
        if (!isset(self::CHOICES[$type])) {
            // NEXT_MAJOR: Throw an \OutOfRangeException instead.
            @trigger_error(
                'Passing a non supported type is deprecated since sonata-project/doctrine-orm-admin-bundle 3.30'
                .' and will throw an \OutOfRangeException error in version 4.0.',
            );
//            throw new \OutOfRangeException(sprintf(
//                'The type "%s" is not supported, allowed one are "%s".',
//                $type,
//                implode('", "', array_keys(self::CHOICES))
//            ));
        }

        // NEXT_MAJOR: Remove the default value
        return self::CHOICES[$type] ?? self::CHOICES[EqualOperatorType::TYPE_EQUAL];
    }
}
