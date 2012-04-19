<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Guesser;

use Sonata\AdminBundle\Guesser\TypeGuesserInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\MappingException;

class FilterTypeGuesser implements TypeGuesserInterface
{
    protected $registry;

    private $cache;

    public function __construct(RegistryInterface $registry)
    {
        $this->registry = $registry;
        $this->cache = array();
    }

    /**
     * @param string $class
     * @param string $property
     * @return TypeGuess
     */
    public function guessType($class, $property)
    {
        if (!$ret = $this->getParentMetadataForProperty($class, $property)) {
            return false;
        }

        $options = array(
            'field_type'     => false,
            'field_options'  => array(),
            'options'        => array(),
        );

        list($metadata, $propertyName, $parentAssociationMappings) = $ret;

        $options['parent_association_mappings'] = $parentAssociationMappings;

        if ($metadata->hasAssociation($propertyName)) {
            $multiple = $metadata->isCollectionValuedAssociation($propertyName);
            $mapping = $metadata->getAssociationMapping($propertyName);

            switch ($mapping['type']) {
                case ClassMetadataInfo::ONE_TO_ONE:
                case ClassMetadataInfo::ONE_TO_MANY:
                case ClassMetadataInfo::MANY_TO_ONE:
                case ClassMetadataInfo::MANY_TO_MANY:

                    $options['operator_type'] = 'sonata_type_equal';
                    $options['operator_options'] = array();

                    $options['field_type'] = 'entity';
                    $options['field_options'] = array(
                        'class' => $mapping['targetEntity']
                    );

                    $options['field_name'] = $mapping['fieldName'];
                    $options['mapping_type'] = $mapping['type'];

                    return new TypeGuess('doctrine_orm_model', $options, Guess::HIGH_CONFIDENCE);
            }
        }

        $options['field_name'] = $metadata->fieldMappings[$propertyName]['fieldName'];

        switch ($metadata->getTypeOfField($propertyName)) {
            case 'boolean':
                $options['field_type'] = 'sonata_type_boolean';
                $options['field_options'] = array();

                return new TypeGuess('doctrine_orm_boolean', $options, Guess::HIGH_CONFIDENCE);
            case 'datetime':
            case 'vardatetime':
            case 'datetimetz':
                return new TypeGuess('doctrine_orm_datetime', $options, Guess::HIGH_CONFIDENCE);
            case 'date':
                return new TypeGuess('doctrine_orm_date', $options, Guess::HIGH_CONFIDENCE);
            case 'decimal':
            case 'float':
                return new TypeGuess('doctrine_orm_number', $options, Guess::MEDIUM_CONFIDENCE);
            case 'integer':
            case 'bigint':
            case 'smallint':
                $options['field_type'] = 'number';
                $options['field_options'] = array(
                    'csrf_protection' => false
                );

                return new TypeGuess('doctrine_orm_number', $options, Guess::MEDIUM_CONFIDENCE);
            case 'string':
            case 'text':
                $options['field_type'] = 'text';

                return new TypeGuess('doctrine_orm_string', $options, Guess::MEDIUM_CONFIDENCE);
            case 'time':
                return new TypeGuess('doctrine_orm_time', $options, Guess::HIGH_CONFIDENCE);
            default:
                return new TypeGuess('doctrine_orm_string', $options, Guess::LOW_CONFIDENCE);
        }
    }

    protected function getMetadata($class)
    {
        if (array_key_exists($class, $this->cache)) {
            return $this->cache[$class];
        }

        $this->cache[$class] = null;
        foreach ($this->registry->getEntityManagers() as $em) {
            try {
                return $this->cache[$class] = $em->getClassMetadata($class);
            } catch (MappingException $e) {
                // not an entity or mapped super class
            }
        }
    }

    protected function getParentMetadataForProperty($baseClass, $propertyFullName)
    {
        $nameElements = explode('.', $propertyFullName);
        $lastPropertyName = array_pop($nameElements);
        $class = $baseClass;
        $parentAssociationMappings = array();

        foreach($nameElements as $nameElement){
            if (!$metadata = $this->getMetadata($class)) {
                return null;
            }

            $class = $metadata->associationMappings[$nameElement]['targetEntity'];

            if (!$metadata->hasAssociation($nameElement)) {
                return null;
            }

            $mapping = $metadata->getAssociationMapping($nameElement);

            switch ($mapping['type']) {
                case ClassMetadataInfo::ONE_TO_ONE:
                case ClassMetadataInfo::ONE_TO_MANY:
                case ClassMetadataInfo::MANY_TO_ONE:
                case ClassMetadataInfo::MANY_TO_MANY:
                    $parentAssociationMappings[] = $mapping;

                    break;

                default:
                    return null;
            }
        }

        return array($this->getMetadata($class), $lastPropertyName, $parentAssociationMappings);
    }
}