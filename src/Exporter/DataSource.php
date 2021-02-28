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

use Doctrine\ORM\Query;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface as BaseProxyQueryInterface;
use Sonata\AdminBundle\Exporter\DataSourceInterface;
use Sonata\DoctrineORMAdminBundle\Datagrid\OrderByToSelectWalker;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\Exporter\Source\DoctrineORMQuerySourceIterator;
use Sonata\Exporter\Source\SourceIteratorInterface;

/**
 * @final since sonata-project/doctrine-orm-admin-bundle 3.29
 */
class DataSource implements DataSourceInterface
{
    public function createIterator(BaseProxyQueryInterface $query, array $fields): SourceIteratorInterface
    {
        // NEXT_MAJOR: Keep the else part and throw a TypeError instead.
        if (!$query instanceof ProxyQueryInterface) {
            @trigger_error(sprintf(
                'Passing %s as argument 1 to %s() is deprecated since sonata-project/doctrine-orm-admin-bundle 3.27'
                .' and will throw a \TypeError error in version 4.0. You MUST pass an instance of %s instead.',
                \get_class($query),
                __METHOD__,
                ProxyQueryInterface::class
            ));

            $query->select('DISTINCT '.current($query->getRootAliases()));
            $query->setFirstResult(null);
            $query->setMaxResults(null);

            $sortBy = $query->getSortBy();
            if (null !== $sortBy) {
                $query->addOrderBy($sortBy, $query->getSortOrder());
                $doctrineQuery = $query->getQuery();
                $doctrineQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [OrderByToSelectWalker::class]);
            } else {
                $doctrineQuery = $query->getQuery();
            }
        } else {
            // Distinct is needed to iterate, even if group by is used
            // @see https://github.com/doctrine/orm/issues/5868
            $query->getQueryBuilder()->distinct();
            $query->setFirstResult(null);
            $query->setMaxResults(null);

            $doctrineQuery = $query->getDoctrineQuery();
        }

        return new DoctrineORMQuerySourceIterator($doctrineQuery, $fields);
    }
}
