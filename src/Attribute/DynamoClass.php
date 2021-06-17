<?php

/**
 * @author: thedomeffm
 * Date: 17.06.21
 *
 * The software is freely available to everyone and published
 * under the MIT licence. No legal claims arise from its use.
 */

namespace TheDomeFfm\Saphir\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class DynamoClass
{
    private string $tableName;

    /**
     * DynamoClass constructor.
     * @param string $tableName
     */
    public function __construct(string $tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }
}
