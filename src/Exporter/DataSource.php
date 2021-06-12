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

use Sonata\AdminBundle\Datagrid\ProxyQueryInterface as BaseProxyQueryInterface;
use Sonata\AdminBundle\Exporter\DataSourceInterface;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\Exporter\Source\DoctrineORMQuerySourceIterator;
use Sonata\Exporter\Source\SourceIteratorInterface;

final class DataSource implements DataSourceInterface
{
    public function createIterator(BaseProxyQueryInterface $query, array $fields): SourceIteratorInterface
    {
        if (!$query instanceof ProxyQueryInterface) {
            throw new \TypeError(sprintf('The query MUST implement %s.', ProxyQueryInterface::class));
        }

        // Distinct is needed to iterate, even if group by is used
        // @see https://github.com/doctrine/orm/issues/5868
        $query->getQueryBuilder()->distinct();
        $query->getQueryBuilder()->select(current($query->getQueryBuilder()->getRootAliases()));
        $query->setFirstResult(null);
        $query->setMaxResults(null);

        return new DoctrineORMQuerySourceIterator($query->getDoctrineQuery(), $fields);
    }
}
