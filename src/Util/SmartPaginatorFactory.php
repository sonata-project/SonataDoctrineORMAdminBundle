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

namespace Sonata\DoctrineORMAdminBundle\Util;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;

/**
 * @internal
 */
final class SmartPaginatorFactory
{
    /**
     * NEXT_MAJOR: Replace ProxyQuery by ProxyQueryInterface.
     *
     * @param array<string, mixed> $hints
     */
    public static function create(ProxyQuery $proxyQuery, array $hints = []): Paginator
    {
        $queryBuilder = $proxyQuery->getQueryBuilder();

        $identifierFieldNames = $queryBuilder
            ->getEntityManager()
            ->getClassMetadata(current($queryBuilder->getRootEntities()))
            ->getIdentifierFieldNames();

        $hasSingleIdentifierName = 1 === \count($identifierFieldNames);
        $hasJoins = \count($queryBuilder->getDQLPart('join')) > 0;

        $query = $proxyQuery->getDoctrineQuery();

        if (!$hasJoins) {
            $query->setHint(CountWalker::HINT_DISTINCT, false);
        }

        foreach ($hints as $name => $value) {
            $query->setHint($name, $value);
        }

        // Paginator with fetchJoinCollection doesn't work with composite primary keys
        // https://github.com/doctrine/orm/issues/2910
        // To stay safe fetch join only when we have single primary key and joins
        $paginator = new Paginator($query, $hasSingleIdentifierName && $hasJoins);

        // it is only safe to disable output walkers for really simple queries
        if (self::canDisableOutPutWalkers($proxyQuery)) {
            $paginator->setUseOutputWalkers(false);
        }

        return $paginator;
    }

    /**
     * @see https://github.com/doctrine/orm/issues/8278#issue-705517756
     */
    private static function canDisableOutPutWalkers(ProxyQueryInterface $proxyQuery): bool
    {
        $queryBuilder = $proxyQuery->getQueryBuilder();

        // does not support queries using HAVING
        if (null !== $queryBuilder->getDQLPart('having')) {
            return false;
        }

        $fromParts = $queryBuilder->getDQLPart('from');

        // does not support queries using multiple entities in FROM
        if (1 !== \count($fromParts)) {
            return false;
        }

        $fromPart = current($fromParts);

        $classMetadata = $queryBuilder
            ->getEntityManager()
            ->getClassMetadata($fromPart->getFrom());

        $identifierFieldNames = $classMetadata->getIdentifierFieldNames();

        // does not support entities using a composite identifier
        if (1 !== \count($identifierFieldNames)) {
            return false;
        }

        $identifierName = current($identifierFieldNames);

        // does not support entities using a foreign key as identifier
        if ($classMetadata->hasAssociation($identifierName)) {
            return false;
        }

        // does not support queries using a field from a toMany relation in the ORDER BY clause
        if (self::hasOrderByWithToManyAssociation($proxyQuery)) {
            return false;
        }

        return true;
    }

    private static function hasOrderByWithToManyAssociation(ProxyQueryInterface $proxyQuery): bool
    {
        $queryBuilder = $proxyQuery->getQueryBuilder();

        $joinParts = $queryBuilder->getDQLPart('join');

        if (0 === \count($joinParts)) {
            return false;
        }

        $sortBy = $proxyQuery->getSortBy();

        if (null === $sortBy) {
            return false;
        }

        $joinAliases = [];

        foreach ($joinParts as $joinPart) {
            foreach ($joinPart as $join) {
                $joinAliases[] = $join->getAlias();
            }
        }

        foreach ($joinAliases as $joinAlias) {
            if (0 === strpos($sortBy, $joinAlias.'.')) {
                return true;
            }
        }

        return false;
    }
}
