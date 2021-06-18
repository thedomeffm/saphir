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
    public CONST AUTO_DETECTION = 'auto';

    private CONST ALL_DYNAMO_FIELD_TYPES = [
     self::AUTO_DETECTION,
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
        self::AUTO_DETECTION,
        'S',
        'N',
        'BOOL',
    ];

    /**
     * @var string
     */
    private string $fieldType;

    /**
     * @var bool
     */
    private bool $isInteger;

    /**
     * DynamoField constructor.
     *
     * @param string $fieldType set a explicit dynamoDB field type
     * @param bool $isInteger   if explicit 'N' type is set it will be cast to float by default
     *
     * @throws InvalidFieldTypeException
     * @throws UnsupportedFieldTypeException
     */
    public function __construct(string $fieldType = self::AUTO_DETECTION, bool $isInteger = false)
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
        $this->isInteger = $isInteger;
    }

    /**
     * @return string
     */
    public function getFieldType(): string
    {
        return $this->fieldType;
    }

    /**
     * @return bool
     */
    public function isInteger(): bool
    {
        return $this->isInteger;
    }
}
