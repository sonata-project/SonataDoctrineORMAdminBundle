<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Tests\Filter;

use Sonata\DoctrineORMAdminBundle\Filter\Filter;

class QueryBuilder
{
    public $parameters = array();

    public $query = array();

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function setParameter($name, $value)
    {
        $this->parameters[$name] = $value;
    }

    /**
     * @param string $query
     */
    public function andWhere($query)
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
     * @param  string $name
     * @param  string $value
     * @return string
     */
    public function in($name, $value)
    {
        $this->query[] = 'in_'.$name;

        if (is_array($value)) {
            return sprintf('%s IN ("%s")', $name, implode(',', $value));
        }

        return sprintf('%s IN %s', 'in_'.$name, $value);
    }

    /**
     * @return string
     */
    public function getRootAlias()
    {
        return 'o';
    }

    /**
     * @param string $parameter
     * @param string $alias
     */
    public function leftJoin($parameter, $alias)
    {
        $this->query[] = $parameter;
    }
}
