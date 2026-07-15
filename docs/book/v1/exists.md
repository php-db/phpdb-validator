# RecordExists and NoRecordExists Validators

`PhpDb\Validator\RecordExists` and `PhpDb\Validator\NoRecordExists` provide
a means to test whether a record exists in a given table of a database, with a
given value.

<!-- markdownlint-disable-next-line MD001 -->
> ### Installation requirements
>
> `PhpDb\Validator\NoRecordExists` and `PhpDb\Validator\RecordExists`
> depends on the core PhpDb component, so be sure to have it installed before
> getting started by installing your adapter:
>
> ```bash
> // E.g. MySQL
> $ composer require php-db/phpdb-adapter-mysql
> ```

## Supported options

The following options are supported for `PhpDb\Validator\NoRecordExists` and
`PhpDb\Validator\RecordExists`:

- `adapter`: A database adapter implementing `PhpDb\Adapter\AdapterInterface` that will be
  used for the search. Required but not immediately within the constructor
- `exclude`: Sets records that will be excluded from the search.
- `field`: The database field within this table that will be searched for the record.
- `schema`: Sets the schema that will be used for the search. Only valid when
  `table` is a string.
- `table`: The table that will be searched for the record, as a non-empty string
  or a `PhpDb\Sql\TableIdentifier` instance. Required.
- `select`: An instance of a where clause by which to add further precision to the query

## Basic usage

An example of basic usage of the validators:

```php
// Assuming $dbAdapter is configured via your framework's DI container or through
// a new instance

// Check that the email address exists in the database
$validator = new PhpDb\Validator\RecordExists([
    'table'   => 'users',
    'field'   => 'emailaddress',
    'adapter' => $dbAdapter,
]);

if ($validator->isValid($emailaddress)) {
    // email address appears to be valid
} else {
    // email address is invalid; print the reasons
    foreach ($validator->getMessages() as $message) {
        echo "$message\n";
    }
}
```

The above will test that a given email address is in the database table. If no
record is found containing the value of `$emailaddress` in the specified column,
then an error message is displayed.

```php
// Check that the username is not present in the database
$validator = new PhpDb\Validator\NoRecordExists([
    'table'   => 'users',
    'field'   => 'username',
    'adapter' => $dbAdapter,
]);

if ($validator->isValid($username)) {
    // username appears to be valid
} else {
    // username is invalid; print the reason
    $messages = $validator->getMessages();
    foreach ($messages as $message) {
        echo "$message\n";
    }
}
```

The above will test that a given username is *not* in the database table. If a
record is found containing the value of `$username` in the specified column,
then an error message is displayed.

## Excluding records

`PhpDb\Validator\RecordExists` and `PhpDb\Validator\NoRecordExists` also
provide a means to test the database, excluding a part of the table, either by
providing a `WHERE` clause as a string, or an array with the keys `field` and
`value`.

When providing an array for the exclude clause, the `!=` operator is used, so
you can check the rest of a table for a value before altering a record (for
example on a user profile form)

```php
// Check no other users have the username
$user_id   = $user->getId();
$validator = new PhpDb\Validator\NoRecordExists([
    'table' => 'users',
    'field' => 'username',
    'adapter' => $dbAdapter,
    'exclude' => [
        'field' => 'id',
        'value' => $user_id,
    ],
]);

if ($validator->isValid($username)) {
    // username appears to be valid
} else {
    // username is invalid; print the reason
    $messages = $validator->getMessages();
    foreach ($messages as $message) {
        echo "$message\n";
    }
}
```

The above example will check the table to ensure no records other than the one
where `id = $user_id` contains the value `$username`.

You can also provide a string to the exclude clause so you can use an operator
other than `!=`. This can be useful for testing against composite keys.

```php
$email     = 'user@example.com';
$clause    = $dbAdapter->quoteIdentifier('email') . ' = ' . $dbAdapter->quoteValue($email);
$validator = new PhpDb\Validator\RecordExists([
    'table'   => 'users',
    'field'   => 'username',
    'adapter' => $dbAdapter,
    'exclude' => $clause,
]);

if ($validator->isValid($username)) {
    // username appears to be valid
} else {
    // username is invalid; print the reason
    $messages = $validator->getMessages();
    foreach ($messages as $message) {
        echo "$message\n";
    }
}
```

The above example will check the `users` table to ensure that only a record with
both the username `$username` and with the email `$email` is valid.

## Database Schemas

You can specify a schema within your database for adapters such as PostgreSQL
and DB/2 by supplying an array with `table` and `schema` keys, as demonstrated
below:

```php
$validator = new PhpDb\Validator\RecordExists([
    'table'  => 'users',
    'schema' => 'my',
    'field'  => 'id',
    'adapter' => $dbAdapter,
]);
```

Alternatively, supply a `PhpDb\Sql\TableIdentifier` carrying both the table and
schema as the `table` option. The `schema` option must be omitted in this case:

```php
use PhpDb\Sql\TableIdentifier;

$validator = new PhpDb\Validator\RecordExists([
    'table'   => new TableIdentifier('users', 'my'),
    'field'   => 'id',
    'adapter' => $dbAdapter,
]);
```

## Using a Select object

It is also possible to supply the validators with a `PhpDb\Sql\Select` object
in place of options. The validator then uses this object instead of building its
own. This allows for greater flexibility with selection of records used for
validation.

```php
use PhpDb\Sql\Select;
use PhpDb\Validator\RecordExists;

$select = new Select();
$select
    ->from('users')
    ->where
    ->equalTo('id', $user_id)
    ->equalTo('email', $email);

$validator = new RecordExists([
    'select' => $select
    'adapter' => $dbAdapter
]);

// Validation is then performed as usual
if ($validator->isValid($username)) {
    // username appears to be valid
} else {
    // username is invalid; print the reason
    $messages = $validator->getMessages();
    foreach ($messages as $message) {
        echo "$message\n";
    }
}
```

The above example will check the `users` table to ensure that only a record with
both the username `$username` and with the email `$email` is valid.
