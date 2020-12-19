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
use Sonata\AdminBundle\Exception\NoValueException;

/**
 * @final since sonata-project/doctrine-orm-admin-bundle 3.24
 */
class FieldDescription extends BaseFieldDescription
{
    public function setAssociationMapping($associationMapping)
    {
        if (!\is_array($associationMapping)) {
            throw new \RuntimeException('The association mapping must be an array');
        }

        $this->associationMapping = $associationMapping;

        if (isset($fieldMapping['type'])) {
            $this->type = $this->type ?: $associationMapping['type'];
            $this->mappingType = $this->mappingType ?: $associationMapping['type'];
        }

        // NEXT_MAJOR: Remove the next line.
        $this->fieldName = $associationMapping['fieldName'];
    }

    /**
     * NEXT_MAJOR: Remove this method.
     *
     * @deprecated since sonata-project/doctrine-orm-admin-bundle 3.20 and will be removed in version 4.0. Use FieldDescription::getTargetModel() instead.
     */
    public function getTargetEntity()
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
        return $this->associationMapping['targetEntity'] ?? null;
    }

    public function setFieldMapping($fieldMapping)
    {
        if (!\is_array($fieldMapping)) {
            throw new \RuntimeException('The field mapping must be an array');
        }

        $this->fieldMapping = $fieldMapping;

        if (isset($fieldMapping['type'])) {
            $this->type = $this->type ?: $fieldMapping['type'];
            $this->mappingType = $this->mappingType ?: $fieldMapping['type'];
        }

        // NEXT_MAJOR: Remove the next line.
        $this->fieldName = $this->fieldName ?: $fieldMapping['fieldName'];
    }

    public function setParentAssociationMappings(array $parentAssociationMappings)
    {
        foreach ($parentAssociationMappings as $parentAssociationMapping) {
            if (!\is_array($parentAssociationMapping)) {
                throw new \RuntimeException('An association mapping must be an array');
            }
        }

        $this->parentAssociationMappings = $parentAssociationMappings;
    }

    public function isIdentifier()
    {
        return $this->fieldMapping['id'] ?? false;
    }

    public function getValue($object)
    {
        foreach ($this->parentAssociationMappings as $parentAssociationMapping) {
            $object = $this->getChildValue($object, $parentAssociationMapping['fieldName']);
        }

        $fieldMapping = $this->getFieldMapping();
        // Support embedded object for mapping
        // Ref: https://www.doctrine-project.org/projects/doctrine-orm/en/latest/tutorials/embeddables.html
        if (isset($fieldMapping['declaredField'])) {
            $parentFields = explode('.', $fieldMapping['declaredField']);
            foreach ($parentFields as $parentField) {
                $object = $this->getChildValue($object, $parentField);
            }
        }

        return $this->getFieldValue($object, $this->fieldName);
    }

    /**
     * @param object|null $object
     * @param string      $fieldName
     *
     * @throws NoValueException
     */
    private function getChildValue(?object $object, string $fieldName): ?object
    {
        if (null === $object) {
            return null;
        }

        $child = $this->getFieldValue($object, $fieldName);
        if (null !== $child && !is_object($child)) {
            throw new NoValueException(sprintf(
                'Unexpected value when accessing to the property "%s" on the class "%s" for the field "%s".'
                .' Expected object|null, got %s.',
                $fieldName,
                get_class($object),
                $this->getName(),
                gettype($child)
            ));
        }

        return $child;
    }
}
