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

namespace Sonata\DoctrineORMAdminBundle\Tests\Filter;

use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Orx;

class QueryBuilder
{
    public $parameters = [];

    public $query = [];

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function setParameter($name, $value): void
    {
        $this->parameters[$name] = $value;
    }

    /**
     * @param string $query
     */
    public function andWhere($query): void
    {
        $this->query[] = $query;
    }

    /**
     * @return QueryBuilder
     */
    public function expr()
    {
        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @return string
     */
    public function in($name, $value)
    {
        $this->query[] = 'in_'.$name;

        if (\is_array($value)) {
            return sprintf('%s IN ("%s")', $name, implode(',', $value));
        }

        return sprintf('%s IN %s', 'in_'.$name, $value);
    }

    public function getDQLPart($queryPart)
    {
        return [];
    }

    /**
     * @return string
     */
    public function getRootAlias()
    {
        return current(($this->getRootAliases()));
    }

    /**
     * @param string $parameter
     * @param string $alias
     */
    public function leftJoin($parameter, $alias): void
    {
        $this->query[] = $parameter;
    }

    /**
     * @return Orx
     */
    public function orX($x = null)
    {
        return new Orx(\func_get_args());
    }

    public function andX($x = null): Andx
    {
        return new Andx(\func_get_args());
    }

    /**
     * @param string $alias
     * @param string $parameter
     *
     * @return string
     */
    public function neq($alias, $parameter)
    {
        return sprintf('%s <> %s', $alias, $parameter);
    }

    /**
     * @param string $queryPart
     *
     * @return string
     */
    public function isNull($queryPart)
    {
        return $queryPart.' IS NULL';
    }

    public function isNotNull(string $queryPart): string
    {
        return $queryPart.' IS NOT NULL';
    }

    /**
     * @param string $alias
     * @param string $parameter
     *
     * @return string
     */
    public function notIn($alias, $parameter)
    {
        return sprintf('%s NOT IN %s', $alias, $parameter);
    }

    /**
     * @return array
     */
    public function getAllAliases()
    {
        return $this->getRootAliases();
    }

    /**
     * @return array
     */
    public function getRootAliases()
    {
        return ['o'];
    }
}
