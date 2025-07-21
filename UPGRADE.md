# Upgrade Guide for PHP 7.4 and MongoDB 8 Compatibility

This document outlines the changes made to ensure compatibility with PHP 7.4 and MongoDB 8.

## Changes Made

### 1. Updated Dependencies in composer.json

- Updated PHP requirement from `>=7.1.0` to `>=7.4.0`
- Updated mongodb/mongodb library from `^1.10` to `^1.15` for MongoDB 8 compatibility

### 2. MongoDB Driver Updates

#### Replaced Deprecated Methods

- Replaced `count()` with `countDocuments()` in MongoDatabaseTable.php
  - The `count()` method is deprecated in MongoDB 4.0+ and removed in MongoDB 8

- Replaced `ensureIndex()` with `createIndex()` in MongoDatabaseTable.php
  - The `ensureIndex()` method is deprecated in newer MongoDB versions

#### Updated Class Names

- Updated `MongoDB\BSON\ObjectID` to `MongoDB\BSON\ObjectId` in:
  - MongoDatabaseTable.php
  - MongoDatabase.php
  
  This change is necessary because the class was renamed in newer MongoDB PHP drivers.

## Testing

The changes can be tested using the MongoDB demo file:
- `/Users/pabloi/Ziggeo/phprecious/demos/mongo/querytest.php`

## Additional Notes

- No PHP 7.4 compatibility issues were found in the codebase
- The code should now work correctly with PHP 7.4 and MongoDB 8
- After updating, run `composer update` to install the updated dependencies
