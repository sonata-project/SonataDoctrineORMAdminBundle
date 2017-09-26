<?php

namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Query;

use Doctrine\ORM\Query\SqlWalker;

final class FooWalker extends SqlWalker
{
    public function walkOrderByClause($orderByClause)
    {
        return str_replace(' ASC', ' DESC', parent::walkOrderByClause($orderByClause));
    }
}
