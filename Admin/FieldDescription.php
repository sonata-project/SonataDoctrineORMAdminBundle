<?php

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

class FieldDescription extends BaseFieldDescription
{
    public function __construct()
    {
        $this->parentAssociationMappings = array();
    }

    /**
     * {@inheritdoc}
     */
    public function setAssociationMapping($associationMapping)
    {
        if (!is_array($associationMapping)) {
            throw new \RuntimeException('The association mapping must be an array');
        }

        $this->associationMapping = $associationMapping;

        $this->type = $this->type ?: $associationMapping['type'];
        $this->mappingType = $this->mappingType ?: $associationMapping['type'];
        $this->fieldName = $associationMapping['fieldName'];
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetEntity()
    {
        if ($this->associationMapping) {
            return $this->associationMapping['targetEntity'];
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function setFieldMapping($fieldMapping)
    {
        if (!is_array($fieldMapping)) {
            throw new \RuntimeException('The field mapping must be an array');
        }

        $this->fieldMapping = $fieldMapping;

        $this->type = $this->type ?: $fieldMapping['type'];
        $this->mappingType = $this->mappingType ?: $fieldMapping['type'];
        $this->fieldName = $this->fieldName ?: $fieldMapping['fieldName'];
    }

    /**
     * {@inheritdoc}
     */
    public function setParentAssociationMappings(array $parentAssociationMappings)
    {
        foreach ($parentAssociationMappings as $parentAssociationMapping) {
            if (!is_array($parentAssociationMapping)) {
                throw new \RuntimeException('An association mapping must be an array');
            }
        }

        $this->parentAssociationMappings = $parentAssociationMappings;
    }

    /**
     * {@inheritdoc}
     */
    public function isIdentifier()
    {
        return isset($this->fieldMapping['id']) ? $this->fieldMapping['id'] : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($object)
    {
        foreach ($this->parentAssociationMappings as $parentAssociationMapping) {
            $object = $this->getFieldValue($object, $parentAssociationMapping['fieldName']);
        }

        $fieldMapping = $this->getFieldMapping();
        // Support embedded object for mapping
        // Ref: http://doctrine-orm.readthedocs.io/projects/doctrine-orm/en/latest/tutorials/embeddables.html
        if (isset($fieldMapping['declaredField'])) {
            $object = $this->getFieldValue($object, $fieldMapping['declaredField']);
        }

        return $this->getFieldValue($object, $this->fieldName);
    }
}
