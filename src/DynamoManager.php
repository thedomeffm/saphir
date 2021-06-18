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

final class DynamoManager implements DynamoManagerInterface
{
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

            if ($dynProperty === null || $property->isStatic()) {
                continue;
            }

            $atLeastOneDynProperty = true;

            if ($property->isPrivate() || $property->isProtected()) {
                $property->setAccessible(true);
            }

            if ($property->getValue($object) === null) {
                $item[$property->getName()] = ['NULL' => true];

                continue;
            }

            if ($dynProperty->getFieldType() === DynamoField::AUTO_DETECTION) {
                /** @var ?\ReflectionNamedType $typedProperty */
                $typedProperty = $property->getType();

                if (!$typedProperty) {
                    throw new CastException(
                        sprintf(
                            'The given property \'%s\' in \'%s\' has no explicit FieldType and is not typed!',
                            $property->getName(),
                            get_class($object),
                        )
                    );
                }
                if (!$typedProperty->isBuiltin()) {
                    throw new CastException(
                        sprintf(
                            'The given property \'%s\' in \'%s\' does not use PHP builtin type. Given type \'%s\'. This is (at least for now) not supported!',
                            $property->getName(),
                            $typedProperty->getName(),
                            get_class($object),
                        )
                    );
                }

                $type = $typedProperty->getName();
                if (in_array($type, ['string', 'float', 'int'])) {
                    $item[$property->getName()] = [Caster::phpTypeToDynamoType($type) => Caster::toString($property->getValue($object))];

                    continue;
                }
                if ($type === 'bool') {
                    $item[$property->getName()] = [Caster::phpTypeToDynamoType($type) => Caster::toBool($property->getValue($object))];

                    continue;
                }
                if ($type === 'object') {
                    $item[$property->getName()] = [Caster::phpTypeToDynamoType($type) => Caster::toBool($property->getValue($object))];

                    continue;
                }
                if ($type === 'array') {
                    $item[$property->getName()] = [Caster::phpTypeToDynamoType($type) => Caster::toBool($property->getValue($object))];

                    continue;
                }
            }

            if (in_array($dynProperty->getFieldType(), ['S', 'N'])) {
                $item[$property->getName()] = [$dynProperty->getFieldType() => Caster::toString($property->getValue($object))];

                continue;
            }

            if ($dynProperty->getFieldType() === 'BOOL') {
                $item[$property->getName()] = [$dynProperty->getFieldType() => Caster::toBool($property->getValue($object))];

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

        /** @var ?DynamoClass $dynamoClass */
        $dynamoClass =$reflection->getAttributes(DynamoClass::class)
            ? $reflection->getAttributes(DynamoClass::class)[0]->newInstance()
            : null;

        if (!$dynamoClass) {
            throw new DynamoClassException('Given object has no DynamoClass Attribute!');
        }

        return $dynamoClass->getTableName();
    }

    /**
     * @param $awsObject
     * @param object|string $object
     * @return object
     * @throws \ReflectionException
     */
    public function getObject($awsObject, object|string $object): object
    {
        // todo: probably make a check before with class_exists()

        if (is_string($object)) {
            $object = $this->instantiateClass($object);
        }

        $reflection = new \ReflectionClass($object);

        foreach ($reflection->getProperties() as $property) {
            /** @var DynamoField $dynProperty */
            $dynProperty = $property->getAttributes(DynamoField::class)
                ? $property->getAttributes(DynamoField::class)[0]->newInstance()
                : null;

            if (!$dynProperty || $property->isStatic()) {
                continue;
            }

            if ($property->isPrivate() || $property->isProtected()) {
                $property->setAccessible(true);
            }

            /**
             * Check if value is null
             */
            if ($awsObject[$property->getName()]->getNull()) {
                $property->setValue($object, null);

                continue;
            }

            if ($dynProperty->getFieldType() === DynamoField::AUTO_DETECTION) {
                /** @var ?\ReflectionNamedType $typedProperty */
                $typedProperty = $property->getType();

                if (!$typedProperty) {
                    throw new CastException(
                        sprintf(
                            'The given property \'%s\' in \'%s\' has no explicit FieldType and is not typed!',
                            $property->getName(),
                            get_class($object),
                        )
                    );
                }
                if (!$typedProperty->isBuiltin()) {
                    throw new CastException(
                        sprintf(
                            'The given property \'%s\' in \'%s\' does not use PHP builtin type. Given type \'%s\'. This is (at least for now) not supported!',
                            $property->getName(),
                            $typedProperty->getName(),
                            get_class($object),
                        )
                    );
                }

                $type = $typedProperty->getName();
                if ($type === 'string') {
                    $property->setValue($object, (string) $awsObject[$property->getName()]->getS());

                    continue;
                }
                if ($type === 'int') {
                    $property->setValue($object, (int) $awsObject[$property->getName()]->getN());

                    continue;
                }
                if ($type === 'float') {
                    $property->setValue($object, (float) $awsObject[$property->getName()]->getN());

                    continue;
                }
                if ($type === 'bool') {
                    $property->setValue($object, (bool) $awsObject[$property->getName()]->getS());

                    continue;
                }
                if ($type === 'object') {
                    $property->setValue($object, json_decode($awsObject[$property->getName()]->getS(), false, JSON_THROW_ON_ERROR));

                    continue;
                }
                if ($type === 'array') {
                    $property->setValue($object, json_decode($awsObject[$property->getName()]->getS(), true, JSON_THROW_ON_ERROR));

                    continue;
                }
            }

            if ($dynProperty->getFieldType() === 'S') {
                $property->setValue($object, (string) $awsObject[$property->getName()]->getS());

                continue;
            }

            if ($dynProperty->getFieldType() === 'N') {
                if ($dynProperty->isInteger()) {
                    $property->setValue($object, (int) $awsObject[$property->getName()]->getN());
                } else {
                    $property->setValue($object, (float) $awsObject[$property->getName()]->getN());
                }

                continue;
            }

            if ($dynProperty->getFieldType() === 'BOOL') {
                $property->setValue($object, (bool) $awsObject[$property->getName()]->getB());

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
