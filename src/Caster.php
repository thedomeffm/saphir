<?php

/**
 * @author: thedomeffm
 * Date: 17.06.21
 *
 * The software is freely available to everyone and published
 * under the MIT licence. No legal claims arise from its use.
 */

namespace TheDomeFfm\Saphir;

use TheDomeFfm\Saphir\Exception\CastException;

final class Caster
{
    /**
     * @param $value
     * @return string|null
     * @throws CastException
     */
    public static function toString($value): ?string
    {
        if (is_null($value) || is_string($value)) {
            return $value;
        }

        if (is_integer($value) || is_double($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_object($value) || is_array($value)) {
            return json_encode($value);
        }

        throw new CastException('Can not cast given value to string! Given type ' . gettype($value));
    }

    /**
     * @param $value
     * @return float|null
     * @throws CastException
     */
    public static function toFloat($value): ?float
    {
        if (is_null($value) || is_float($value)) {
            return $value;
        }

        if (is_integer($value)) {
            return (float) $value;
        }

        throw new CastException('Can not cast given value to float! Given type ' . gettype($value));
    }

    /**
     * @param $value
     * @return int|null
     * @throws CastException
     */
    public static function toInt($value): ?int
    {
        if (is_null($value) || is_integer($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) $value;
        }

        throw new CastException('Can not cast given value to integer! Given type ' . gettype($value));
    }

    /**
     * @param $value
     * @return bool|null
     * @throws CastException
     */
    public static function toBool($value): ?bool
    {
        if (is_null($value) || is_bool($value)) {
            return $value;
        }

        throw new CastException('Can not cast given value to bool! Given type ' . gettype($value));
    }
}
