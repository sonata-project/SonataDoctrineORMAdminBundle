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

namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\StringType;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Util\NonIntegerIdentifierTestClass;

/**
 * Mock for a custom doctrine type used in the ModelManagerTest suite.
 *
 * @author Jeroen Thora <jeroen.thora@gmail.com>
 */
class UuidType extends StringType
{
    const NAME = 'uuid';

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param mixed $value
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?NonIntegerIdentifierTestClass
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (!\is_string($value)) {
            throw ConversionException::conversionFailedInvalidType(
                $value,
                $this->getName(),
                ['null', 'string', NonIntegerIdentifierTestClass::class]
            );
        }

        return new NonIntegerIdentifierTestClass($value);
    }

    /**
     * @param mixed $value
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        $value = $this->convertToPHPValue($value, $platform);

        return null !== $value ? $value->toString() : null;
    }
}
