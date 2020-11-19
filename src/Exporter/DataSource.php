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
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Exporter\DataSourceInterface;
use Sonata\DoctrineORMAdminBundle\Datagrid\OrderByToSelectWalker;
use Sonata\Exporter\Source\DoctrineORMQuerySourceIterator;
use Sonata\Exporter\Source\SourceIteratorInterface;

class DataSource implements DataSourceInterface
{
    public function createIterator(ProxyQueryInterface $query, array $fields): SourceIteratorInterface
    {
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

        return new DoctrineORMQuerySourceIterator($doctrineQuery, $fields);
    }
}
