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

namespace Sonata\DoctrineORMAdminBundle\Admin;

use Sonata\AdminBundle\Admin\BaseFieldDescription;

/**
 * @final since sonata-project/doctrine-orm-admin-bundle 3.24
 */
class FieldDescription extends BaseFieldDescription
{
    /**
     * NEXT_MAJOR: Change visibility to protected.
     */
    public function setAssociationMapping(array $associationMapping): void
    {
        $this->associationMapping = $associationMapping;

        $this->type = $this->type ?: $associationMapping['type'];
        $this->mappingType = $this->mappingType ?: $associationMapping['type'];
        // NEXT_MAJOR: Remove the next line.
        $this->fieldName = $associationMapping['fieldName'];
    }

    /**
     * NEXT_MAJOR: Remove this method.
     *
     * @deprecated since sonata-project/doctrine-orm-admin-bundle 3.20 and will be removed in version 4.0. Use FieldDescription::getTargetModel() instead.
     */
    public function getTargetEntity(): ?string
    {
        @trigger_error(sprintf(
            'Method %s() is deprecated since sonata-project/doctrine-orm-admin-bundle 3.20 and will be removed in version 4.0.'
            .' Use %s::getTargetModel() instead.',
            __METHOD__,
            __CLASS__
        ), E_USER_DEPRECATED);

        return $this->getTargetModel();
    }

    public function getTargetModel(): ?string
    {
        if ($this->associationMapping) {
            return $this->associationMapping['targetEntity'];
        }

        return null;
    }

    /**
     * NEXT_MAJOR: Change visibility to protected.
     */
    public function setFieldMapping(array $fieldMapping): void
    {
        $this->fieldMapping = $fieldMapping;

        $this->type = $this->type ?: $fieldMapping['type'];
        $this->mappingType = $this->mappingType ?: $fieldMapping['type'];
        // NEXT_MAJOR: Remove the next line.
        $this->fieldName = $this->fieldName ?: $fieldMapping['fieldName'];
    }

    /**
     * NEXT_MAJOR: Change visibility to protected.
     */
    public function setParentAssociationMappings(array $parentAssociationMappings): void
    {
        foreach ($parentAssociationMappings as $parentAssociationMapping) {
            if (!\is_array($parentAssociationMapping)) {
                throw new \RuntimeException('An association mapping must be an array');
            }
        }

        $this->parentAssociationMappings = $parentAssociationMappings;
    }

    public function isIdentifier(): bool
    {
        return $this->fieldMapping['id'] ?? false;
    }

    public function getValue($object)
    {
        foreach ($this->parentAssociationMappings as $parentAssociationMapping) {
            $object = $this->getFieldValue($object, $parentAssociationMapping['fieldName']);
        }

        $fieldMapping = $this->getFieldMapping();
        // Support embedded object for mapping
        // Ref: https://www.doctrine-project.org/projects/doctrine-orm/en/latest/tutorials/embeddables.html
        if (isset($fieldMapping['declaredField'])) {
            $parentFields = explode('.', $fieldMapping['declaredField']);
            foreach ($parentFields as $parentField) {
                $object = $this->getFieldValue($object, $parentField);
            }
        }

        return $this->getFieldValue($object, $this->fieldName);
    }
}
