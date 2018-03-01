<?php

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
use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\AdminBundle\Builder\DatagridBuilderInterface;
use Sonata\AdminBundle\Datagrid\Datagrid;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\PagerInterface;
use Sonata\AdminBundle\Datagrid\SimplePager;
use Sonata\AdminBundle\Filter\FilterFactoryInterface;
use Sonata\AdminBundle\Guesser\TypeGuesserInterface;
use Sonata\DoctrineORMAdminBundle\Datagrid\Pager;
use Sonata\DoctrineORMAdminBundle\Filter\ModelAutocompleteFilter;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;

class DatagridBuilder implements DatagridBuilderInterface
{
    /**
     * @var FilterFactoryInterface
     */
    protected $filterFactory;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var TypeGuesserInterface
     */
    protected $guesser;

    /**
     * @var bool
     */
    protected $csrfTokenEnabled;

    /**
     * @param bool $csrfTokenEnabled
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        FilterFactoryInterface $filterFactory,
        TypeGuesserInterface $guesser,
        $csrfTokenEnabled = true
    ) {
        $this->formFactory = $formFactory;
        $this->filterFactory = $filterFactory;
        $this->guesser = $guesser;
        $this->csrfTokenEnabled = $csrfTokenEnabled;
    }

    public function fixFieldDescription(AdminInterface $admin, FieldDescriptionInterface $fieldDescription)
    {
        // set default values
        $fieldDescription->setAdmin($admin);

        if ($admin->getModelManager()->hasMetadata($admin->getClass())) {
            list($metadata, $lastPropertyName, $parentAssociationMappings) = $admin->getModelManager()
                ->getParentMetadataForProperty($admin->getClass(), $fieldDescription->getName());

            // set the default field mapping
            if (isset($metadata->fieldMappings[$lastPropertyName])) {
                $fieldDescription->setOption(
                    'field_mapping',
                    $fieldDescription->getOption(
                        'field_mapping',
                        $fieldMapping = $metadata->fieldMappings[$lastPropertyName]
                    )
                );

                if ('string' == $fieldMapping['type']) {
                    $fieldDescription->setOption('global_search', $fieldDescription->getOption('global_search', true)); // always search on string field only
                }

                if (!empty($embeddedClasses = $metadata->embeddedClasses)
                    && isset($fieldMapping['declaredField'])
                    && array_key_exists($fieldMapping['declaredField'], $embeddedClasses)
                ) {
                    $fieldDescription->setOption(
                        'field_name',
                        $fieldMapping['fieldName']
                    );
                }
            }

            // set the default association mapping
            if (isset($metadata->associationMappings[$lastPropertyName])) {
                $fieldDescription->setOption(
                    'association_mapping',
                    $fieldDescription->getOption(
                        'association_mapping',
                        $metadata->associationMappings[$lastPropertyName]
                    )
                );
            }

            $fieldDescription->setOption(
                'parent_association_mappings',
                $fieldDescription->getOption('parent_association_mappings', $parentAssociationMappings)
            );
        }

        $fieldDescription->setOption('code', $fieldDescription->getOption('code', $fieldDescription->getName()));
        $fieldDescription->setOption('name', $fieldDescription->getOption('name', $fieldDescription->getName()));

        if (in_array($fieldDescription->getMappingType(), [
            ClassMetadata::ONE_TO_MANY,
            ClassMetadata::MANY_TO_MANY,
            ClassMetadata::MANY_TO_ONE,
            ClassMetadata::ONE_TO_ONE,
        ])) {
            $admin->attachAdminClass($fieldDescription);
        }
    }

    public function addFilter(DatagridInterface $datagrid, $type, FieldDescriptionInterface $fieldDescription, AdminInterface $admin)
    {
        if (null == $type) {
            $guessType = $this->guesser->guessType($admin->getClass(), $fieldDescription->getName(), $admin->getModelManager());

            $type = $guessType->getType();

            $fieldDescription->setType($type);

            $options = $guessType->getOptions();

            foreach ($options as $name => $value) {
                if (is_array($value)) {
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

        $fieldDescription->mergeOption('field_options', ['required' => false]);

        // NEXT_MAJOR: Check only against FQCNs when dropping support for Symfony 2.8
        if ('doctrine_orm_model_autocomplete' === $type || ModelAutocompleteFilter::class === $type) {
            $fieldDescription->mergeOption('field_options', [
                'class' => $fieldDescription->getTargetEntity(),
                'model_manager' => $fieldDescription->getAdmin()->getModelManager(),
                'admin_code' => $admin->getCode(),
                'context' => 'filter',
            ]);
        }

        $filter = $this->filterFactory->create($fieldDescription->getName(), $type, $fieldDescription->getOptions());

        if (false !== $filter->getLabel() && !$filter->getLabel()) {
            $filter->setLabel($admin->getLabelTranslatorStrategy()->getLabel($fieldDescription->getName(), 'filter', 'label'));
        }

        $datagrid->addFilter($filter);
    }

    public function getBaseDatagrid(AdminInterface $admin, array $values = [])
    {
        $pager = $this->getPager($admin->getPagerType());

        $pager->setCountColumn($admin->getModelManager()->getIdentifierFieldNames($admin->getClass()));

        $defaultOptions = [];
        if ($this->csrfTokenEnabled) {
            $defaultOptions['csrf_protection'] = false;
        }

        $formBuilder = $this->formFactory->createNamedBuilder('filter', FormType::class, [], $defaultOptions);

        return new Datagrid($admin->createQuery(), $admin->getList(), $pager, $formBuilder, $values);
    }

    /**
     * Get pager by pagerType.
     *
     * @param string $pagerType
     *
     * @throws \RuntimeException If invalid pager type is set
     *
     * @return PagerInterface
     */
    protected function getPager($pagerType)
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
