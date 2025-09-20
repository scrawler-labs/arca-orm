<?php

use function Pest\Faker\fake;

// Common Test Helper Functions
// These functions are shared across all test files to avoid redeclaration errors

/**
 * Create a test user with standard fake data.
 */
function createTestUser(string $useUUID, array $additionalData = []): object
{
    $user = db($useUUID)->create('user');
    $user->name = $additionalData['name'] ?? fake()->name();
    $user->email = $additionalData['email'] ?? fake()->email();
    $user->dob = $additionalData['dob'] ?? fake()->date();
    $user->age = $additionalData['age'] ?? fake()->randomNumber(2, false);
    $user->active = $additionalData['active'] ?? fake()->boolean();
    $user->address = $additionalData['address'] ?? fake()->streetAddress();

    // Allow additional fields to be set
    foreach ($additionalData as $key => $value) {
        if (!in_array($key, ['name', 'email', 'dob', 'age', 'active', 'address'])) {
            $user->$key = $value;
        }
    }

    return $user;
}

/**
 * Create a test parent with standard fake data.
 */
function createTestParent(string $useUUID, array $additionalData = []): object
{
    $parent = db($useUUID)->create('parent');
    $parent->name = $additionalData['name'] ?? fake()->name();

    // Allow additional fields to be set
    foreach ($additionalData as $key => $value) {
        if ('name' !== $key) {
            $parent->$key = $value;
        }
    }

    return $parent;
}

/**
 * Create a test grandparent with standard fake data.
 */
function createTestGrandparent(string $useUUID, array $additionalData = []): object
{
    $grandparent = db($useUUID)->create('grandparent');
    $grandparent->name = $additionalData['name'] ?? fake()->name();

    // Allow additional fields to be set
    foreach ($additionalData as $key => $value) {
        if ('name' !== $key) {
            $grandparent->$key = $value;
        }
    }

    return $grandparent;
}

/**
 * Create a test child with standard fake data.
 */
function createTestChild(string $useUUID, array $additionalData = []): object
{
    $child = db($useUUID)->create('child');
    $child->name = $additionalData['name'] ?? fake()->name();

    // Allow additional fields to be set
    foreach ($additionalData as $key => $value) {
        if ('name' !== $key) {
            $child->$key = $value;
        }
    }

    return $child;
}

/**
 * Create and save a test user, returning the saved object.
 */
function createAndSaveTestUser(string $useUUID, array $additionalData = []): object
{
    $user = createTestUser($useUUID, $additionalData);
    $user->save();

    return $user;
}

/**
 * Create and save a test user, returning the ID (for TypeTest compatibility).
 */
function createAndSaveTestUserReturnId(string $useUUID, array $additionalData = []): string|int
{
    $userData = createTestUserWithData($useUUID, $additionalData);

    $user = db($useUUID)->create('user');
    foreach ($userData as $key => $value) {
        $user->$key = $value;
    }

    return $user->save();
}

/**
 * Create and save a test parent, returning the saved object.
 */
function createAndSaveTestParent(string $useUUID, array $additionalData = []): object
{
    $parent = createTestParent($useUUID, $additionalData);
    $parent->save();

    return $parent;
}

/**
 * Create test user data as array without creating the model.
 */
function createTestUserData(array $overrides = []): array
{
    return array_merge([
        'name' => fake()->name(),
        'email' => fake()->email(),
        'dob' => fake()->date(),
        'age' => fake()->randomNumber(2, false),
        'active' => fake()->boolean(),
        'address' => fake()->streetAddress(),
    ], $overrides);
}

/**
 * Create test user with all possible fields for table structure testing.
 */
function createTestUserWithAllFields(string $useUUID): array
{
    $user = createTestUser($useUUID);
    $user->bio = fake()->text(200);
    $user->phone = fake()->phoneNumber();
    $user->website = fake()->url();
    $user->save();

    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'dob' => $user->dob,
        'age' => $user->age,
        'active' => $user->active,
        'address' => $user->address,
        'bio' => $user->bio,
        'phone' => $user->phone,
        'website' => $user->website,
    ];
}

/**
 * Create a test user model and return it (for constraint testing).
 */
function createTestUserModel(string $useUUID): Scrawler\Arca\Model
{
    return createTestUser($useUUID);
}

/**
 * Creates a test profile model for One-to-One relationships.
 */
function createTestProfileModel(string $useUUID): Scrawler\Arca\Model
{
    $profile = db($useUUID)->create('profile');
    $profile->bio = fake()->sentence();
    $profile->avatar = fake()->url();

    return $profile;
}

/**
 * Creates a test post model for One-to-Many relationships.
 */
function createTestPostModel(string $useUUID): Scrawler\Arca\Model
{
    $post = db($useUUID)->create('post');
    $post->title = fake()->sentence();
    $post->content = fake()->paragraph();

    return $post;
}

/**
 * Creates a test tag model for Many-to-Many relationships.
 */
function createTestTagModel(string $useUUID): Scrawler\Arca\Model
{
    $tag = db($useUUID)->create('tag');
    $tag->name = fake()->word();
    $tag->color = fake()->hexColor();

    return $tag;
}

/**
 * Create a test user record and return the saved object.
 */
function createTestUserRecord(string $useUUID): object
{
    return createAndSaveTestUser($useUUID);
}

/**
 * Assert that a table exists in the database.
 */
function assertTableExists(string $tableName, string $useUUID): void
{
    $connection = db($useUUID)->getConnection();
    $schemaManager = $connection->createSchemaManager();
    expect($schemaManager->tablesExist([$tableName]))->toBeTrue("Table {$tableName} should exist");
}

/**
 * Assert that a table does not exist in the database.
 */
function assertTableNotExists(string $tableName, string $useUUID): void
{
    $connection = db($useUUID)->getConnection();
    $schemaManager = $connection->createSchemaManager();
    expect($schemaManager->tablesExist([$tableName]))->toBeFalse("Table {$tableName} should not exist");
}

/**
 * Get direct database record for comparison.
 */
function getDirectDatabaseRecord(string $useUUID, string $table, string|int $id): array
{
    $connection = db($useUUID)->getConnection();
    $stmt = $connection->prepare("SELECT * FROM {$table} WHERE id = ?");
    $stmt->bindValue(1, $id);

    return $stmt->executeQuery()->fetchAssociative() ?: [];
}

/**
 * Creates the expected schema for the user table based on UUID configuration.
 */
function createExpectedUserTableSchema(string $useUUID): Doctrine\DBAL\Schema\Table
{
    $requiredTable = new Doctrine\DBAL\Schema\Table('user');

    if (db($useUUID)->isUsingUUID()) {
        $requiredTable->addColumn('id', 'string', ['length' => 36, 'notnull' => true, 'comment' => 'string']);
    } else {
        $requiredTable->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true, 'comment' => 'integer']);
    }

    $requiredTable->addColumn('name', 'text', ['notnull' => false, 'comment' => 'text']);
    $requiredTable->addColumn('email', 'text', ['notnull' => false, 'comment' => 'text']);
    $requiredTable->addColumn('dob', 'text', ['notnull' => false, 'comment' => 'text']);
    $requiredTable->addColumn('age', 'integer', ['notnull' => false, 'comment' => 'integer']);
    $requiredTable->addColumn('active', 'integer', ['notnull' => false, 'comment' => 'integer']);
    $requiredTable->addColumn('address', 'text', ['notnull' => false, 'comment' => 'text']);
    $requiredTable->addColumn('bio', 'text', ['notnull' => false, 'comment' => 'text']);
    $requiredTable->addColumn('phone', 'text', ['notnull' => false, 'comment' => 'text']);
    $requiredTable->addColumn('website', 'text', ['notnull' => false, 'comment' => 'text']);

    $requiredTable->setPrimaryKey(['id']);

    return $requiredTable;
}

/**
 * Compares actual table schema with expected schema and returns differences.
 */
function compareTableSchemas(string $tableName, Doctrine\DBAL\Schema\Table $expectedTable, string $useUUID): array
{
    $actualTable = db($useUUID)->getConnection()->createSchemaManager()->introspectTable($tableName);
    $actual = new Doctrine\DBAL\Schema\Schema([$actualTable]);
    $required = new Doctrine\DBAL\Schema\Schema([$expectedTable]);
    $comparator = db($useUUID)->getConnection()->createSchemaManager()->createComparator();
    $diff = $comparator->compareSchemas($actual, $required);

    return db($useUUID)->getConnection()->getDatabasePlatform()->getAlterSchemaSQL($diff);
}

/**
 * Cleanup test tables after each test.
 */
function cleanupTableManagerTestTables(): void
{
    try {
        db()->getConnection()->executeStatement('DROP TABLE IF EXISTS user CASCADE; ');
    } catch (Exception) {
        // Ignore cleanup errors
    }
}

/**
 * Create test user data array.
 */
function createTestUserWithData(string $useUUID, array $additionalData = []): array
{
    $userData = [
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'age' => fake()->randomNumber(2, false),
        'address' => fake()->streetAddress(),
    ];

    return array_merge($userData, $additionalData);
}

/**
 * Retrieve user by ID from database.
 */
function retrieveUserById(string $useUUID, string|int $id): object
{
    $result = db($useUUID)->getOne('user', $id);
    if (null === $result) {
        throw new RuntimeException("User with ID {$id} not found in database");
    }

    return $result;
}

/**
 * Create test user data array (for TraitsTest).
 */
function createTestUserDataArray(): array
{
    return [
        'name' => fake()->name(),
        'email' => fake()->email(),
        'dob' => fake()->date(),
        'age' => fake()->randomNumber(2, false),
        'address' => fake()->streetAddress(),
    ];
}

/**
 * Create and save user for Traits tests (no UUID parameter needed).
 */
function createAndSaveUserForTraits(?array $data = null): object
{
    $userData = $data ?? createTestUserDataArray();

    $model = db()->create('user');
    foreach ($userData as $key => $value) {
        $model->$key = $value;
    }
    $model->save();

    return db()->find('user')->first();
}

/**
 * Create and save user with specific data (for QueryBuilder tests).
 */
function createAndSaveUser(string $useUUID, array $specificData = []): string|int
{
    $user = db($useUUID)->create('user');
    $user->name = $specificData['name'] ?? fake()->name();
    $user->email = $specificData['email'] ?? fake()->email();
    $user->age = $specificData['age'] ?? fake()->randomNumber(2, false);

    if (isset($specificData['dob'])) {
        $user->dob = $specificData['dob'];
    }
    if (isset($specificData['address'])) {
        $user->address = $specificData['address'];
    }

    return $user->save();
}

/**
 * Create multiple users for testing.
 */
function createMultipleUsers(string $useUUID, int $count): array
{
    $userIds = [];
    for ($i = 0; $i < $count; ++$i) {
        $userIds[] = createAndSaveUser($useUUID);
    }

    return $userIds;
}

/**
 * Compare model with database record.
 */
function compareModelWithDatabaseRecord(object $model, array $dbRecord): bool
{
    return json_encode($dbRecord) === $model->toString();
}

/**
 * Create multiple test users and return their IDs.
 */
function createMultipleTestUsers(string $useUUID, int $count, array $baseData = []): array
{
    $userIds = [];
    for ($i = 0; $i < $count; ++$i) {
        $user = createAndSaveTestUser($useUUID, $baseData);
        $userIds[] = $user->id;
    }

    return $userIds;
}

/**
 * Clean up common test tables for a specific configuration.
 */
function cleanupTestTables(string $useUUID): void
{
    try {
        $connection = db($useUUID)->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0;');

        // Get all existing tables first
        $tables = $connection->createSchemaManager()->listTableNames();

        // Drop all tables to ensure complete cleanup
        foreach ($tables as $table) {
            $connection->executeStatement("DROP TABLE IF EXISTS {$table} CASCADE;");
        }

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
    } catch (Exception) {
        // Ignore cleanup errors
    }
}

/**
 * Smart cleanup that only cleans all tables when switching between UUID and ID configurations.
 */
function cleanupAllTestTables(): void
{
    // Unknown state - clean both as fallback
    foreach (['UUID', 'ID'] as $config) {
        try {
            $connection = db($config)->getConnection();
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0;');

            // Get all existing tables first
            $tables = $connection->createSchemaManager()->listTableNames();

            // Drop all tables to ensure complete cleanup
            foreach ($tables as $table) {
                $connection->executeStatement("DROP TABLE IF EXISTS {$table} CASCADE;");
            }

            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
        } catch (Exception) {
            // Ignore cleanup errors
        }
    }
}
