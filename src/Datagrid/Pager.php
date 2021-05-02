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

use Sonata\AdminBundle\Datagrid\Pager as BasePager;

/**
 * Doctrine pager class.
 *
 * @author Jonathan H. Wage <jonwage@gmail.com>
 *
 * @phpstan-extends BasePager<ProxyQueryInterface>
 */
final class Pager extends BasePager
{
    /**
     * @var int
     */
    private $resultsCount = 0;

    public function getCurrentPageResults(): iterable
    {
        $query = $this->getQuery();
        if (null === $query) {
            throw new \LogicException('The pager need a query to display results');
        }

        return $query->execute();
    }

    public function countResults(): int
    {
        return $this->resultsCount;
    }

    public function init(): void
    {
        $query = $this->getQuery();
        if (null === $query) {
            throw new \LogicException('The pager need a query to be initialised');
        }

        $this->resultsCount = \count($query->execute());

        $query->setFirstResult(null);
        $query->setMaxResults(null);

        if (0 === $this->getPage() || 0 === $this->getMaxPerPage() || 0 === $this->countResults()) {
            $this->setLastPage(0);
        } else {
            $offset = ($this->getPage() - 1) * $this->getMaxPerPage();

            $this->setLastPage((int) ceil($this->countResults() / $this->getMaxPerPage()));

            $query->setFirstResult($offset);
            $query->setMaxResults($this->getMaxPerPage());
        }
    }
}
