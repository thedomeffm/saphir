# SAPPHIRE

A simple small PHP DynamoDB ODM. Made to be fast and tiny.
Ideal for [serverless PHP functions](https://bref.sh).

You have to use [async-aws](https://async-aws.com/clients/dynamodb.html)
or the [aws-sdk](https://github.com/aws/aws-sdk-php) in order to work with AWS DynamoDB!

### Requirements
- php: >=8.0
- composer: >=2.0

> The package is not installable with composer 1

## What does the library do?

You add Attributes to your PHP Classes and with the help of the
DynamoManager you can convert to PHP Object to an Array that the AWS-SDK or async-aws can work with and vice versa.

## Info
This library is WIP.

## Example

```php
<?php

use Symfony\Component\Uid\UuidV4;
use TheDomeFfm\Sapphire\Attribute\DynamoClass;
use TheDomeFfm\Sapphire\Attribute\DynamoField;

#[DynamoClass('products')]
class Product
{
    #[DynamoField('S')]
    private ?string $id;
    
    #[DynamoField]
    private ?string $name;
    
    #[DynamoField('N', isInteger: true)]
    private $group;
    
    public function __construct() {
        $this->id = (string) UuidV4::v4();
    }
}
```

Wehen you use typed properties the ODM will try to cast the properties
to the most useful way (see $name).

You can also type them manually (see $id).

When you set the field value by you own and expect an integer use 'N' with `isInteger: true`.
Without `isInteger: true` or with `isInteger: false` the property will be cast to float. 

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

$domain = $dynamoDbClient->getItem($getItem)->getItem();

$domain = $dm->getObject($domain, Product::class);
```

### Cast your PHP Object to AWS DynamoDB Item 
```php
<?php

// ...

$domain = new Product();
$domain->name = $form['name'];
$domain->group = $form['group'];

$dm = new DynamoManager();

$putItem = $dm->preparePutAction($domain);
$dynamoDbClient->putItem($putItem);
```
