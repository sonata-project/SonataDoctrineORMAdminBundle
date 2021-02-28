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

namespace Sonata\DoctrineORMAdminBundle\Datagrid;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface as BaseProxyQueryInterface;

/**
 * NEXT_MAJOR: Remove the "@method" and uncomment the methods instead.
 *
 * @method Query getDoctrineQuery()
 */
interface ProxyQueryInterface extends BaseProxyQueryInterface
{
    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder();

//    /**
//     * This method should be preferred over `$this->getQueryBuilder()->getQuery()`
//     * since some changes are done to the query builder in order to handle all the
//     * previously called Sonata\AdminBundle\Datagrid\ProxyQueryInterface methods.
//     *
//     * @return Query
//     */
//    public function getDoctrineQuery(): Query;
}
