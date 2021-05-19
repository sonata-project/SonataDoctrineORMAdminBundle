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

use Doctrine\ORM\Tools\Pagination\CountWalker;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;

/**
 * @internal
 */
final class SmartPaginatorFactory
{
    /**
     * @param array<string, mixed> $hints
     *
     * @phpstan-return Paginator<object>
     */
    public static function create(ProxyQueryInterface $proxyQuery, array $hints = []): Paginator
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

        $hasHavingPart = null !== $queryBuilder->getDQLPart('having');

        // it is only safe to disable output walkers for really simple queries
        if (!$hasHavingPart && !$hasJoins && $hasSingleIdentifierName) {
            $paginator->setUseOutputWalkers(false);
        }

        return $paginator;
    }
}
