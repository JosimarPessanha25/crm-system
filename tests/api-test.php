<?php

/**
 * CRM System API Integration Tests
 * Tests all API endpoints to ensure proper functionality
 */

require_once '../vendor/autoload.php';

class APITester {
    private $baseUrl;
    private $token;
    private $results = [];
    
    public function __construct($baseUrl) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }
    
    /**
     * Run all API tests
     */
    public function runAllTests() {
        echo "🚀 Starting CRM System API Tests\n";
        echo "=" . str_repeat("=", 50) . "\n\n";
        
        try {
            // Test authentication first
            $this->testAuthentication();
            
            // Test all endpoints
            $this->testUsersEndpoint();
            $this->testCompaniesEndpoint();
            $this->testContactsEndpoint();
            $this->testOpportunitiesEndpoint();
            $this->testActivitiesEndpoint();
            
            // Test dashboard endpoints
            $this->testDashboardEndpoints();
            
            // Display results
            $this->displayResults();
            
        } catch (Exception $e) {
            echo "❌ Fatal Error: " . $e->getMessage() . "\n";
            return false;
        }
        
        return $this->allTestsPassed();
    }
    
    /**
     * Test authentication endpoints
     */
    private function testAuthentication() {
        echo "🔐 Testing Authentication...\n";
        
        // Test login with valid credentials
        $loginData = [
            'email' => 'admin@crm.com',
            'password' => 'admin123'
        ];
        
        $response = $this->makeRequest('POST', '/auth/login', $loginData);
        
        if ($response && isset($response['success']) && $response['success']) {
            $this->token = $response['data']['token'];
            $this->recordResult('Auth Login', true, 'Login successful');
        } else {
            $this->recordResult('Auth Login', false, 'Login failed: ' . json_encode($response));
            throw new Exception('Authentication failed - cannot continue tests');
        }
        
        // Test token validation
        $profileResponse = $this->makeRequest('GET', '/auth/profile');
        $this->recordResult('Auth Profile', 
            isset($profileResponse['success']) && $profileResponse['success'],
            'Profile endpoint with JWT token'
        );
        
        echo "   ✅ Authentication tests completed\n\n";
    }
    
    /**
     * Test users endpoint
     */
    private function testUsersEndpoint() {
        echo "👥 Testing Users Endpoint...\n";
        
        // Test list users
        $response = $this->makeRequest('GET', '/users');
        $this->recordResult('Users List', 
            isset($response['success']) && $response['success'],
            'List users endpoint'
        );
        
        // Test create user
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'user'
        ];
        
        $createResponse = $this->makeRequest('POST', '/users', $userData);
        $userId = null;
        
        if (isset($createResponse['success']) && $createResponse['success']) {
            $userId = $createResponse['data']['id'];
            $this->recordResult('Users Create', true, 'User created successfully');
        } else {
            $this->recordResult('Users Create', false, 'Failed to create user');
        }
        
        // Test get user by ID
        if ($userId) {
            $getResponse = $this->makeRequest('GET', "/users/{$userId}");
            $this->recordResult('Users Get', 
                isset($getResponse['success']) && $getResponse['success'],
                'Get user by ID'
            );
            
            // Test update user
            $updateData = ['name' => 'Updated Test User'];
            $updateResponse = $this->makeRequest('PUT', "/users/{$userId}", $updateData);
            $this->recordResult('Users Update',
                isset($updateResponse['success']) && $updateResponse['success'],
                'Update user'
            );
            
            // Test delete user
            $deleteResponse = $this->makeRequest('DELETE', "/users/{$userId}");
            $this->recordResult('Users Delete',
                isset($deleteResponse['success']) && $deleteResponse['success'],
                'Delete user'
            );
        }
        
        echo "   ✅ Users endpoint tests completed\n\n";
    }
    
    /**
     * Test companies endpoint
     */
    private function testCompaniesEndpoint() {
        echo "🏢 Testing Companies Endpoint...\n";
        
        // Test list companies
        $response = $this->makeRequest('GET', '/companies');
        $this->recordResult('Companies List',
            isset($response['success']) && $response['success'],
            'List companies endpoint'
        );
        
        // Test create company
        $companyData = [
            'name' => 'Test Company Ltd',
            'email' => 'contact@testcompany.com',
            'phone' => '+55 11 99999-9999',
            'address' => 'Test Street, 123',
            'industry' => 'Technology',
            'size' => '50-200'
        ];
        
        $createResponse = $this->makeRequest('POST', '/companies', $companyData);
        $companyId = null;
        
        if (isset($createResponse['success']) && $createResponse['success']) {
            $companyId = $createResponse['data']['id'];
            $this->recordResult('Companies Create', true, 'Company created successfully');
        } else {
            $this->recordResult('Companies Create', false, 'Failed to create company');
        }
        
        // Test CRUD operations if company was created
        if ($companyId) {
            $this->testCrudOperations('companies', $companyId, ['name' => 'Updated Test Company']);
        }
        
        echo "   ✅ Companies endpoint tests completed\n\n";
    }
    
    /**
     * Test contacts endpoint
     */
    private function testContactsEndpoint() {
        echo "📞 Testing Contacts Endpoint...\n";
        
        // Test list contacts
        $response = $this->makeRequest('GET', '/contacts');
        $this->recordResult('Contacts List',
            isset($response['success']) && $response['success'],
            'List contacts endpoint'
        );
        
        // Test create contact
        $contactData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+55 11 88888-8888',
            'position' => 'Manager',
            'type' => 'lead',
            'status' => 'active'
        ];
        
        $createResponse = $this->makeRequest('POST', '/contacts', $contactData);
        $contactId = null;
        
        if (isset($createResponse['success']) && $createResponse['success']) {
            $contactId = $createResponse['data']['id'];
            $this->recordResult('Contacts Create', true, 'Contact created successfully');
        } else {
            $this->recordResult('Contacts Create', false, 'Failed to create contact');
        }
        
        // Test CRUD operations if contact was created
        if ($contactId) {
            $this->testCrudOperations('contacts', $contactId, ['name' => 'John Updated Doe']);
        }
        
        echo "   ✅ Contacts endpoint tests completed\n\n";
    }
    
    /**
     * Test opportunities endpoint
     */
    private function testOpportunitiesEndpoint() {
        echo "🎯 Testing Opportunities Endpoint...\n";
        
        // Test list opportunities
        $response = $this->makeRequest('GET', '/opportunities');
        $this->recordResult('Opportunities List',
            isset($response['success']) && $response['success'],
            'List opportunities endpoint'
        );
        
        // Test create opportunity
        $opportunityData = [
            'title' => 'Test Opportunity',
            'value' => 50000.00,
            'stage' => 'qualification',
            'probability' => 25,
            'expected_close_date' => date('Y-m-d', strtotime('+30 days')),
            'description' => 'Test opportunity for API testing'
        ];
        
        $createResponse = $this->makeRequest('POST', '/opportunities', $opportunityData);
        $opportunityId = null;
        
        if (isset($createResponse['success']) && $createResponse['success']) {
            $opportunityId = $createResponse['data']['id'];
            $this->recordResult('Opportunities Create', true, 'Opportunity created successfully');
        } else {
            $this->recordResult('Opportunities Create', false, 'Failed to create opportunity');
        }
        
        // Test CRUD operations if opportunity was created
        if ($opportunityId) {
            $this->testCrudOperations('opportunities', $opportunityId, ['title' => 'Updated Test Opportunity']);
        }
        
        echo "   ✅ Opportunities endpoint tests completed\n\n";
    }
    
    /**
     * Test activities endpoint
     */
    private function testActivitiesEndpoint() {
        echo "📅 Testing Activities Endpoint...\n";
        
        // Test list activities
        $response = $this->makeRequest('GET', '/activities');
        $this->recordResult('Activities List',
            isset($response['success']) && $response['success'],
            'List activities endpoint'
        );
        
        // Test create activity
        $activityData = [
            'type' => 'task',
            'title' => 'Test Task',
            'description' => 'This is a test task for API testing',
            'due_date' => date('Y-m-d'),
            'due_time' => '14:00:00',
            'status' => 'pending',
            'priority' => 'medium'
        ];
        
        $createResponse = $this->makeRequest('POST', '/activities', $activityData);
        $activityId = null;
        
        if (isset($createResponse['success']) && $createResponse['success']) {
            $activityId = $createResponse['data']['id'];
            $this->recordResult('Activities Create', true, 'Activity created successfully');
        } else {
            $this->recordResult('Activities Create', false, 'Failed to create activity');
        }
        
        // Test CRUD operations if activity was created
        if ($activityId) {
            $this->testCrudOperations('activities', $activityId, ['title' => 'Updated Test Task']);
        }
        
        echo "   ✅ Activities endpoint tests completed\n\n";
    }
    
    /**
     * Test dashboard endpoints
     */
    private function testDashboardEndpoints() {
        echo "📊 Testing Dashboard Endpoints...\n";
        
        // Test dashboard stats
        $statsResponse = $this->makeRequest('GET', '/dashboard/stats');
        $this->recordResult('Dashboard Stats',
            isset($statsResponse['success']) && $statsResponse['success'],
            'Dashboard statistics endpoint'
        );
        
        // Test recent activities
        $activitiesResponse = $this->makeRequest('GET', '/dashboard/recent-activities');
        $this->recordResult('Dashboard Recent Activities',
            isset($activitiesResponse['success']) && $activitiesResponse['success'],
            'Dashboard recent activities endpoint'
        );
        
        echo "   ✅ Dashboard endpoint tests completed\n\n";
    }
    
    /**
     * Test CRUD operations for a resource
     */
    private function testCrudOperations($resource, $id, $updateData) {
        // Test GET by ID
        $getResponse = $this->makeRequest('GET', "/{$resource}/{$id}");
        $this->recordResult(ucfirst($resource) . ' Get',
            isset($getResponse['success']) && $getResponse['success'],
            'Get ' . $resource . ' by ID'
        );
        
        // Test UPDATE
        $updateResponse = $this->makeRequest('PUT', "/{$resource}/{$id}", $updateData);
        $this->recordResult(ucfirst($resource) . ' Update',
            isset($updateResponse['success']) && $updateResponse['success'],
            'Update ' . $resource
        );
        
        // Test DELETE
        $deleteResponse = $this->makeRequest('DELETE', "/{$resource}/{$id}");
        $this->recordResult(ucfirst($resource) . ' Delete',
            isset($deleteResponse['success']) && $deleteResponse['success'],
            'Delete ' . $resource
        );
    }
    
    /**
     * Make HTTP request to API
     */
    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->baseUrl . '/api' . $endpoint;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                $this->token ? 'Authorization: Bearer ' . $this->token : ''
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response: " . $response);
        }
        
        return $decoded;
    }
    
    /**
     * Record test result
     */
    private function recordResult($test, $passed, $description) {
        $this->results[] = [
            'test' => $test,
            'passed' => $passed,
            'description' => $description
        ];
    }
    
    /**
     * Display test results
     */
    private function displayResults() {
        echo "📋 Test Results Summary\n";
        echo "=" . str_repeat("=", 50) . "\n\n";
        
        $passed = 0;
        $failed = 0;
        
        foreach ($this->results as $result) {
            $status = $result['passed'] ? '✅' : '❌';
            echo sprintf("   %s %s: %s\n", 
                $status, 
                $result['test'], 
                $result['description']
            );
            
            if ($result['passed']) {
                $passed++;
            } else {
                $failed++;
            }
        }
        
        $total = count($this->results);
        $percentage = $total > 0 ? round(($passed / $total) * 100, 1) : 0;
        
        echo "\n" . str_repeat("-", 50) . "\n";
        echo "📊 Results: {$passed} passed, {$failed} failed ({$percentage}%)\n";
        
        if ($failed === 0) {
            echo "🎉 All tests passed! API is working correctly.\n";
        } else {
            echo "⚠️  Some tests failed. Please check the API implementation.\n";
        }
    }
    
    /**
     * Check if all tests passed
     */
    private function allTestsPassed() {
        foreach ($this->results as $result) {
            if (!$result['passed']) {
                return false;
            }
        }
        return true;
    }
}

// Run tests if called directly
if (php_sapi_name() === 'cli') {
    $baseUrl = $argv[1] ?? 'http://localhost:8000';
    
    echo "CRM System API Integration Tests\n";
    echo "Base URL: {$baseUrl}\n\n";
    
    $tester = new APITester($baseUrl);
    $success = $tester->runAllTests();
    
    exit($success ? 0 : 1);
}