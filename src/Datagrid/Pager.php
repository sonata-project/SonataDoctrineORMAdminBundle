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
 *
 * @phpstan-extends BasePager<ProxyQueryInterface>
 */
class Pager extends BasePager
{
    /**
     * NEXT_MAJOR: remove this property.
     *
     * @deprecated since sonata-project/doctrine-orm-admin-bundle 3.x and will be removed in 4.0
     *
     * Use separator in CONCAT() function for correct determinate similar records.
     */
    public const CONCAT_SEPARATOR = '|';

    /**
     * NEXT_MAJOR: remove this property.
     *
     * @deprecated since sonata-project/doctrine-orm-admin-bundle 2.4 and will be removed in 4.0
     */
    protected $queryBuilder = null;

    /**
     * @var int
     */
    private $resultsCount = 0;

    /**
     * NEXT_MAJOR: remove this method.
     *
     * @deprecated since sonata-project/doctrine-orm-admin-bundle 3.27
     *
     * @return int
     */
    public function computeNbResult()
    {
        if ('sonata_deprecation_mute' !== (\func_get_args()[0] ?? null)) {
            @trigger_error(sprintf(
                'The %s() method is deprecated since sonata-project/doctrine-orm-admin-bundle 3.27 and will be removed in 4.0.',
                __METHOD__,
            ), E_USER_DEPRECATED);
        }

        return $this->computeResultsCount();
    }

    public function getCurrentPageResults(): iterable
    {
        return $this->getQuery()->execute();
    }

    /**
     * NEXT_MAJOR: remove this method.
     *
     * @deprecated since sonata-project/doctrine-orm-admin-bundle 3.27
     */
    public function getResults($hydrationMode = Query::HYDRATE_OBJECT)
    {
        @trigger_error(sprintf(
            'The %s() method is deprecated since sonata-project/doctrine-orm-admin-bundle 3.27 and will be removed in 4.0. Use "getCurrentPageResults()" instead.',
            __METHOD__,
        ), E_USER_DEPRECATED);

        return $this->getQuery()->execute([], $hydrationMode);
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function countResults(): int
    {
        // NEXT_MAJOR: just return "$this->resultsCount" directly.
        $deprecatedCount = $this->getNbResults('sonata_deprecation_mute');

        if ($deprecatedCount === $this->resultsCount) {
            return $this->resultsCount;
        }

        @trigger_error(sprintf(
            'Relying on the protected property "%s::$nbResults" and its getter/setter is deprecated since sonata-project/doctrine-orm-admin-bundle 3.27 and will fail 4.0. Use "countResults()" and "setResultsCount()" instead.',
            self::class,
        ), E_USER_DEPRECATED);

        return $deprecatedCount;
    }

    /**
     * NEXT_MAJOR: remove this method.
     *
     * @deprecated since sonata-project/doctrine-orm-admin-bundle 3.27
     *
     * @return int
     */
    public function getNbResults()
    {
        if ('sonata_deprecation_mute' !== (\func_get_args()[0] ?? null)) {
            @trigger_error(sprintf(
                'The %s() method is deprecated since sonata-project/doctrine-orm-admin-bundle 3.27 and will be removed in 4.0. Use "countResults()" instead.',
                __METHOD__,
            ), E_USER_DEPRECATED);
        }

        return $this->nbResults;
    }

    public function init()
    {
        // NEXT_MAJOR: Remove this line.
        $this->resetIterator('sonata_deprecation_mute');

        // NEXT_MAJOR: Remove this line and uncomment the following one.
        $this->setResultsCount($this->computeNbResult('sonata_deprecation_mute'));
//        $this->setResultsCount($this->computeResultsCount());

        $this->getQuery()->setFirstResult(null);
        $this->getQuery()->setMaxResults(null);

        // NEXT_MAJOR: Remove this code.
        if (\count($this->getParameters('sonata_deprecation_mute')) > 0) {
            $this->getQuery()->setParameters($this->getParameters('sonata_deprecation_mute'));
        }

        if (0 === $this->getPage() || 0 === $this->getMaxPerPage() || 0 === $this->countResults()) {
            $this->setLastPage(0);
        } else {
            $offset = ($this->getPage() - 1) * $this->getMaxPerPage();

            $this->setLastPage((int) ceil($this->countResults() / $this->getMaxPerPage()));

            $this->getQuery()->setFirstResult($offset);
            $this->getQuery()->setMaxResults($this->getMaxPerPage());
        }
    }

    /**
     * NEXT_MAJOR: remove this method.
     *
     * @deprecated since sonata-project/doctrine-orm-admin-bundle 3.27
     *
     * @param int $nb
     */
    protected function setNbResults($nb)
    {
        if ('sonata_deprecation_mute' !== (\func_get_args()[1] ?? null)) {
            @trigger_error(sprintf(
                'The %s() method is deprecated since sonata-project/doctrine-orm-admin-bundle 3.27 and will be removed in 4.0. Use "setResultsCount()" instead.',
                __METHOD__,
            ), E_USER_DEPRECATED);
        }

        $this->nbResults = $nb;
        $this->resultsCount = (int) $nb;
    }

    private function computeResultsCount(): int
    {
        // NEXT_MAJOR: remove the clone.
        $query = clone $this->getQuery();

        if (!$query instanceof ProxyQueryInterface) {
            throw new \TypeError(sprintf(
                'The pager query MUST implement %s, %s provided.',
                ProxyQueryInterface::class,
                \is_object($query) ? sprintf('instance of %s', \get_class($query)) : \gettype($query)
            ));
        }

        // NEXT_MAJOR: Remove this code.
        if (\count($this->getParameters('sonata_deprecation_mute')) > 0) {
            $query->setParameters($this->getParameters('sonata_deprecation_mute'));
        }

        $paginator = new Paginator($query->getQueryBuilder());

        return \count($paginator);
    }

    private function setResultsCount(int $count): void
    {
        $this->resultsCount = $count;
        // NEXT_MAJOR: Remove this line.
        $this->setNbResults($count, 'sonata_deprecation_mute');
    }
}
