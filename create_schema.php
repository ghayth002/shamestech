<?php
// create_schema.php
require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Dotenv\Dotenv;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\SchemaTool;

// Load environment variables
(new Dotenv())->bootEnv(__DIR__ . '/.env');

// Get application kernel
require_once __DIR__ . '/src/Kernel.php';
$kernel = new \App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

// Get the entity manager
$entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');

// Create schema tool
$tool = new SchemaTool($entityManager);
$classes = $entityManager->getMetadataFactory()->getAllMetadata();

try {
    // Drop and recreate schema
    $tool->dropSchema($classes);
    $tool->createSchema($classes);
    
    echo "Database schema created successfully!\n";
} catch (\Exception $e) {
    echo "An error occurred while creating the database schema: " . $e->getMessage() . "\n";
}
