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
use Doctrine\DBAL\Types\Type;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\ProductId;

final class ProductIdType extends Type
{
    const NAME = 'ProductId';

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return $platform->getIntegerTypeDeclarationSQL($fieldDeclaration);
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    /**
     * @param mixed $value
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?ProductId
    {
        if ($value === null || $value instanceof ProductId) {
            return $value;
        }

        try {
            return new ProductId((int) $value);
        } catch (\Throwable $e) {
            throw ConversionException::conversionFailed($value, $this->getName());
        }
    }

    /**
     * @param mixed $value
     *
     * @return int|null
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        $value = $this->convertToPHPValue($value, $platform);

        return $value !== null ? $value->getId() : null;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
