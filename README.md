# SAPPHIRE

### Info
> This library is WIP.

---

Sapphire is simple PHP DynamoDB ODM. Made to be fast and tiny.
Ideal for [serverless PHP functions](https://bref.sh).

The ODM does just the mapping part and does not handle queries at all!
You have to use [async-aws](https://async-aws.com/clients/dynamodb.html)
or the [aws-sdk](https://github.com/aws/aws-sdk-php) in order to work with the ODM.

The mapping just work with typed properties and just uses php attributes!
There is no support for annotation!

### Requirements
- php: >=8.0
- typed properties!
- composer: >=2.0

> The package has probably some problems if used with an old composer 1 version

## What does the library do?

You add Attributes to your PHP Classes and with the help of the
DynamoManager you can convert to PHP Object to an Array that the AWS-SDK or async-aws can work with and vice versa.
The library does not care about generating missing tables. You can use CloudFormation or Terraform with this.

## Example

```php
<?php

use Symfony\Component\Uid\UuidV4;
use TheDomeFfm\Sapphire\Attribute\DynamoClass;
use TheDomeFfm\Sapphire\Attribute\DynamoField;

#[DynamoClass('products')]
class Product
{
    #[DynamoField]
    private ?string $id;
    
    #[DynamoField]
    private ?string $name;
    
    #[DynamoField]
    private ?float $price;
    
    public function __construct() {
        $this->id = (string) UuidV4::v4();
    }
}
```

### Save item in DynamoDB
```php
<?php

// ...

$product = new Product();
$product->name = $form['name'];
$product->prive = $form['price'];

$dm = new DynamoManager();

$putItem = $dm->preparePutAction($product);
$dynamoDbClient->putItem($putItem);
```

### Cast AWS DynamoDB Item to your PHP Object
```php
<?php

// ...

$dm = new DynamoManager();

$getItem = [
    'TableName' => $dm->getTableName(Product::class),
    'Key' => [
        'id' => ['S' => $id]
    ]
];

$product = $dynamoDbClient->getItem($getItem)->getItem();

$product = $dm->getObject($product, Product::class);
```

## More complex Examples

### Typed arrays => Dynamo Data Sets
```php
#[DynamoClass('CustomerDataSet')]
class Example {
    #[DynamoField]
    private ?string $id;

    #[DynamoField(arrayType: 'string')]
    private array $stringArray = [
        'one', 'two', 'five'
    ];

    #[DynamoField(arrayType: 'mixed')]
    private array $mixedArray = [
        'one', 2.5, 'eight'
    ];

    #[DynamoField(arrayType: 'number')]
    private array $numberArray = [
        1, 2.5, 3.01
    ];

    #[DynamoField(arrayType: 'binary')]
    private array $binArray = [
        1337, 'i like cheesecake', null, 'potato'
    ];

    public function __construct()
    {
        $this->id = (string) UuidV4::v4();
    }
    
    // ...
}
```

> **IMPORTANT INFO**
> The content of the array is not checked by the ODM (for performance reasons)!

| arrayType | DynamoDB Set |
| --------- | ------------ |
| mixed     | L            |
| string    | SS           |
| number    | NS           |
| binary    | BS           |

### Embedded Documents
```php
#[DynamoEmbeddedClass]
class Category
{
    #[DynamoField]
    private string $id;

    #[DynamoField]
    private ?string $name = null;

    public function __construct()
    {
        $this->id = (string) UuidV4::v4();
    }
}

#[DynamoClass('products')]
class Product
{
    #[DynamoField]
    private ?string $id;
    
    #[DynamoField]
    private ?string $name;
    
    #[DynamoField]
    private ?float $price;
    
    #[DynamoField]
    private ?Category $category;
    
    public function __construct() {
        $this->id = (string) UuidV4::v4();
    }
}
```

> **Info**
> The embedded document will be saved as 'M' => Map
> 
> See [documentation](https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_AttributeValue.html)

### Binary
```php
#[DynamoClass('customers')]
class Customer
{
    #[DynamoField]
    private ?string $id;

    #[DynamoField(isBinary: true)]
    public ?string $blob = 'myBinaryString';
    
    // ...
}
```

> **INFO**
> The async-aws (and also the aws-sdk) convert your string to base64_encode.
> So you can pass a string without the hassle to convert something.
