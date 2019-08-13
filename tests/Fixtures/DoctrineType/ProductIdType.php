<?php
declare(strict_types=1);

namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures\DoctrineType;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\IntegerType;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\ProductId;

final class ProductIdType extends IntegerType
{
    const NAME = 'ProductId';

    /**
     * @param array            $fieldDeclaration
     * @param AbstractPlatform $platform
     *
     * @return string
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getIntegerTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * @param AbstractPlatform $platform
     *
     * @return bool
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    /**
     * @throws ConversionException
     *
     * @param mixed            $value
     * @param AbstractPlatform $platform
     *
     * @return ProductId|null
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?ProductId
    {
        if ($value === null || $value instanceof ProductId) {
            return $value;
        }

        try {
            return new ProductId((int) $value);
        } catch (\Exception $e) {
            throw ConversionException::conversionFailed($value, $this->getName());
        }
    }

    /**
     * @throws ConversionException
     *
     * @param mixed            $value
     * @param AbstractPlatform $platform
     *
     * @return mixed
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        $value = $this->convertToPHPValue($value, $platform);

        return $value !== null ? $value->getId() : null;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
