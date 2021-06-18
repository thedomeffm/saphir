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
use TheDomeFfm\Sapphire\Exception\DynamoClassException;

interface DynamoManagerInterface
{
    /**
     * @param object $object
     *
     * @return array
     *
     * @throws CastException        When the property can't be casted to desired dynamo field type
     * @throws DynamoClassException The class is not marked as a DynamoClass
     * @throws \ReflectionException Invalid Reflection
     */
    public function preparePutAction(object $object): array;

    /**
     * @param object $object
     *
     * @return array
     *
     * @throws CastException When the property can't be casted to desired dynamo field type
     */
    public function toDynamoItem(object $object): array;

    /**
     * @param array $objects
     *
     * @return array
     *
     * @throws CastException When the property can't be casted to desired dynamo field type
     */
    public function toDynamoItems(array $objects): array;

    /**
     * @param object|string $object
     *
     * @return string
     *
     * @throws DynamoClassException The class is not marked as a DynamoClass
     * @throws \ReflectionException Invalid Reflection
     */
    public function getTableName(object|string $object): string;

    /**
     * @param $awsObject
     * @param object|string $object
     *
     * @return object
     *
     * @throws \ReflectionException  Invalid Reflection
     */
    public function getObject($awsObject, object|string $object): object;
}
