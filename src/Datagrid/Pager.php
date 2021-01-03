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
use Sonata\AdminBundle\Datagrid\Pager as BasePager;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;

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
     * @var string[]
     */
    protected $countColumn = ['id'];

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

    public function getResults($hydrationMode = Query::HYDRATE_OBJECT)
    {
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

        $this->setResultsCount($this->computeNbResult('sonata_deprecation_mute'));

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
     * @return string[]
     */
    public function getCountColumn()
    {
        return $this->countColumn;
    }

    /**
     * @return string[]
     */
    public function setCountColumn(array $countColumn)
    {
        return $this->countColumn = $countColumn;
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

    private function countCompositePrimaryKey(ProxyQueryInterface $countQuery): void
    {
        $rootAliases = current($countQuery->getRootAliases());
        $countQuery->setParameter('concat_separator', self::CONCAT_SEPARATOR);

        $columns = $rootAliases.'.'.implode(', :concat_separator, '.$rootAliases.'.', $this->getCountColumn());

        $countQuery->select(sprintf(
            'count(%s concat(%s)) as cnt',
            $countQuery instanceof ProxyQuery && !$countQuery->isDistinct() ? null : 'DISTINCT',
            $columns
        ));
    }

    private function countSinglePrimaryKey(ProxyQueryInterface $countQuery): void
    {
        $countQuery->select(sprintf(
            'count(%s %s.%s) as cnt',
            $countQuery instanceof ProxyQuery && !$countQuery->isDistinct() ? null : 'DISTINCT',
            current($countQuery->getRootAliases()),
            current($this->getCountColumn())
        ));
    }

    private function computeResultsCount(): int
    {
        $countQuery = clone $this->getQuery();

        // NEXT_MAJOR: Remove this code.
        if (\count($this->getParameters('sonata_deprecation_mute')) > 0) {
            $countQuery->setParameters($this->getParameters('sonata_deprecation_mute'));
        }

        if (\count($this->getCountColumn()) > 1) {
            $this->countCompositePrimaryKey($countQuery);
        } else {
            $this->countSinglePrimaryKey($countQuery);
        }

        return array_sum(array_column(
            $countQuery->resetDQLPart('orderBy')->getQuery()->getResult(Query::HYDRATE_SCALAR),
            'cnt'
        ));
    }

    private function setResultsCount(int $count): void
    {
        $this->resultsCount = $count;
        // NEXT_MAJOR: Remove this line.
        $this->setNbResults($count, 'sonata_deprecation_mute');
    }
}
