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

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use Sonata\AdminBundle\Datagrid\Pager as BasePager;

/**
 * Doctrine pager class.
 *
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
class Pager extends BasePager
{
    /**
     * NEXT_MAJOR: remove this property.
     *
     * @deprecated This property is deprecated since version 2.4 and will be removed in 3.0
     */
    protected $queryBuilder = null;

    public function computeNbResult()
    {
        $countQuery = clone $this->getQuery();

        if (count($this->getParameters()) > 0) {
            $countQuery->setParameters($this->getParameters());
        }

        $query = $countQuery->getQuery();
        $query->setHint(
            CountWalker::HINT_DISTINCT,
            $countQuery instanceof ProxyQuery ? $countQuery->isDistinct() : true
        );
        $this->appendTreeWalker($query, CountWalker::class);

        return $query->getSingleScalarResult();
    }

    public function getResults($hydrationMode = Query::HYDRATE_OBJECT)
    {
        return $this->getQuery()->execute([], $hydrationMode);
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function init()
    {
        $this->resetIterator();

        $this->setNbResults($this->computeNbResult());

        $this->getQuery()->setFirstResult(null);
        $this->getQuery()->setMaxResults(null);

        if (count($this->getParameters()) > 0) {
            $this->getQuery()->setParameters($this->getParameters());
        }

        if (0 == $this->getPage() || 0 == $this->getMaxPerPage() || 0 == $this->getNbResults()) {
            $this->setLastPage(0);
        } else {
            $offset = ($this->getPage() - 1) * $this->getMaxPerPage();

            $this->setLastPage(ceil($this->getNbResults() / $this->getMaxPerPage()));

            $this->getQuery()->setFirstResult($offset);
            $this->getQuery()->setMaxResults($this->getMaxPerPage());
        }
    }

    /**
     * Appends a custom tree walker to the tree walkers hint.
     *
     * @param string $walkerClass
     */
    private function appendTreeWalker(AbstractQuery $query, $walkerClass)
    {
        $hints = $query->getHint(Query::HINT_CUSTOM_TREE_WALKERS);

        if (false === $hints) {
            $hints = [];
        }

        $hints[] = $walkerClass;
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, $hints);
    }
}
