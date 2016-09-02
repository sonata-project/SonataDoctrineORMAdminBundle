<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Guesser;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sonata\AdminBundle\Model\ModelManagerInterface;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;

class FilterTypeGuesser extends AbstractTypeGuesser
{
    /**
     * {@inheritdoc}
     */
    public function guessType($class, $property, ModelManagerInterface $modelManager)
    {
        if (!$ret = $this->getParentMetadataForProperty($class, $property, $modelManager)) {
            return false;
        }

        $options = array(
            'field_type' => null,
            'field_options' => array(),
            'options' => array(),
        );

        list($metadata, $propertyName, $parentAssociationMappings) = $ret;

        $options['parent_association_mappings'] = $parentAssociationMappings;

        if ($metadata->hasAssociation($propertyName)) {
            $mapping = $metadata->getAssociationMapping($propertyName);

            switch ($mapping['type']) {
                case ClassMetadataInfo::ONE_TO_ONE:
                case ClassMetadataInfo::ONE_TO_MANY:
                case ClassMetadataInfo::MANY_TO_ONE:
                case ClassMetadataInfo::MANY_TO_MANY:
                    // NEXT_MAJOR: Remove ternary (when requirement of Symfony is >= 2.8)
                    $options['operator_type'] = method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')
                        ? 'Sonata\CoreBundle\Form\Type\EqualType'
                        : 'sonata_type_equal';
                    $options['operator_options'] = array();

                    // NEXT_MAJOR: Remove ternary (when requirement of Symfony is >= 2.8)
                    $options['field_type'] = method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')
                        ? 'Symfony\Bridge\Doctrine\Form\Type\EntityType'
                        : 'entity';
                    $options['field_options'] = array(
                        'class' => $mapping['targetEntity'],
                    );

                    $options['field_name'] = $mapping['fieldName'];
                    $options['mapping_type'] = $mapping['type'];

                    return new TypeGuess('doctrine_orm_model', $options, Guess::HIGH_CONFIDENCE);
            }
        }

        $options['field_name'] = $metadata->fieldMappings[$propertyName]['fieldName'];

        switch ($metadata->getTypeOfField($propertyName)) {
            case 'boolean':
                // NEXT_MAJOR: Remove ternary (when requirement of Symfony is >= 2.8)
                $options['field_type'] = method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')
                    ? 'Sonata\CoreBundle\Form\Type\BooleanType'
                    : 'sonata_type_boolean';
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
            case 'integer':
            case 'bigint':
            case 'smallint':
                // NEXT_MAJOR: Remove ternary (when requirement of Symfony is >= 2.8)
                $options['field_type'] = method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')
                    ? 'Symfony\Component\Form\Extension\Core\Type\NumberType'
                    : 'number';

                return new TypeGuess('doctrine_orm_number', $options, Guess::MEDIUM_CONFIDENCE);
            case 'string':
            case 'text':
                // NEXT_MAJOR: Remove ternary (when requirement of Symfony is >= 2.8)
                $options['field_type'] = method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')
                    ? 'Symfony\Component\Form\Extension\Core\Type\TextType'
                    : 'text';

                return new TypeGuess('doctrine_orm_string', $options, Guess::MEDIUM_CONFIDENCE);
            case 'time':
                return new TypeGuess('doctrine_orm_time', $options, Guess::HIGH_CONFIDENCE);
            default:
                return new TypeGuess('doctrine_orm_string', $options, Guess::LOW_CONFIDENCE);
        }
    }
}
