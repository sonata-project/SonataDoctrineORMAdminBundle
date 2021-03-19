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

namespace Sonata\DoctrineORMAdminBundle\Builder;

use Doctrine\ORM\Mapping\ClassMetadata;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Builder\DatagridBuilderInterface;
use Sonata\AdminBundle\Datagrid\Datagrid;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\PagerInterface;
use Sonata\AdminBundle\Datagrid\SimplePager;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\FieldDescription\TypeGuesserInterface;
use Sonata\AdminBundle\Filter\FilterFactoryInterface;
use Sonata\DoctrineORMAdminBundle\Datagrid\Pager;
use Sonata\DoctrineORMAdminBundle\Filter\ModelAutocompleteFilter;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;

final class DatagridBuilder implements DatagridBuilderInterface
{
    /**
     * @var FilterFactoryInterface
     */
    private $filterFactory;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var TypeGuesserInterface
     */
    private $guesser;

    /**
     * @var bool
     */
    private $csrfTokenEnabled;

    public function __construct(
        FormFactoryInterface $formFactory,
        FilterFactoryInterface $filterFactory,
        TypeGuesserInterface $guesser,
        bool $csrfTokenEnabled = true
    ) {
        $this->formFactory = $formFactory;
        $this->filterFactory = $filterFactory;
        $this->guesser = $guesser;
        $this->csrfTokenEnabled = $csrfTokenEnabled;
    }

    public function fixFieldDescription(AdminInterface $admin, FieldDescriptionInterface $fieldDescription): void
    {
        // NEXT_MAJOR: Remove this line.
        $fieldDescription->setAdmin($admin);

        if ([] !== $fieldDescription->getFieldMapping()) {
            $fieldDescription->setOption('field_mapping', $fieldDescription->getOption('field_mapping', $fieldDescription->getFieldMapping()));

            if ('string' === $fieldDescription->getFieldMapping()['type']) {
                $fieldDescription->setOption('global_search', $fieldDescription->getOption('global_search', true)); // always search on string field only
            }
        }

        if ([] !== $fieldDescription->getAssociationMapping()) {
            $fieldDescription->setOption('association_mapping', $fieldDescription->getOption('association_mapping', $fieldDescription->getAssociationMapping()));
        }

        if ([] !== $fieldDescription->getParentAssociationMappings()) {
            $fieldDescription->setOption('parent_association_mappings', $fieldDescription->getOption('parent_association_mappings', $fieldDescription->getParentAssociationMappings()));
        }

        $fieldDescription->setOption('field_name', $fieldDescription->getOption('field_name', $fieldDescription->getFieldName()));

        $fieldDescription->mergeOption('field_options', ['required' => false]);

        if (ModelAutocompleteFilter::class === $fieldDescription->getType()) {
            $fieldDescription->mergeOption('field_options', [
                'class' => $fieldDescription->getTargetModel(),
                'model_manager' => $fieldDescription->getAdmin()->getModelManager(),
                'admin_code' => $admin->getCode(),
                'context' => 'filter',
            ]);
        }

        if (\in_array($fieldDescription->getMappingType(), [
            ClassMetadata::ONE_TO_MANY,
            ClassMetadata::MANY_TO_MANY,
            ClassMetadata::MANY_TO_ONE,
            ClassMetadata::ONE_TO_ONE,
        ], true)) {
            $admin->attachAdminClass($fieldDescription);
        }
    }

    public function addFilter(DatagridInterface $datagrid, ?string $type, FieldDescriptionInterface $fieldDescription, AdminInterface $admin): void
    {
        if (null === $type) {
            $guessType = $this->guesser->guess($fieldDescription);

            $type = $guessType->getType();
            $fieldDescription->setType($type);

            foreach ($guessType->getOptions() as $name => $value) {
                if (\is_array($value)) {
                    $fieldDescription->setOption($name, array_merge($value, $fieldDescription->getOption($name, [])));
                } else {
                    $fieldDescription->setOption($name, $fieldDescription->getOption($name, $value));
                }
            }
        } else {
            $fieldDescription->setType($type);
        }

        $this->fixFieldDescription($admin, $fieldDescription);
        $admin->addFilterFieldDescription($fieldDescription->getName(), $fieldDescription);

        $filter = $this->filterFactory->create($fieldDescription->getName(), $type, $fieldDescription->getOptions());
        $datagrid->addFilter($filter);
    }

    public function getBaseDatagrid(AdminInterface $admin, array $values = []): DatagridInterface
    {
        $pager = $this->getPager($admin->getPagerType());

        $defaultOptions = ['validation_groups' => false];
        if ($this->csrfTokenEnabled) {
            $defaultOptions['csrf_protection'] = false;
        }

        $formBuilder = $this->formFactory->createNamedBuilder('filter', FormType::class, [], $defaultOptions);

        return new Datagrid($admin->createQuery(), $admin->getList(), $pager, $formBuilder, $values);
    }

    /**
     * Get pager by pagerType.
     *
     * @throws \RuntimeException If invalid pager type is set
     */
    private function getPager(string $pagerType): PagerInterface
    {
        switch ($pagerType) {
            case Pager::TYPE_DEFAULT:
                return new Pager();

            case Pager::TYPE_SIMPLE:
                return new SimplePager();

            default:
                throw new \RuntimeException(sprintf('Unknown pager type "%s".', $pagerType));
        }
    }
}
