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
use Sonata\AdminBundle\Form\Type\Filter\ChoiceType;
use Sonata\AdminBundle\Form\Type\Operator\StringOperatorType;

/**
 * @final since sonata-project/doctrine-orm-admin-bundle 3.24
 */
class StringFilter extends Filter
{
    public const CHOICES = [
        StringOperatorType::TYPE_CONTAINS => 'LIKE',
        StringOperatorType::TYPE_STARTS_WITH => 'LIKE',
        StringOperatorType::TYPE_ENDS_WITH => 'LIKE',
        StringOperatorType::TYPE_NOT_CONTAINS => 'NOT LIKE',
        StringOperatorType::TYPE_EQUAL => '=',
    ];

    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $value): void
    {
        if (!$value || !\is_array($value) || !\array_key_exists('value', $value) || null === $value['value']) {
            return;
        }

        $value['value'] = trim($value['value']);

        if (0 === \strlen($value['value'])) {
            return;
        }

        $type = $value['type'] ?? StringOperatorType::TYPE_CONTAINS;
        $operator = $this->getOperator((int) $type);

        // c.name > '1' => c.name OPERATOR :FIELDNAME
        $parameterName = $this->getNewParameterName($queryBuilder);

        $or = $queryBuilder->expr()->orX();

        if ($this->getOption('case_sensitive')) {
            $or->add(sprintf('%s.%s %s :%s', $alias, $field, $operator, $parameterName));
        } else {
            $or->add(sprintf('LOWER(%s.%s) %s :%s', $alias, $field, $operator, $parameterName));
        }

        if (StringOperatorType::TYPE_NOT_CONTAINS === $type) {
            $or->add($queryBuilder->expr()->isNull(sprintf('%s.%s', $alias, $field)));
        }

        $this->applyWhere($queryBuilder, $or);

        if (StringOperatorType::TYPE_EQUAL === $type) {
            $queryBuilder->setParameter(
                $parameterName,
                $this->getOption('case_sensitive') ? $value['value'] : mb_strtolower($value['value'])
            );
        } else {
            switch ($type) {
                case StringOperatorType::TYPE_STARTS_WITH:
                    $format = '%s%%';
                    break;
                case StringOperatorType::TYPE_ENDS_WITH:
                    $format = '%%%s';
                    break;
                default:
                    $format = $this->getOption('format');

                    if ('%%%s%%' !== $format) {
                        @trigger_error(
                            'The "format" option is deprecated since sonata-project/doctrine-orm-admin-bundle 3.21 and will be removed in version 4.0.',
                            E_USER_DEPRECATED
                        );
                    }
            }

            $queryBuilder->setParameter(
                $parameterName,
                sprintf(
                    $format,
                    $this->getOption('case_sensitive') ? $value['value'] : mb_strtolower($value['value'])
                )
            );
        }
    }

    public function getDefaultOptions(): array
    {
        return [
            // NEXT_MAJOR: Remove the format option.
            'format' => '%%%s%%',
            'case_sensitive' => true,
        ];
    }

    public function getRenderSettings(): array
    {
        return [ChoiceType::class, [
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label' => $this->getLabel(),
        ]];
    }

    private function getOperator(int $type): string
    {
        return self::CHOICES[$type] ?? self::CHOICES[StringOperatorType::TYPE_CONTAINS];
    }
}
