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
use TheDomeFfm\Sapphire\Attribute\DynamoEmbeddedClass;
use TheDomeFfm\Sapphire\Attribute\DynamoField;
use TheDomeFfm\Sapphire\Exception\CastException;
use TheDomeFfm\Sapphire\Exception\ClassNotFoundException;
use TheDomeFfm\Sapphire\Exception\DynamoClassException;
use TheDomeFfm\Sapphire\Exception\UntypedPropertyException;

final class DynamoManager
{
    /**
     * @param object $object
     * @return array
     * @throws CastException
     * @throws DynamoClassException
     * @throws \ReflectionException
     * @throws UntypedPropertyException
     */
    public function preparePutAction(object $object): array
    {
        return [
            'TableName' => $this->getTableName($object),
            'Item' => $this->toDynamoItem($object),
        ];
    }

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

            /** Skip property if it is not a DynamoField (or static) */
            if ($dynProperty === null || $property->isStatic()) {
                continue;
            }

            $atLeastOneDynProperty = true;

            if ($property->isPrivate() || $property->isProtected()) {
                $property->setAccessible(true);
            }

            /** @var ?\ReflectionNamedType $typedProperty */
            $typedProperty = $property->getType();

            if (!$typedProperty) {
                throw new UntypedPropertyException(
                    sprintf(
                        'The property \'%s\' in \'%s\' is not typed, but marked as an DynamoField!',
                        $property->getName(),
                        get_class($object),
                    )
                );
            }

            if ($property->getValue($object) === null) {
                $item[$property->getName()] = ['NULL' => true];

                continue;
            }

            if ($dynProperty->isBinary()) {
                $item[$property->getName()] = ['B' => $property->getValue($object)];

                continue;
            }

            $type = $typedProperty->getName();

            if ($typedProperty->isBuiltin() && $type !== 'array') {
                $item[$property->getName()] = DynamoCaster::castBuiltinField($type, $property->getValue($object));
            } elseif ($type === 'array') {
                $arrayType = $dynProperty->getArrayType();

                if (!$arrayType) {
                    throw new CastException(
                        sprintf(
                            'For the array typed property \'%s\' in \'%s\' you have to provide a array type!',
                            $property->getName(),
                            get_class($object),
                        )
                    );
                }

                if (DynamoField::STRING_ARRAY === $arrayType) {
                    $item[$property->getName()] = ['SS' => $property->getValue($object)];

                    continue;
                }

                if (DynamoField::NUMBER_ARRAY === $arrayType) {
                    $castedArray = [];
                    foreach ($property->getValue($object) as $value) {
                        $castedArray[] = (string) $value;
                    }
                    $item[$property->getName()] = ['NS' => $castedArray];

                    continue;
                }

                if (DynamoField::MIXED_ARRAY === $arrayType) {
                    $castedArray = [];
                    foreach ($property->getValue($object) as $value) {
                        $castedArray[] = DynamoCaster::castBuiltinField(gettype($value), $value);
                    }
                    $item[$property->getName()] = ['L' => $castedArray];

                    continue;
                }

                if (DynamoField::BINARY_ARRAY === $arrayType) {
                    $item[$property->getName()] = ['BS' => $property->getValue($object)];

                    continue;
                }
            } else {
                $item[$property->getName()] = [
                    'M' => $this->toDynamoItem($property->getValue($object))
                ];
            }
        }

        if (!$atLeastOneDynProperty) {
            throw new CastException(
                sprintf(
                    'The given object \'%s\' does not contain a single property that is marked as DynamoField!',
                    get_class($object),
                )
            );
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
        $object = $this->instantiateClass($object);

        $reflection = new \ReflectionClass($object);

        /** @var ?DynamoClass $dynamoClass */
        $dynamoClass = $reflection->getAttributes(DynamoClass::class)
            ? $reflection->getAttributes(DynamoClass::class)[0]->newInstance()
            : null;

        if (!$dynamoClass instanceof DynamoClass) {
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
    public function toPhpObject($awsObject, object|string $object): object
    {
        /** @var object $object */
        $object = $this->instantiateClass($object);

        $reflection = new \ReflectionClass($object);

        if (!$reflection->getAttributes(DynamoEmbeddedClass::class)) {
            /** @var ?DynamoClass $dynamoClass */
            $dynamoClass = $reflection->getAttributes(DynamoClass::class)
                ? $reflection->getAttributes(DynamoClass::class)[0]->newInstance()
                : null;

            if (!$dynamoClass instanceof DynamoClass) {
                throw new DynamoClassException(
                    sprintf(
                        'Given object \'%s\' has no #[DynamoClass] Attribute and is also not marked as #[DynamoEmbeddedClass]',
                        get_class($object),
                    )
                );
            }
        }

        foreach ($reflection->getProperties() as $property) {
            /** @var DynamoField $dynProperty */
            $dynProperty = $property->getAttributes(DynamoField::class)
                ? $property->getAttributes(DynamoField::class)[0]->newInstance()
                : null;

            // skip statics
            if (!$dynProperty || $property->isStatic()) {
                continue;
            }

            // Check that key exist in awsObject
            if (!isset($awsObject[$property->getName()])) {
                continue;
            }

            // make it accessible
            if ($property->isPrivate() || $property->isProtected()) {
                $property->setAccessible(true);
            }

            /** @var ?\ReflectionNamedType $typedProperty */
            $typedProperty = $property->getType();

            if (!$typedProperty) {
                throw new UntypedPropertyException(
                    sprintf(
                        'The property \'%s\' in \'%s\' is not typed, but marked as an DynamoField!',
                        $property->getName(),
                        get_class($object),
                    )
                );
            }

            // if value is null, set it immediately
            if ($awsObject[$property->getName()]->getNull()) {
                if (!$typedProperty->allowsNull()) {
                    throw new CastException(
                        sprintf(
                            'Typed property \'%s\' can\'t be null, but got null!',
                            $property->getName()
                        )
                    );
                }

                $property->setValue($object, null);

                continue;
            }

            $type = $typedProperty->getName();

            if ($typedProperty->isBuiltin() && 'array' !== $type) {
                if ('string' === $type) {
                    $property->setValue($object, (string) $awsObject[$property->getName()]->getS());

                    continue;
                }
                if ('int' === $type) {
                    $property->setValue($object, (int) $awsObject[$property->getName()]->getN());

                    continue;
                }
                if ('float' === $type) {
                    $property->setValue($object, (float) $awsObject[$property->getName()]->getN());

                    continue;
                }
                if ('bool' === $type) {
                    $property->setValue($object, (bool) $awsObject[$property->getName()]->getBool());

                    continue;
                }
                if ('bool' === $type) {
                    $property->setValue($object, json_decode($awsObject[$property->getName()]->getS(), false, JSON_THROW_ON_ERROR));

                    continue;
                }
            } elseif ('array' === $type) {
                $arrayType = $dynProperty->getArrayType();

                if (DynamoField::STRING_ARRAY === $arrayType) {
                    $property->setValue($object, $awsObject[$property->getName()]->getSs());

                    continue;
                }

                if (DynamoField::NUMBER_ARRAY === $arrayType) {
                    $property->setValue($object, $awsObject[$property->getName()]->getNs());

                    continue;
                }

                if (DynamoField::MIXED_ARRAY === $arrayType) {
                    $property->setValue($object, $awsObject[$property->getName()]->getL());

                    continue;
                }

                if (DynamoField::BINARY_ARRAY === $arrayType) {
                    $castedArray = [];
                    foreach ($awsObject[$property->getName()]->getBs() as $item) {
                        if ('' === $item) {
                            $castedArray[] = null;

                            continue;
                        }
                        $castedArray[] = $item;
                    }
                    $property->setValue($object, $castedArray);

                    continue;
                }
            } else {
                $property->setValue($object, $this->toPhpObject($awsObject[$property->getName()]->getM(), $property->getType()->getName()));
            }
        }

        return $object;
    }

    /**
     * @param string $class
     * @return object
     */
    private function instantiateClass(string|object $class): object
    {
        if (is_object($class)) {
            return $class;
        }

        if (!class_exists($class)) {
            throw new ClassNotFoundException(
                sprintf(
                    'Can\'t find class \'%s\'',
                    $class,
                )
            );
        }

        return clone unserialize(sprintf('O:%d:"%s":0:{}', strlen($class), $class));
    }
}
