<?php declare(strict_types=1);

/**
 * @author: thedomeffm
 * Date: 17.06.21
 *
 * The software is freely available to everyone and published
 * under the MIT licence. No legal claims arise from its use.
 */

namespace TheDomeFfm\Sapphire;

use TheDomeFfm\Sapphire\Exception\CastException;

final class DynamoCaster
{
    /**
     * @param string $phpType
     * @param mixed $value
     * @return array
     * @throws CastException
     */
    public static function castBuiltinField(string $phpType, mixed $value): array
    {
        // $phpType can come from gettype()
        // https://www.php.net/manual/en/function.gettype.php#refsect1-function.gettype-returnvalues
        if (null === $value || 'NULL' === $phpType) {
            return [
                'NULL' => true
            ];
        }

        $caster = self::getDynamoValueCaster($phpType);

        return [
            self::getDynamoType($phpType) => self::$caster($value),
        ];
    }

    /**
     * @param string $phpType
     * @return string
     * @throws CastException
     */
    private static function getDynamoType(string $phpType): string
    {
        return match ($phpType) {
            'string', 'object', 'array'         => 'S',
            'int', 'integer', 'float', 'double' => 'N',
            'bool', 'boolean'                   => 'BOOL',
            default => throw new CastException(
                sprintf(
                    'Can\'t cast \'%s\' to an equivalent DynamoDB type!',
                    $phpType
                )
            )
        };
    }

    /**
     * @param string $phpType
     * @return string
     * @throws CastException
     */
    private static function getDynamoValueCaster(string $phpType): string
    {
        return match ($phpType) {
            'string', 'int', 'integer', 'float', 'double', 'object' => 'toString',
            'bool', 'boolean' => 'toBool',
            default => throw new CastException(
                sprintf(
                    'Can\'t get a compatible value caster for \'%s\'!',
                    $phpType
                )
            )
        };
    }

    /**
     * @param mixed $value
     * @return string
     * @throws CastException
     */
    private static function toString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_integer($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_object($value)) {
            return json_encode($value);
        }

        throw new CastException(
            sprintf(
                'Can not cast value of type \'%s\' to string!',
                gettype($value)
            )
        );
    }

    /**
     * @param mixed $value
     * @return bool
     */
    private static function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return (bool) $value;
    }
}
