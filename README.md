# Arca ORM
![GitHub Workflow Status](https://img.shields.io/github/workflow/status/scrawler-labs/arca-orm/Tests?style=flat-square)
![Scrutinizer code quality (GitHub/Bitbucket)](https://img.shields.io/scrutinizer/quality/g/scrawler-labs/arca-orm?style=flat-square)
![Packagist Version (including pre-releases)](https://img.shields.io/packagist/v/scrawler/arca?include_prereleases&style=flat-square)
![GitHub](https://img.shields.io/github/license/scrawler-labs/arca-orm?color=blue&style=flat-square)

A low code / Zero Configuration / NoSQL like ORM 

## Why user Arca Orm ?
- Automatically creates tables and columns as you go
- No configuration, just fire and forget
- Save loads of time while working on database
- Built upon stable foundation of Doctrine Dbal and extensively tested
- Thanks to [loophp](https://github.com/loophp/collection) Arca comes with Lazy collection and tons of helper collection functions
- Supports lots database of platform , you can see the complete list [here](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/platforms.html#platforms)
- Supports concurrent queries and connection pooling using swoole. Check the adapter at [https://github.com/scrawler-labs/swoole-postgresql-doctrine](https://github.com/scrawler-labs/swoole-postgresql-doctrine)

## Requirements
- PHP 8.1 or greater
- PHP PDO or other supported database adapter
- Mysql, MariaDB, Sqlite or any other supported database. check the list [here](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/platforms.html#platforms) 

## Installation
You can install Arca ORM via Composer. If you don't have composer installed , you can download composer from [here](https://getcomposer.org/download/)

```
composer require scrawler/arca
```

## QuickStart

### Setup
```php
   <?php
    include './vendor/autoload.php'
    
    $connectionParams = array(
        'dbname' => YOUR_DB_NAME,
        'user' => YOUR_DB_USER',
        'password' => YOUR_DB_PASSWORD,
        'host' => YOUR_DB_HOST,
        'driver' => 'pdo_mysql', //You can use other supported driver this is the most basic mysql driver
    );

    $db =  new \Scrawler\Arca\Database($connectionParams);
```
    
### CRUD
```php

    // Create new record
    // The below code will automatically create user table and store the record

    $user = $db->create('user');
    $user->name = "Pranja Pandey";
    $user->age = 24
    $user->gender = "male"
    $user->save()
    
    // Get record with id 1
    
    $user = $db->get('user',1);
    
    //Get all records
    
    $users = $db->get('user');
    
    // Update a record
     $user = $db->get('user',1);
     $user->name = "Mr Pranjal";
     $user->save();
     
    // Delete a record
     $user = $db->get('user',1);
     $user->delete();

```

### Finding data with query
```php

  // Using where clause
  $users = $db->find('user')
              ->where('name = "Pranjal Pandey"')
              ->get();
              
  foreach ($users as $user){
  // Some logic here 
  }
  
  // Get only single record
  $user = 
  $db->find('user')
     ->where('name = "Pranjal Pandey"')
     ->first();  

  // Using limit in query
  $db->find('user')
     ->setFirstResult(10)
     ->setMaxResults(20);
     ->get()

```

## Roadmap
Here is list of few things that i would like to add in upcoming release
- [ ] Models should be extendible with custom models
- [ ] Validations for custom models
- [ ] Automatically create migrations when table is updated or created
- [ ] Support eager loading for relations
- [ ] Better documentaions

## Similar projects and inspiration
- [Eloquent ORM](https://laravel.com/docs/5.0/eloquent)
- [Redbean PHP](https://redbeanphp.com/index.php)
- [Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/2.11/index.html)

## License
Arca ORM is created by [Pranjal Pandey](https://www.github.com/ipranjal) and released under the [Apache 2.0 License](https://github.com/scrawler-labs/arca-orm/blob/main/LICENSE).
