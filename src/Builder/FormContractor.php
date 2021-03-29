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
use Sonata\AdminBundle\Builder\AbstractFormContractor;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * @final since sonata-project/doctrine-orm-admin-bundle 3.24
 */
class FormContractor extends AbstractFormContractor
{
    /**
     * NEXT_MAJOR: remove this property.
     *
     * @deprecated since sonata-project/doctrine-orm-admin-bundle 3.0.4, to be removed in 4.0
     *
     * @var FormFactoryInterface
     */
    protected $fieldFactory;

    public function fixFieldDescription(AdminInterface $admin, FieldDescriptionInterface $fieldDescription)
    {
        // NEXT_MAJOR: Remove this block.
        if ($admin->getModelManager()->hasMetadata($admin->getClass(), 'sonata_deprecation_mute')) {
            $metadata = $admin->getModelManager()->getMetadata($admin->getClass(), 'sonata_deprecation_mute');

            // set the default field mapping
            if (isset($metadata->fieldMappings[$fieldDescription->getName()])) {
                $fieldDescription->setFieldMapping($metadata->fieldMappings[$fieldDescription->getName()]);
            }

            // set the default association mapping
            if (isset($metadata->associationMappings[$fieldDescription->getName()])) {
                $fieldDescription->setAssociationMapping($metadata->associationMappings[$fieldDescription->getName()]);
            }
        }

        if (!$fieldDescription->getType()) {
            throw new \RuntimeException(sprintf(
                'Please define a type for field `%s` in `%s`',
                $fieldDescription->getName(),
                \get_class($admin)
            ));
        }

        // NEXT_MAJOR: Remove this line.
        $fieldDescription->setAdmin($admin);

        parent::fixFieldDescription($admin, $fieldDescription);
    }

    // NEXT_MAJOR: Remove this method.
    protected function hasAssociation(FieldDescriptionInterface $fieldDescription): bool
    {
        if (method_exists($fieldDescription, 'describesAssociation')) {
            return $fieldDescription->describesAssociation();
        }

        return \in_array($fieldDescription->getMappingType(), [
            ClassMetadata::ONE_TO_MANY,
            ClassMetadata::MANY_TO_MANY,
            ClassMetadata::MANY_TO_ONE,
            ClassMetadata::ONE_TO_ONE,
        ], true);
    }

    // NEXT_MAJOR: Remove this method.
    protected function hasSingleValueAssociation(FieldDescriptionInterface $fieldDescription): bool
    {
        if (method_exists($fieldDescription, 'describesSingleValuedAssociation')) {
            return $fieldDescription->describesSingleValuedAssociation();
        }

        return \is_int($fieldDescription->getMappingType()) && $fieldDescription->getMappingType() === ($fieldDescription->getMappingType() & ClassMetadata::TO_ONE);
    }
}
