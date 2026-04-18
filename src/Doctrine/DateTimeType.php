<?php
namespace App\Doctrine;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class DateTimeType extends Type
{
    public const NAME = 'datetime';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getDateTimeTypeDeclarationSQL($column);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?\DateTimeInterface
    {
        if ($value === null || $value instanceof \DateTimeInterface) {
            return $value;
        }

        $val = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
        if (!$val) {
            throw new \InvalidArgumentException('Invalid datetime value: ' . $value);
        }
        return $val;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        throw new \InvalidArgumentException('Invalid datetime value');
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}