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

/**
 * NEXT_MAJOR: Remove this class.
 *
 * @deprecated since sonata-project/doctrine-orm-admin-bundle 3.x, to be removed in 4.0.
 */
abstract class AbstractTypeGuesser implements TypeGuesserInterface
{
    /**
     * NEXT_MAJOR: Remove this method.
     *
     * @deprecated since sonata-project/doctrine-orm-admin-bundle 3.x, to be removed in 4.0.
     *
     * @param string $baseClass
     * @param string $propertyFullName
     *
     * @return array|null
     *
     * @phpstan-param class-string $baseClass
     *
     * @phpstan-return array{\Doctrine\ORM\Mapping\ClassMetadata, string, array}|null
     */
    protected function getParentMetadataForProperty($baseClass, $propertyFullName, ModelManagerInterface $modelManager)
    {
        if ('sonata_deprecation_mute' !== (\func_get_args()[3] ?? null)) {
            @trigger_error(sprintf(
                'The "%s()" method is deprecated since sonata-project/doctrine-orm-admin-bundle 3.x and'
                .' will be removed in version 4.0.',
                __METHOD__
            ), \E_USER_DEPRECATED);
        }

        try {
            return $modelManager->getParentMetadataForProperty($baseClass, $propertyFullName, 'sonata_deprecation_mute');
        } catch (MappingException $e) {
            // no metadata not found.
            return null;
        }
    }
}
