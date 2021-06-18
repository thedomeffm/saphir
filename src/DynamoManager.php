<?php declare(strict_types=1);

/**
 * @author: thedomeffm
 * Date: 17.06.21
 *
 * The software is freely available to everyone and published
 * under the MIT licence. No legal claims arise from its use.
 */

namespace TheDomeFfm\Sapphire;

use TheDomeFfm\Sapphire\Attribute\DynamoClass;
use TheDomeFfm\Sapphire\Attribute\DynamoField;
use TheDomeFfm\Sapphire\Exception\CastException;
use TheDomeFfm\Sapphire\Exception\DynamoClassException;

final class DynamoManager
{
    private CONST CAST_TO_STRING = ['S', 'N'];

    /**
     * @param object $object
     * @return array
     * @throws CastException
     * @throws DynamoClassException
     * @throws \ReflectionException
     */
    public function preparePutAction(object $object): array
    {
        return [
            'TableName' => $this->getTableName($object),
            'Item' => $this->toDynamoItem($object),
        ];
    }

    /**
     * @param object $object
     * @return array
     * @throws CastException
     */
    public function toDynamoItem(object $object): array
    {
        $reflection = new \ReflectionClass($object);

        $properties = $reflection->getProperties();

        $item = [];

        $atLeastOneDynProperty = false;

        foreach ($properties as $property) {
            /** @var DynamoField $dynProperty */
            $dynProperty = $property->getAttributes(DynamoField::class)
                ? $property->getAttributes(DynamoField::class)[0]->newInstance()
                : null;

            if ($dynProperty === null) {
                continue;
            }

            $atLeastOneDynProperty = true;

            if ($property->isPrivate() || $property->isProtected()) {
                $property->setAccessible(true);
            }

            /* STRING CAST */
            if (in_array($dynProperty->getFieldType(), self::CAST_TO_STRING)) {
                // e.g. $item['myKey'] = ['S' => 'myValue']
                $item[$property->getName()] = [$dynProperty->getFieldType() => Caster::toString($property->getValue($object))];

                continue;
            }

            /* BOOL CAST */
            if ($dynProperty->getFieldType() === 'BOOL') {
                $item[$property->getName()] = [$dynProperty->getDynamoField() => Caster::toBool($property->getValue($object))];

                continue;
            }
        }

        if (!$atLeastOneDynProperty) {
            throw new CastException('The given object does not contain a single property that is marked as DynamoField!');
        }

        return $item;
    }

    /**
     * @param array $objects
     * @return array
     * @throws CastException
     */
    public function toDynamoItems(array $objects): array
    {
        $dynamoItems = [];

        foreach ($objects as $object) {
            $dynamoItems[] = $this->toDynamoItem($object);
        }

        return $dynamoItems;
    }

    /**
     * @param object|string $object
     * @return string
     * @throws DynamoClassException
     * @throws \ReflectionException
     */
    public function getTableName(object|string $object): string
    {
        // todo: probably make a check before with class_exists()

        if (is_string($object)) {
            $object = new $object;
        }

        $reflection = new \ReflectionClass($object);

        $dynamoClass =$reflection->getAttributes(DynamoClass::class)
            ? $reflection->getAttributes(DynamoClass::class)[0]->newInstance()
            : null;

        if ($dynamoClass === null) {
            throw new DynamoClassException('Given object has no DynamoClass Attribute!');
        }

        return $dynamoClass->table;
    }

    public function getObject($awsObject, object|string $object): object
    {
        if (is_string($object)) {
            $object = $this->instantiateClass($object);
        }

        $reflection = new \ReflectionClass($object);

        foreach ($reflection->getProperties() as $property) {
            /** @var DynamoField $dynProperty */
            $dynProperty = $property->getAttributes(DynamoField::class)
                ? $property->getAttributes(DynamoField::class)[0]->newInstance()
                : null;

            if ($dynProperty === null) {
                continue;
            }

            if ($property->isPrivate() || $property->isProtected()) {
                $property->setAccessible(true);
            }

            if ($dynProperty->getFieldType() === 'S') {
                $property->setValue($object, $awsObject[$property->getName()]->getS());

                continue;
            }

            if ($dynProperty->getFieldType() === 'N') {
                // todo cast to int or cast to float?
                // check if type definition exists
                $property->setValue($object, $awsObject[$property->getName()]->getN());

                continue;
            }

            if ($dynProperty->getFieldType() === 'BOOL') {
                $property->setValue($object, $awsObject[$property->getName()]->getB());

                continue;
            }
        }

        return $object;
    }

    /**
     * @param string $class
     * @return object
     */
    private function instantiateClass(string $class): object
    {
        return clone unserialize(sprintf('O:%d:"%s":0:{}', strlen($class), $class));
    }
}
