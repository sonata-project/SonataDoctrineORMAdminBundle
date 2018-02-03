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

use Doctrine\ORM\Query\AST\Functions\IdentityFunction;
use Doctrine\ORM\Query\AST\OrderByClause;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\SelectExpression;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\TreeWalkerAdapter;

/**
 * Finds all PathExpressions in an AST's OrderByClause, and ensures that
 * the referenced fields are present in the SelectClause of the passed AST.
 *
 * Inspired by Doctrine\ORM\Tools\Pagination classes.
 *
 * @author Dariusz Markowicz <dmarkowicz77@gmail.com>
 */
final class OrderByToSelectWalker extends TreeWalkerAdapter
{
    public function walkSelectStatement(SelectStatement $AST)
    {
        if (!$AST->orderByClause instanceof OrderByClause) {
            return;
        }

        // Get a map of referenced identifiers to field names.
        $selects = [];
        foreach ($AST->orderByClause->orderByItems as $item) {
            if (!$item->expression instanceof PathExpression) {
                continue;
            }

            $pathExpression = $item->expression;
            $idVar = $pathExpression->identificationVariable;
            $field = $pathExpression->field;
            if (!isset($selects[$idVar])) {
                $selects[$idVar] = [];
            }
            $selects[$idVar][$field] = $pathExpression;
        }

        // Loop the select clause of the AST and exclude items from $selects
        // that are already being selected in the query.
        foreach ($AST->selectClause->selectExpressions as $selectExpression) {
            if ($selectExpression instanceof SelectExpression) {
                $idVar = $selectExpression->expression;
                if ($idVar instanceof IdentityFunction) {
                    $idVar = $idVar->pathExpression->identificationVariable;
                }
                if (!is_string($idVar)) {
                    continue;
                }
                $field = $selectExpression->fieldIdentificationVariable;
                if (null === $field) {
                    // No need to add this select, as we're already fetching the whole object.
                    unset($selects[$idVar]);
                } else {
                    unset($selects[$idVar][$field]);
                }
            }
        }

        // Add select items which were not excluded to the AST's select clause.
        foreach ($selects as $idVar => $fields) {
            foreach ($fields as $field => $expression) {
                $AST->selectClause->selectExpressions[] = new SelectExpression(
                    $this->createSelectExpressionItem($expression), null, true
                );
            }
        }
    }

    /**
     * Retrieve either an IdentityFunction (IDENTITY(u.assoc)) or a state field (u.name).
     *
     * @return IdentityFunction|PathExpression
     */
    private function createSelectExpressionItem(PathExpression $pathExpression)
    {
        if (PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION === $pathExpression->type) {
            $identity = new IdentityFunction('identity');

            $identity->pathExpression = clone $pathExpression;

            return $identity;
        }

        return clone $pathExpression;
    }
}
