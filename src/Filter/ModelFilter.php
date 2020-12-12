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

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface as BaseProxyQueryInterface;
use Sonata\AdminBundle\Form\Type\Filter\DefaultType;
use Sonata\AdminBundle\Form\Type\Operator\EqualOperatorType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * @final since sonata-project/doctrine-orm-admin-bundle 3.24
 */
class ModelFilter extends Filter
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

        if (!$data || !\is_array($data) || !\array_key_exists('value', $data) || empty($data['value'])) {
            return;
        }

        if ($data['value'] instanceof Collection) {
            $data['value'] = $data['value']->toArray();
        }

        if (!\is_array($data['value'])) {
            $data['value'] = [$data['value']];
        }

        $this->handleMultiple($query, $alias, $data);
    }

    public function getDefaultOptions(): array
    {
        return [
            'mapping_type' => false,
            'field_name' => false,
            'field_type' => EntityType::class,
            'field_options' => [],
            'operator_type' => EqualOperatorType::class,
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
     * For the record, the $alias value is provided by the association method (and the entity join method)
     *  so the field value is not used here.
     *
     * @param string  $alias
     * @param mixed[] $data
     */
    protected function handleMultiple(BaseProxyQueryInterface $query, $alias, $data): void
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

        if (0 === \count($data['value'])) {
            return;
        }

        $parameterName = $this->getNewParameterName($query);

        if (isset($data['type']) && EqualOperatorType::TYPE_NOT_EQUAL === $data['type']) {
            $or = $query->getQueryBuilder()->expr()->orX();

            $or->add($query->getQueryBuilder()->expr()->notIn($alias, ':'.$parameterName));

            if (ClassMetadata::MANY_TO_MANY === $this->getOption('mapping_type')) {
                $or->add(
                    sprintf('%s.%s IS EMPTY', $this->getParentAlias($query, $alias), $this->getFieldName())
                );
            } else {
                $or->add($query->getQueryBuilder()->expr()->isNull(
                    sprintf('IDENTITY(%s.%s)', $this->getParentAlias($query, $alias), $this->getFieldName())
                ));
            }

            $this->applyWhere($query, $or);
        } else {
            $this->applyWhere($query, $query->getQueryBuilder()->expr()->in($alias, ':'.$parameterName));
        }

        $query->getQueryBuilder()->setParameter($parameterName, $data['value']);
    }

    /**
     * @param mixed[] $data
     *
     * @phpstan-return array{string, bool}
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

        $types = [
            ClassMetadata::ONE_TO_ONE,
            ClassMetadata::ONE_TO_MANY,
            ClassMetadata::MANY_TO_MANY,
            ClassMetadata::MANY_TO_ONE,
        ];

        if (!\in_array($this->getOption('mapping_type'), $types, true)) {
            throw new \RuntimeException('Invalid mapping type');
        }

        $associationMappings = $this->getParentAssociationMappings();
        $associationMappings[] = $this->getAssociationMapping();
        $alias = $query->entityJoin($associationMappings);

        return [$alias, false];
    }

    /**
     * Retrieve the parent alias for given alias.
     * Root alias for direct association or entity joined alias for association depth >= 2.
     */
    private function getParentAlias(BaseProxyQueryInterface $query, string $alias): string
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

        $parentAlias = $rootAlias = current($query->getQueryBuilder()->getRootAliases());
        $joins = $query->getQueryBuilder()->getDQLPart('join');
        if (isset($joins[$rootAlias])) {
            foreach ($joins[$rootAlias] as $join) {
                if ($join->getAlias() === $alias) {
                    $parts = explode('.', $join->getJoin());
                    $parentAlias = $parts[0];

                    break;
                }
            }
        }

        return $parentAlias;
    }
}
