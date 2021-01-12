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
use Doctrine\ORM\Tools\Pagination\Paginator;
use Sonata\AdminBundle\Datagrid\Pager as BasePager;

/**
 * Doctrine pager class.
 *
 * @author Jonathan H. Wage <jonwage@gmail.com>
 *
 * @final since sonata-project/doctrine-orm-admin-bundle 3.24
 */
class Pager extends BasePager
{
    /**
     * @var int
     */
    private $resultsCount = 0;

    public function getResults($hydrationMode = Query::HYDRATE_OBJECT): array
    {
        return $this->getQuery()->execute([], $hydrationMode);
    }

    public function countResults(): int
    {
        return $this->resultsCount;
    }

    public function init(): void
    {
        $this->setResultsCount($this->computeResultsCount());

        $this->getQuery()->setFirstResult(null);
        $this->getQuery()->setMaxResults(null);

        if (0 === $this->getPage() || 0 === $this->getMaxPerPage() || 0 === $this->countResults()) {
            $this->setLastPage(0);
        } else {
            $offset = ($this->getPage() - 1) * $this->getMaxPerPage();

            $this->setLastPage((int) ceil($this->countResults() / $this->getMaxPerPage()));

            $this->getQuery()->setFirstResult($offset);
            $this->getQuery()->setMaxResults($this->getMaxPerPage());
        }
    }

    private function computeResultsCount(): int
    {
        if (null === $this->getQuery()) {
            throw new \TypeError('Missing mandatory datagrid query.');
        }

        $countQuery = clone $this->getQuery();

        $paginator = new Paginator($countQuery->getQueryBuilder());

        return \count($paginator);
    }

    private function setResultsCount(int $count): void
    {
        $this->resultsCount = $count;
    }
}
