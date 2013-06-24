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

use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sonata\AdminBundle\Model\ModelManagerInterface;

class TypeGuesser extends AbstractTypeGuesser
{
    /**
     * {@inheritdoc}
     */
    public function guessType($class, $property, ModelManagerInterface $modelManager)
    {
        if (!$ret = $this->getParentMetadataForProperty($class, $property, $modelManager)) {
            return new TypeGuess('text', array(), Guess::LOW_CONFIDENCE);
        }

        list($metadata, $propertyName, $parentAssociationMappings) = $ret;

        if ($metadata->hasAssociation($propertyName)) {
            $mapping = $metadata->getAssociationMapping($propertyName);

            switch ($mapping['type']) {
                case ClassMetadataInfo::ONE_TO_MANY:
                    return new TypeGuess('orm_one_to_many', array(), Guess::HIGH_CONFIDENCE);

                case ClassMetadataInfo::MANY_TO_MANY:
                    return new TypeGuess('orm_many_to_many', array(), Guess::HIGH_CONFIDENCE);

                case ClassMetadataInfo::MANY_TO_ONE:
                    return new TypeGuess('orm_many_to_one', array(), Guess::HIGH_CONFIDENCE);

                case ClassMetadataInfo::ONE_TO_ONE:
                    return new TypeGuess('orm_one_to_one', array(), Guess::HIGH_CONFIDENCE);
            }
        }

        switch ($metadata->getTypeOfField($propertyName)) {
            case 'array':
            case 'json':
                return new TypeGuess('array', array(), Guess::HIGH_CONFIDENCE);
            case 'boolean':
                return new TypeGuess('boolean', array(), Guess::HIGH_CONFIDENCE);
            case 'datetime':
            case 'vardatetime':
            case 'datetimetz':
                return new TypeGuess('datetime', array(), Guess::HIGH_CONFIDENCE);
            case 'date':
                return new TypeGuess('date', array(), Guess::HIGH_CONFIDENCE);
            case 'decimal':
            case 'float':
                return new TypeGuess('number', array(), Guess::MEDIUM_CONFIDENCE);
            case 'integer':
            case 'bigint':
            case 'smallint':
                return new TypeGuess('integer', array(), Guess::MEDIUM_CONFIDENCE);
            case 'string':
                return new TypeGuess('text', array(), Guess::MEDIUM_CONFIDENCE);
            case 'text':
                return new TypeGuess('textarea', array(), Guess::MEDIUM_CONFIDENCE);
            case 'time':
                return new TypeGuess('time', array(), Guess::HIGH_CONFIDENCE);
            default:
                return new TypeGuess('text', array(), Guess::LOW_CONFIDENCE);
        }
    }
}
