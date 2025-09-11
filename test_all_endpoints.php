<?php

/**
 * Comprehensive API Endpoint Testing Script
 * Tests all Fundi API endpoints with proper authentication and validation
 */

class FundiApiTester
{
    private $baseUrl = 'http://localhost:8000/api/v1';
    private $token = null;
    private $testResults = [];
    private $testUser = [
        'phone' => '+255123456789',
        'password' => 'password123'
    ];

    public function __construct()
    {
        echo "🚀 Starting Fundi API Comprehensive Testing\n";
        echo "==========================================\n\n";
    }

    private function makeRequest($method, $endpoint, $data = null, $headers = [])
    {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $headers[] = 'Content-Type: application/json';
        }
        
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        
        $headers[] = 'Accept: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        return [
            'http_code' => $httpCode,
            'response' => $response,
            'error' => $error
        ];
    }

    private function logTest($testName, $endpoint, $method, $expectedCode, $actualCode, $response, $passed)
    {
        $status = $passed ? '✅ PASS' : '❌ FAIL';
        echo sprintf("%-50s %s\n", $testName, $status);
        
        if (!$passed) {
            echo "   Expected: HTTP $expectedCode\n";
            echo "   Actual: HTTP $actualCode\n";
            echo "   Response: " . substr($response, 0, 200) . (strlen($response) > 200 ? '...' : '') . "\n";
        }
        
        $this->testResults[] = [
            'test' => $testName,
            'endpoint' => $endpoint,
            'method' => $method,
            'expected_code' => $expectedCode,
            'actual_code' => $actualCode,
            'passed' => $passed,
            'response' => $response
        ];
        
        echo "\n";
    }

    public function testPublicEndpoints()
    {
        echo "🌐 TESTING PUBLIC ENDPOINTS\n";
        echo "==========================\n\n";

        // Test 1: Basic API Health Check
        $result = $this->makeRequest('GET', '/test');
        $this->logTest(
            'API Health Check',
            '/test',
            'GET',
            200,
            $result['http_code'],
            $result['response'],
            $result['http_code'] === 200
        );

        // Test 2: Debug Auth Endpoint
        $result = $this->makeRequest('GET', '/debug/auth');
        $this->logTest(
            'Debug Auth Endpoint',
            '/debug/auth',
            'GET',
            200,
            $result['http_code'],
            $result['response'],
            $result['http_code'] === 200
        );
    }

    public function testAuthenticationEndpoints()
    {
        echo "🔐 TESTING AUTHENTICATION ENDPOINTS\n";
        echo "===================================\n\n";

        // Test 1: User Registration
        $registerData = [
            'phone' => '+255999888' . rand(100, 999),
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
            'nida_number' => '99988877766655544' . rand(100, 999)
        ];
        
        $result = $this->makeRequest('POST', '/auth/register', $registerData);
        $this->logTest(
            'User Registration',
            '/auth/register',
            'POST',
            201,
            $result['http_code'],
            $result['response'],
            $result['http_code'] === 201
        );

        // Test 2: User Login
        $loginData = [
            'phone' => $this->testUser['phone'],
            'password' => $this->testUser['password']
        ];
        
        $result = $this->makeRequest('POST', '/auth/login', $loginData);
        $loginPassed = $result['http_code'] === 200;
        
        if ($loginPassed) {
            $responseData = json_decode($result['response'], true);
            if (isset($responseData['data']['token'])) {
                $this->token = $responseData['data']['token'];
            }
        }
        
        $this->logTest(
            'User Login',
            '/auth/login',
            'POST',
            200,
            $result['http_code'],
            $result['response'],
            $loginPassed
        );

        // Test 3: Get User Profile (Me)
        $result = $this->makeRequest('GET', '/auth/me');
        $this->logTest(
            'Get User Profile',
            '/auth/me',
            'GET',
            200,
            $result['http_code'],
            $result['response'],
            $result['http_code'] === 200
        );

        // Test 4: Token Info
        if ($this->token) {
            $tokenData = ['token' => $this->token];
            $result = $this->makeRequest('POST', '/auth/token-info', $tokenData);
            $this->logTest(
                'Token Information',
                '/auth/token-info',
                'POST',
                200,
                $result['http_code'],
                $result['response'],
                $result['http_code'] === 200
            );
        }
    }

    public function testProtectedEndpoints()
    {
        if (!$this->token) {
            echo "❌ Cannot test protected endpoints - no valid token\n\n";
            return;
        }

        echo "🛡️ TESTING PROTECTED ENDPOINTS\n";
        echo "==============================\n\n";

        // Test 1: Protected Test Route
        $result = $this->makeRequest('GET', '/test-protected');
        $this->logTest(
            'Protected Test Route',
            '/test-protected',
            'GET',
            200,
            $result['http_code'],
            $result['response'],
            $result['http_code'] === 200
        );

        // Test 2: Categories
        $result = $this->makeRequest('GET', '/categories');
        $this->logTest(
            'Get Categories',
            '/categories',
            'GET',
            200,
            $result['http_code'],
            $result['response'],
            $result['http_code'] === 200
        );

        // Test 3: Jobs
        $result = $this->makeRequest('GET', '/jobs');
        $this->logTest(
            'Get Jobs',
            '/jobs',
            'GET',
            200,
            $result['http_code'],
            $result['response'],
            $result['http_code'] === 200
        );

        // Test 4: Notifications
        $result = $this->makeRequest('GET', '/notifications');
        $this->logTest(
            'Get Notifications',
            '/notifications',
            'GET',
            200,
            $result['http_code'],
            $result['response'],
            $result['http_code'] === 200
        );

        // Test 5: User Profile (Alternative)
        $result = $this->makeRequest('GET', '/users/me');
        $this->logTest(
            'Get User Profile (Alternative)',
            '/users/me',
            'GET',
            200,
            $result['http_code'],
            $result['response'],
            $result['http_code'] === 200
        );

        // Test 6: My Applications
        $result = $this->makeRequest('GET', '/my-applications');
        $this->logTest(
            'Get My Applications',
            '/my-applications',
            'GET',
            200,
            $result['http_code'],
            $result['response'],
            $result['http_code'] === 200
        );

        // Test 7: Payments
        $result = $this->makeRequest('GET', '/payments');
        $this->logTest(
            'Get Payments',
            '/payments',
            'GET',
            200,
            $result['http_code'],
            $result['response'],
            $result['http_code'] === 200
        );

        // Test 8: My Ratings
        $result = $this->makeRequest('GET', '/ratings/my-ratings');
        $this->logTest(
            'Get My Ratings',
            '/ratings/my-ratings',
            'GET',
            200,
            $result['http_code'],
            $result['response'],
            $result['http_code'] === 200
        );
    }

    public function testJobEndpoints()
    {
        if (!$this->token) {
            echo "❌ Cannot test job endpoints - no valid token\n\n";
            return;
        }

        echo "💼 TESTING JOB ENDPOINTS\n";
        echo "========================\n\n";

        // Test 1: Get Specific Job
        $result = $this->makeRequest('GET', '/jobs/1');
        $this->logTest(
            'Get Specific Job',
            '/jobs/1',
            'GET',
            200,
            $result['http_code'],
            $result['response'],
            $result['http_code'] === 200
        );

        // Test 2: Get Job Applications
        $result = $this->makeRequest('GET', '/jobs/1/applications');
        $this->logTest(
            'Get Job Applications',
            '/jobs/1/applications',
            'GET',
            200,
            $result['http_code'],
            $result['response'],
            $result['http_code'] === 200
        );

        // Test 3: Create New Job
        $jobData = [
            'title' => 'Test Job from Script',
            'description' => 'This is a test job created by the testing script',
            'category_id' => 1,
            'budget' => 50000,
            'budget_type' => 'fixed',
            'deadline' => '2025-12-31',
            'location_lat' => -6.7924,
            'location_lng' => 39.2083
        ];
        
        $result = $this->makeRequest('POST', '/jobs', $jobData);
        $this->logTest(
            'Create New Job',
            '/jobs',
            'POST',
            201,
            $result['http_code'],
            $result['response'],
            $result['http_code'] === 201
        );
    }

    public function testAdminEndpoints()
    {
        if (!$this->token) {
            echo "❌ Cannot test admin endpoints - no valid token\n\n";
            return;
        }

        echo "👑 TESTING ADMIN ENDPOINTS\n";
        echo "==========================\n\n";

        // Test 1: Get All Users
        $result = $this->makeRequest('GET', '/admin/users');
        $this->logTest(
            'Get All Users (Admin)',
            '/admin/users',
            'GET',
            200,
            $result['http_code'],
            $result['response'],
            $result['http_code'] === 200
        );

        // Test 2: Get All Jobs (Admin)
        $result = $this->makeRequest('GET', '/admin/jobs');
        $this->logTest(
            'Get All Jobs (Admin)',
            '/admin/jobs',
            'GET',
            200,
            $result['http_code'],
            $result['response'],
            $result['http_code'] === 200
        );

        // Test 3: Get All Payments (Admin)
        $result = $this->makeRequest('GET', '/admin/payments');
        $this->logTest(
            'Get All Payments (Admin)',
            '/admin/payments',
            'GET',
            200,
            $result['http_code'],
            $result['response'],
            $result['http_code'] === 200
        );

        // Test 4: Get System Health
        $result = $this->makeRequest('GET', '/admin/monitor/system-health');
        $this->logTest(
            'Get System Health',
            '/admin/monitor/system-health',
            'GET',
            200,
            $result['http_code'],
            $result['response'],
            $result['http_code'] === 200
        );

        // Test 5: Get Settings
        $result = $this->makeRequest('GET', '/admin/settings');
        $this->logTest(
            'Get Settings',
            '/admin/settings',
            'GET',
            200,
            $result['http_code'],
            $result['response'],
            $result['http_code'] === 200
        );
    }

    public function testErrorHandling()
    {
        echo "⚠️ TESTING ERROR HANDLING\n";
        echo "=========================\n\n";

        // Test 1: Access Protected Route Without Token
        $originalToken = $this->token;
        $this->token = null; // Clear token for this test
        $result = $this->makeRequest('GET', '/auth/me');
        $this->logTest(
            'Access Protected Route Without Token',
            '/auth/me',
            'GET',
            401,
            $result['http_code'],
            $result['response'],
            $result['http_code'] === 401
        );
        $this->token = $originalToken; // Restore token for other tests

        // Test 2: Access Protected Route With Invalid Token
        $this->token = 'invalid_token_123';
        $result = $this->makeRequest('GET', '/test-protected');
        $this->logTest(
            'Access Protected Route With Invalid Token',
            '/test-protected',
            'GET',
            401,
            $result['http_code'],
            $result['response'],
            $result['http_code'] === 401
        );

        // Test 3: Login With Invalid Credentials
        $invalidLoginData = [
            'phone' => '+255999999999',
            'password' => 'wrongpassword'
        ];
        
        $result = $this->makeRequest('POST', '/auth/login', $invalidLoginData);
        $this->logTest(
            'Login With Invalid Credentials',
            '/auth/login',
            'POST',
            401,
            $result['http_code'],
            $result['response'],
            $result['http_code'] === 401
        );

        // Test 4: Access Non-existent Endpoint
        $result = $this->makeRequest('GET', '/non-existent-endpoint');
        $this->logTest(
            'Access Non-existent Endpoint',
            '/non-existent-endpoint',
            'GET',
            404,
            $result['http_code'],
            $result['response'],
            $result['http_code'] === 404
        );

        // Reset token for logout test
        $this->token = null;
    }

    public function testLogout()
    {
        if (!$this->token) {
            // Get a fresh token for logout test
            $loginData = [
                'phone' => $this->testUser['phone'],
                'password' => $this->testUser['password']
            ];
            
            $result = $this->makeRequest('POST', '/auth/login', $loginData);
            if ($result['http_code'] === 200) {
                $responseData = json_decode($result['response'], true);
                if (isset($responseData['data']['token'])) {
                    $this->token = $responseData['data']['token'];
                }
            }
        }

        if (!$this->token) {
            echo "❌ Cannot test logout - no valid token\n\n";
            return;
        }

        echo "🚪 TESTING LOGOUT\n";
        echo "=================\n\n";

        // Test 1: Logout
        $result = $this->makeRequest('POST', '/auth/logout');
        $this->logTest(
            'User Logout',
            '/auth/logout',
            'POST',
            200,
            $result['http_code'],
            $result['response'],
            $result['http_code'] === 200
        );

        // Test 2: Access Protected Route After Logout
        $result = $this->makeRequest('GET', '/auth/me');
        $this->logTest(
            'Access Protected Route After Logout',
            '/auth/me',
            'GET',
            401,
            $result['http_code'],
            $result['response'],
            $result['http_code'] === 401
        );
    }

    public function generateReport()
    {
        echo "📊 TESTING REPORT\n";
        echo "=================\n\n";

        $totalTests = count($this->testResults);
        $passedTests = array_filter($this->testResults, function($test) {
            return $test['passed'];
        });
        $failedTests = array_filter($this->testResults, function($test) {
            return !$test['passed'];
        });

        $passedCount = count($passedTests);
        $failedCount = count($failedTests);
        $successRate = $totalTests > 0 ? round(($passedCount / $totalTests) * 100, 2) : 0;

        echo "Total Tests: $totalTests\n";
        echo "Passed: $passedCount ✅\n";
        echo "Failed: $failedCount ❌\n";
        echo "Success Rate: $successRate%\n\n";

        if ($failedCount > 0) {
            echo "❌ FAILED TESTS:\n";
            echo "================\n";
            foreach ($failedTests as $test) {
                echo "- {$test['test']} ({$test['method']} {$test['endpoint']})\n";
                echo "  Expected: HTTP {$test['expected_code']}, Got: HTTP {$test['actual_code']}\n";
            }
            echo "\n";
        }

        echo "🎯 SUMMARY:\n";
        echo "===========\n";
        if ($successRate >= 95) {
            echo "🌟 EXCELLENT! API is performing exceptionally well!\n";
        } elseif ($successRate >= 90) {
            echo "✅ GOOD! API is working well with minor issues.\n";
        } elseif ($successRate >= 80) {
            echo "⚠️ FAIR! API has some issues that need attention.\n";
        } else {
            echo "❌ POOR! API has significant issues that need immediate attention.\n";
        }

        // Save detailed report to file
        $reportData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_tests' => $totalTests,
            'passed_tests' => $passedCount,
            'failed_tests' => $failedCount,
            'success_rate' => $successRate,
            'test_results' => $this->testResults
        ];

        file_put_contents('test_report_' . date('Y-m-d_H-i-s') . '.json', json_encode($reportData, JSON_PRETTY_PRINT));
        echo "\n📄 Detailed report saved to: test_report_" . date('Y-m-d_H-i-s') . ".json\n";
    }

    public function runAllTests()
    {
        $this->testPublicEndpoints();
        $this->testAuthenticationEndpoints();
        $this->testProtectedEndpoints();
        $this->testJobEndpoints();
        $this->testAdminEndpoints();
        $this->testErrorHandling();
        $this->testLogout();
        $this->generateReport();
    }
}

// Run the tests
$tester = new FundiApiTester();
$tester->runAllTests();

?>
