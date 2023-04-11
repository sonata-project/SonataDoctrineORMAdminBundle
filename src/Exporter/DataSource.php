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

namespace Sonata\DoctrineORMAdminBundle\Exporter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface as BaseProxyQueryInterface;
use Sonata\AdminBundle\Exporter\DataSourceInterface;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\Exporter\Source\DoctrineORMQuerySourceIterator;

final class DataSource implements DataSourceInterface
{
    public function createIterator(BaseProxyQueryInterface $query, array $fields): \Iterator
    {
        if (!$query instanceof ProxyQueryInterface) {
            throw new \TypeError(sprintf('The query MUST implement %s.', ProxyQueryInterface::class));
        }

        $rootAlias = current($query->getQueryBuilder()->getRootAliases());

        // Distinct is needed to iterate, even if group by is used
        // @see https://github.com/doctrine/orm/issues/5868
        $query->getQueryBuilder()->distinct();
        $query->getQueryBuilder()->select($rootAlias);

        $sortBy = $query->getSortBy();

        // AddSelect is needed when exporting the results sorted by a column that is part of ManyToOne relation
        // For OneToMany the toIterable() doctrine method is not supported so the select is not added and the sort is removed.
        //
        // @see https://github.com/sonata-project/SonataDoctrineORMAdminBundle/issues/1586
        if (null !== $sortBy) {
            $rootAliasSortBy = strstr($sortBy, '.', true);

            if (false !== $rootAliasSortBy && $rootAliasSortBy !== $rootAlias) {
                $this->isOneToMany($query, $rootAliasSortBy) ?
                    $query->setSortBy([], []) :
                    $query->getQueryBuilder()->addSelect($rootAliasSortBy);
            }
        }

        $query->setFirstResult(null);
        $query->setMaxResults(null);

        return new DoctrineORMQuerySourceIterator($query->getDoctrineQuery(), $fields);
    }

    /**
     * @param ProxyQueryInterface<object> $query
     */
    private function isOneToMany(ProxyQueryInterface $query, string $alias): bool
    {
        $fieldName = $this->findFieldName($query, $alias);

        if (null === $fieldName) {
            return false;
        }

        $rootEntity = current($query->getQueryBuilder()->getRootEntities());

        if (false === $rootEntity) {
            return false;
        }

        $associationMapping = $query
            ->getQueryBuilder()
            ->getEntityManager()
            ->getClassMetadata($rootEntity)
            ->getAssociationMapping($fieldName);

        return ClassMetadata::ONE_TO_MANY === $associationMapping['type'];
    }

    /**
     * @param ProxyQueryInterface<object> $query
     */
    private function findFieldName(ProxyQueryInterface $query, string $alias): ?string
    {
        $joins = $query->getQueryBuilder()->getDQLPart('join');

        foreach ($joins as $joined) {
            foreach ($joined as $joinPart) {
                $join = $joinPart->getJoin();
                $joinAlias = $joinPart->getAlias();

                if ($joinAlias === $alias) {
                    $joinParts = explode('.', $join);

                    return end($joinParts);
                }
            }
        }

        return null;
    }
}
