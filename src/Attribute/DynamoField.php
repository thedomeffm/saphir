<?php declare(strict_types=1);

/**
 * @author: thedomeffm
 * Date: 17.06.21
 *
 * The software is freely available to everyone and published
 * under the MIT licence. No legal claims arise from its use.
 */

namespace TheDomeFfm\Sapphire\Attribute;

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
    public CONST ALLOWED_ARRAY_TYPES = [
        self::MIXED_ARRAY,  // 'L'
        self::STRING_ARRAY, // 'SS'
        self::NUMBER_ARRAY, // 'NS'
        self::BINARY_ARRAY, // 'BS'
    ];

    public CONST MIXED_ARRAY = 'mixed';   // 'L'
    public CONST STRING_ARRAY = 'string'; // 'SS'
    public CONST NUMBER_ARRAY = 'number'; // 'NS'
    public CONST BINARY_ARRAY = 'binary'; // 'BS'

    /**
     * @link https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_AttributeValue.html
     */
    public CONST DYNAMO_FIELD_TYPES = [
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

    private bool $isBinary;

    /**
     * @var string|null
     */
    private ?string $arrayType;

    /**
     * DynamoField constructor.
     */
    public function __construct(bool $isBinary = false, ?string $arrayType = null)
    {
        $this->isBinary = $isBinary;

        if (null !== $arrayType && in_array($arrayType, self::ALLOWED_ARRAY_TYPES)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The given arrayType \'%s\' is not supported',
                    $arrayType
                )
            );
        }

        $this->arrayType = $arrayType;
    }

    /**
     * @return bool
     */
    public function isBinary(): bool
    {
        return $this->isBinary;
    }

    /**
     * @return string|null
     */
    public function getArrayType(): ?string
    {
        return $this->arrayType;
    }
}
