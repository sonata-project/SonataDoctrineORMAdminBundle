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

namespace Sonata\DoctrineORMAdminBundle\Guesser;

use Doctrine\ORM\Mapping\MappingException;
use Sonata\AdminBundle\Guesser\TypeGuesserInterface;
use Sonata\AdminBundle\Model\ModelManagerInterface;

abstract class AbstractTypeGuesser implements TypeGuesserInterface
{
    /**
     * @phpstan-param class-string $baseClass
     *
     * @phpstan-return array{\Doctrine\ORM\Mapping\ClassMetadata, string, array}|null
     */
    protected function getParentMetadataForProperty(string $baseClass, string $propertyFullName, ModelManagerInterface $modelManager): ?array
    {
        try {
            return $modelManager->getParentMetadataForProperty($baseClass, $propertyFullName);
        } catch (MappingException $e) {
            // no metadata not found.
            return null;
        }
    }
}
