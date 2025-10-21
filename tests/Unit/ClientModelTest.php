<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\ClientModel;
use PDO;

class ClientModelTest extends TestCase
{
    private $pdo;
    private $clientModel;

    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create clients table
        $this->pdo->exec("
            CREATE TABLE clients (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE,
                phone_number VARCHAR(20),
                address TEXT,
                date_of_birth DATE,
                status VARCHAR(20) DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->clientModel = new ClientModel($this->pdo);
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
    }

    public function testCreateClient()
    {
        $clientData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone_number' => '+1234567890',
            'address' => '123 Main St',
            'date_of_birth' => '1990-01-01'
        ];

        $clientId = $this->clientModel->create($clientData);

        $this->assertIsInt($clientId);
        $this->assertGreaterThan(0, $clientId);
    }

    public function testFindById()
    {
        // Create a test client
        $clientData = [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone_number' => '+0987654321',
            'address' => '456 Oak Ave',
            'date_of_birth' => '1985-05-15'
        ];

        $clientId = $this->clientModel->create($clientData);
        $client = $this->clientModel->findById($clientId);

        $this->assertIsArray($client);
        $this->assertEquals($clientId, $client['id']);
        $this->assertEquals('Jane Smith', $client['name']);
        $this->assertEquals('jane@example.com', $client['email']);
    }

    public function testFindByIdReturnsNullForNonExistentClient()
    {
        $client = $this->clientModel->findById(999);

        $this->assertNull($client);
    }

    public function testUpdateClient()
    {
        // Create a test client
        $clientData = [
            'name' => 'Bob Wilson',
            'email' => 'bob@example.com',
            'phone_number' => '+1111111111',
            'address' => '789 Pine St',
            'date_of_birth' => '1975-12-31'
        ];

        $clientId = $this->clientModel->create($clientData);

        // Update the client
        $updateData = [
            'name' => 'Robert Wilson',
            'phone_number' => '+2222222222'
        ];

        $result = $this->clientModel->update($clientId, $updateData);

        $this->assertTrue($result);

        // Verify the update
        $updatedClient = $this->clientModel->findById($clientId);
        $this->assertEquals('Robert Wilson', $updatedClient['name']);
        $this->assertEquals('+2222222222', $updatedClient['phone_number']);
        $this->assertEquals('bob@example.com', $updatedClient['email']); // Should remain unchanged
    }

    public function testDeleteClient()
    {
        // Create a test client
        $clientData = [
            'name' => 'Alice Brown',
            'email' => 'alice@example.com',
            'phone_number' => '+3333333333',
            'address' => '321 Elm St',
            'date_of_birth' => '1995-08-20'
        ];

        $clientId = $this->clientModel->create($clientData);

        // Delete the client
        $result = $this->clientModel->delete($clientId);

        $this->assertTrue($result);

        // Verify the client is deleted
        $deletedClient = $this->clientModel->findById($clientId);
        $this->assertNull($deletedClient);
    }

    public function testGetAllWithPagination()
    {
        // Create multiple test clients
        for ($i = 1; $i <= 5; $i++) {
            $this->clientModel->create([
                'name' => "Client $i",
                'email' => "client$i@example.com",
                'phone_number' => "+123456789$i",
                'address' => "$i Test St",
                'date_of_birth' => '1990-01-01'
            ]);
        }

        $clients = $this->clientModel->getAll(1, 3);

        $this->assertCount(3, $clients);
        $this->assertEquals('Client 1', $clients[0]['name']);
        $this->assertEquals('Client 2', $clients[1]['name']);
        $this->assertEquals('Client 3', $clients[2]['name']);
    }

    public function testGetTotalCount()
    {
        // Create test clients
        for ($i = 1; $i <= 3; $i++) {
            $this->clientModel->create([
                'name' => "Count Client $i",
                'email' => "count$i@example.com",
                'phone_number' => "+987654321$i",
                'address' => "$i Count Ave",
                'date_of_birth' => '1990-01-01'
            ]);
        }

        $totalCount = $this->clientModel->getTotalCount();

        $this->assertEquals(3, $totalCount);
    }

    public function testSearchClients()
    {
        // Create test clients
        $this->clientModel->create([
            'name' => 'John Search',
            'email' => 'john.search@example.com',
            'phone_number' => '+1111111111',
            'address' => '123 Search St',
            'date_of_birth' => '1990-01-01'
        ]);

        $this->clientModel->create([
            'name' => 'Jane Other',
            'email' => 'jane.other@example.com',
            'phone_number' => '+2222222222',
            'address' => '456 Other Ave',
            'date_of_birth' => '1985-05-15'
        ]);

        // Search by name
        $results = $this->clientModel->search('John');

        $this->assertCount(1, $results);
        $this->assertEquals('John Search', $results[0]['name']);
    }

    public function testGetActiveClients()
    {
        // Create active client
        $this->clientModel->create([
            'name' => 'Active Client',
            'email' => 'active@example.com',
            'phone_number' => '+1111111111',
            'address' => '123 Active St',
            'date_of_birth' => '1990-01-01',
            'status' => 'active'
        ]);

        // Create inactive client
        $this->clientModel->create([
            'name' => 'Inactive Client',
            'email' => 'inactive@example.com',
            'phone_number' => '+2222222222',
            'address' => '456 Inactive Ave',
            'date_of_birth' => '1985-05-15',
            'status' => 'inactive'
        ]);

        $activeClients = $this->clientModel->getActiveClients();

        $this->assertCount(1, $activeClients);
        $this->assertEquals('Active Client', $activeClients[0]['name']);
    }
}
