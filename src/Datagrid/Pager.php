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

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\CountOutputWalker;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use Sonata\AdminBundle\Datagrid\Pager as BasePager;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;

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
        $countQuery = $this->getClonedQuery()->getQuery();

        $platform = $countQuery->getEntityManager()->getConnection()->getDatabasePlatform();
        $rsm = new Query\ResultSetMapping();
        $rsm->addScalarResult($platform->getSQLResultCasing('dctrn_count'), 'count');
        $countQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, CountOutputWalker::class);
        $countQuery->setResultSetMapping($rsm);

        $countQuery->setFirstResult(null)->setMaxResults(null);

        return $countQuery->getSingleScalarResult();
    }

    public function getResults($hydrationMode = Query::HYDRATE_OBJECT)
    {
        return $this->getQuery()->execute([], $hydrationMode);
    }

    public function init()
    {
        $this->resetIterator();

        $this->setNbResults($this->computeNbResult());

        $this->getQuery()->setFirstResult(null);
        $this->getQuery()->setMaxResults(null);

        if (\count($this->getParameters()) > 0) {
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
     * @return ProxyQueryInterface
     */
    private function getClonedQuery()
    {
        /** @var ProxyQueryInterface $cloneQuery */
        $cloneQuery = clone $this->getQuery();

        if (\count($this->getParameters()) > 0) {
            $countQuery->setParameters($this->getParameters());
        }

        return $cloneQuery;
    }
}
