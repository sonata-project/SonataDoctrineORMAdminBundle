<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Builder;

use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\Datagrid;
use Sonata\AdminBundle\Builder\DatagridBuilderInterface;
use Sonata\AdminBundle\Guesser\TypeGuesserInterface;
use Sonata\AdminBundle\Filter\FilterFactoryInterface;
use Sonata\AdminBundle\Datagrid\PagerInterface;

use Sonata\DoctrineORMAdminBundle\Datagrid\Pager;
use Symfony\Component\Form\FormFactory;

class DatagridBuilder implements DatagridBuilderInterface
{
    protected $filterFactory;

    protected $formFactory;

    protected $guesser;

    protected $pager;

    /**
     * @param \Symfony\Component\Form\FormFactory               $formFactory
     * @param \Sonata\AdminBundle\Filter\FilterFactoryInterface $filterFactory
     * @param \Sonata\AdminBundle\Guesser\TypeGuesserInterface  $guesser
     */
    public function __construct(FormFactory $formFactory, FilterFactoryInterface $filterFactory, TypeGuesserInterface $guesser)
    {
        $this->formFactory   = $formFactory;
        $this->filterFactory = $filterFactory;
        $this->guesser       = $guesser;
    }

    /**
     * @param \Sonata\AdminBundle\Datagrid\PagerInterface $pager
     * @return void
     */
    public function setPager(PagerInterface $pager)
    {
        $this->pager = $pager;
    }

    /**
     * @return \Sonata\AdminBundle\Datagrid\PagerInterface
     */
    public function getPager()
    {
        if (null === $this->pager) {
            $this->pager = new Pager();
        }

        return $this->pager;
    }

    /**
     * @param \Sonata\AdminBundle\Admin\AdminInterface            $admin
     * @param \Sonata\AdminBundle\Admin\FieldDescriptionInterface $fieldDescription
     *
     * @return void
     */
    public function fixFieldDescription(AdminInterface $admin, FieldDescriptionInterface $fieldDescription)
    {
        // set default values
        $fieldDescription->setAdmin($admin);

        if ($admin->getModelManager()->hasMetadata($admin->getClass())) {
            list($metadata, $lastPropertyName, $parentAssociationMappings) = $admin->getModelManager()->getParentMetadataForProperty($admin->getClass(), $fieldDescription->getName());

            // set the default field mapping
            if (isset($metadata->fieldMappings[$lastPropertyName])) {
                $fieldDescription->setOption('field_mapping', $fieldDescription->getOption('field_mapping', $metadata->fieldMappings[$lastPropertyName]));
            }

            // set the default association mapping
            if (isset($metadata->associationMappings[$lastPropertyName])) {
                $fieldDescription->setOption('association_mapping', $fieldDescription->getOption('association_mapping', $metadata->associationMappings[$lastPropertyName]));
            }

            $fieldDescription->setOption('parent_association_mappings', $fieldDescription->getOption('parent_association_mappings', $parentAssociationMappings));
        }

        $fieldDescription->setOption('code', $fieldDescription->getOption('code', $fieldDescription->getName()));
        $fieldDescription->setOption('name', $fieldDescription->getOption('name', $fieldDescription->getName()));
    }

    /**
     * @param \Sonata\AdminBundle\Datagrid\DatagridInterface      $datagrid
     * @param null                                                $type
     * @param \Sonata\AdminBundle\Admin\FieldDescriptionInterface $fieldDescription
     * @param \Sonata\AdminBundle\Admin\AdminInterface            $admin
     *
     * @return void
     */
    public function addFilter(DatagridInterface $datagrid, $type = null, FieldDescriptionInterface $fieldDescription, AdminInterface $admin)
    {
        if ($type == null) {
            $guessType = $this->guesser->guessType($admin->getClass(), $fieldDescription->getName(), $admin->getModelManager());

            $type = $guessType->getType();

            $fieldDescription->setType($type);

            $options = $guessType->getOptions();

            foreach ($options as $name => $value) {
                if (is_array($value)) {
                    $fieldDescription->setOption($name, array_merge($value, $fieldDescription->getOption($name, array())));
                } else {
                    $fieldDescription->setOption($name, $fieldDescription->getOption($name, $value));
                }
            }
        } else {
            $fieldDescription->setType($type);
        }

        $this->fixFieldDescription($admin, $fieldDescription);
        $admin->addFilterFieldDescription($fieldDescription->getName(), $fieldDescription);

        $fieldDescription->mergeOption('field_options', array('required' => false));
        $filter = $this->filterFactory->create($fieldDescription->getName(), $type, $fieldDescription->getOptions());

        if (!$filter->getLabel()) {
            $filter->setLabel($admin->getLabelTranslatorStrategy()->getLabel($fieldDescription->getName(), 'filter', 'label'));
        }

        $datagrid->addFilter($filter);

    }

    /**
     * @param \Sonata\AdminBundle\Admin\AdminInterface $admin
     * @param array                                    $values
     *
     * @return \Sonata\AdminBundle\Datagrid\DatagridInterface
     */
    public function getBaseDatagrid(AdminInterface $admin, array $values = array())
    {
        $pager = $this->getPager();
        $pager->setCountColumn($admin->getModelManager()->getIdentifierFieldNames($admin->getClass()));

        $formBuilder = $this->formFactory->createNamedBuilder('filter', 'form', array(), array('csrf_protection' => false));

        return new Datagrid($admin->createQuery(), $admin->getList(), $pager, $formBuilder, $values);
    }
}
