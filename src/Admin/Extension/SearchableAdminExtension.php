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

namespace Sonata\DoctrineORMAdminBundle\Admin\Extension;

use Sonata\AdminBundle\Admin\AbstractAdminExtension;
use Sonata\AdminBundle\Admin\SearchableAdminInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * @phpstan-extends AbstractAdminExtension<object>
 */
final class SearchableAdminExtension extends AbstractAdminExtension
{
    public function configureDatagridFilters(DatagridMapper $filter): void
    {
        $admin = $filter->getAdmin();

        if (!$admin instanceof SearchableAdminInterface) {
            return;
        }

        if (\count($admin->getSearchFields()) <= 0) {
            return;
        }

        $filter
            ->add('_search', CallbackFilter::class, [
                'callback' => function (ProxyQueryInterface $queryBuilder, $alias, $field, $value) use ($admin): bool {
                    if (!$value['value']) {
                        return false;
                    }

                    $this->prepareSearchQuery($queryBuilder, $admin->getSearchFields(), $value['value']);

                    return true;
                },
                'field_type' => TextType::class,
            ])
        ;
    }

    /**
     * @param string[] $searchfields
     */
    private function prepareSearchQuery(ProxyQueryInterface $query, array $searchfields, string $text): ProxyQueryInterface
    {
        $conditions = [];

        foreach ($searchfields as $searchfield) {
            $fields = explode('.', $searchfield);
            $field = end($fields);

            $mappings = [];
            $fieldsCount = \count($fields);
            for ($i = 0; $i < $fieldsCount - 1; ++$i) {
                $mappings[] = ['fieldName' => $fields[$i]];
            }
            $alias = $query->entityJoin($mappings);

            $conditions[] = sprintf('%s.%s LIKE :search_string', $alias, $field);
        }

        if ([] !== $conditions) {
            $query->andWhere('('.implode(' or ', $conditions).')');
            $query->setParameter('search_string', '%'.$text.'%');
        }

        return $query;
    }
}
