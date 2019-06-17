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

class StringFilter extends Filter
{
    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $data)
    {
        if (!$data || !\is_array($data) || !\array_key_exists('value', $data) || null === $data['value']) {
            return;
        }

        $data['value'] = trim($data['value']);

        if (0 === \strlen($data['value'])) {
            return;
        }

        $data['type'] = !isset($data['type']) ? ChoiceType::TYPE_CONTAINS : $data['type'];

        $operator = $this->getOperator((int) $data['type']);

        if (!$operator) {
            $operator = 'LIKE';
        }

        // c.name > '1' => c.name OPERATOR :FIELDNAME
        $parameterName = $this->getNewParameterName($queryBuilder);

        if (self::CONDITION_AND === $this->getCondition()) {
            $clause = $queryBuilder->expr()->andX();
        } else {
            $clause = $queryBuilder->expr()->orX();
        }

        if ($this->getOption('case_sensitive')) {
            $clause->add(sprintf('%s.%s %s :%s', $alias, $field, $operator, $parameterName));
        } else {
            $clause->add(sprintf('LOWER(%s.%s) %s :%s', $alias, $field, $operator, $parameterName));
        }

        if (ChoiceType::TYPE_NOT_CONTAINS === $data['type']) {
            $clause->add($queryBuilder->expr()->isNull(sprintf('%s.%s', $alias, $field)));
        }

        if (ChoiceType::TYPE_EQUAL === $data['type']) {
            $queryBuilder->setParameter($parameterName, $data['value']);
        } else {
            $queryBuilder->setParameter($parameterName,
                sprintf(
                    $this->getOption('format'),
                    $this->getOption('case_sensitive') ? $data['value'] : mb_strtolower($data['value'])
                )
            );
        }

        $this->applyWhere($queryBuilder, $clause);

        return $clause;
    }

    public function getDefaultOptions()
    {
        return [
            'format' => '%%%s%%',
            'case_sensitive' => true,
        ];
    }

    public function getRenderSettings()
    {
        return [ChoiceType::class, [
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label' => $this->getLabel(),
        ]];
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    private function getOperator($type)
    {
        $choices = [
            ChoiceType::TYPE_CONTAINS => 'LIKE',
            ChoiceType::TYPE_NOT_CONTAINS => 'NOT LIKE',
            ChoiceType::TYPE_EQUAL => '=',
        ];

        return $choices[$type] ?? false;
    }
}
