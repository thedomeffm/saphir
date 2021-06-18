<?php

/**
 * @author: thedomeffm
 * Date: 17.06.21
 *
 * The software is freely available to everyone and published
 * under the MIT licence. No legal claims arise from its use.
 */

namespace TheDomeFfm\Sapphire\Attribute;

use TheDomeFfm\Sapphire\Exception\InvalidFieldTypeException;
use TheDomeFfm\Sapphire\Exception\UnsupportedFieldTypeException;

/**
 * @author: thedomeffm
 * Date: 17.06.21
 *
 * The software is freely available to everyone and published
 * under the MIT licence. No legal claims arise from its use.
 */

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class DynamoField
{
    private CONST ALL_DYNAMO_FIELD_TYPES = [
     'S',
     'N',
     'B',
     'BOOL',
     'NULL',
     'M',
     'L',
     'SS',
     'NS',
     'BS',
    ];

    private CONST CURRENTLY_SUPPORTED_FIELD_TYPES = [
        'S',
        'N',
        'BOOL',
    ];

    private string $fieldType;

    /**
     * DynamoField constructor.
     * @param string $fieldType
     * @throws InvalidFieldTypeException
     * @throws UnsupportedFieldTypeException
     */
    public function __construct(string $fieldType = 'S')
    {
        if (!in_array($fieldType, self::ALL_DYNAMO_FIELD_TYPES)) {
            $fieldTypes = implode(', ', self::ALL_DYNAMO_FIELD_TYPES);
            throw new InvalidFieldTypeException(
                sprintf(
                    'The given type %s does not exist! Official DynamoDB field types are %s',
                    $fieldType,
                    $fieldTypes
                )
            );
        }

        if (!in_array($fieldType, self::CURRENTLY_SUPPORTED_FIELD_TYPES)) {
            $supported = implode(', ', self::CURRENTLY_SUPPORTED_FIELD_TYPES);
            throw new UnsupportedFieldTypeException(
                sprintf(
                    'The given type %s is currently not supported! Currently Supported field types are %s',
                    $fieldType,
                    $supported
                )
            );
        }

        $this->fieldType = $fieldType;
    }

    /**
     * @return string
     */
    public function getFieldType(): string
    {
        return $this->fieldType;
    }
}
