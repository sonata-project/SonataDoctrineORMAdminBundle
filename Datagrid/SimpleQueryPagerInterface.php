<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Datagrid;

/**
 * Interface SimpleQueryPagerInterface.
 *
 * @author Andrej Hudec <pulzarraider@gmail.com>
 */
interface SimpleQueryPagerInterface
{
    /**
     * If set to true, the generated query will not contain any duplicate identifier check (e.g. DISTINCT keyword).
     * Enabling simple qurery will improve query performance, but can also return duplicate items. It depends on the query and the database schema.
     * Please enable the simple query only if you are sure that the duplicate identifier check in the query is useless.
     *
     * @param bool $simpleQueryEnabled
     */
    public function setSimpleQueryEnabled($simpleQueryEnabled);
}
