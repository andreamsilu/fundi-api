<?php

/**
 * Mobile Core API Testing Script
 * Tests essential mobile endpoints with proper role-based access
 */

class MobileCoreTester
{
    private $baseUrl = 'http://localhost:8000/api/v1';
    private $tokens = [];
    private $testResults = [];
    
    // Test users for each role
    private $testUsers = [
        'admin' => ['phone' => '+255123456789', 'password' => 'password123'],
        'customer' => ['phone' => '+255111111111', 'password' => 'password123'],
        'fundi' => ['phone' => '+255222222222', 'password' => 'password123']
    ];

    public function __construct()
    {
        echo "📱 Mobile Core API Testing\n";
        echo "==========================\n\n";
    }

    private function makeRequest($method, $endpoint, $data = null, $role = null)
    {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        $headers = ['Accept: application/json'];
        
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $headers[] = 'Content-Type: application/json';
        }
        
        if ($role && isset($this->tokens[$role])) {
            $headers[] = 'Authorization: Bearer ' . $this->tokens[$role];
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ['http_code' => $httpCode, 'response' => $response];
    }

    private function logTest($testName, $endpoint, $method, $role, $expectedCode, $actualCode, $response, $passed)
    {
        $status = $passed ? '✅ PASS' : '❌ FAIL';
        $roleIcon = $this->getRoleIcon($role);
        echo sprintf("%-50s %s %s\n", $testName, $roleIcon, $status);
        
        if (!$passed && $actualCode !== $expectedCode) {
            echo "   Expected: HTTP $expectedCode, Got: HTTP $actualCode\n";
        }
        
        $this->testResults[] = [
            'test' => $testName,
            'endpoint' => $endpoint,
            'method' => $method,
            'role' => $role,
            'expected_code' => $expectedCode,
            'actual_code' => $actualCode,
            'passed' => $passed
        ];
        
        echo "\n";
    }

    private function getRoleIcon($role)
    {
        $icons = ['admin' => '👑', 'customer' => '👤', 'fundi' => '🔧', 'public' => '🌐'];
        return $icons[$role] ?? '❓';
    }

    private function authenticateUser($role)
    {
        if (isset($this->tokens[$role])) return true;

        $user = $this->testUsers[$role];
        $result = $this->makeRequest('POST', '/auth/login', $user);
        
        if ($result['http_code'] === 200) {
            $responseData = json_decode($result['response'], true);
            if (isset($responseData['data']['token'])) {
                $this->tokens[$role] = $responseData['data']['token'];
                return true;
            }
        }
        return false;
    }

    public function testPublicEndpoints()
    {
        echo "🌐 PUBLIC ENDPOINTS\n";
        echo "==================\n\n";

        $tests = [
            ['API Health Check', '/test', 'GET', 'public', 200],
            ['Debug Auth', '/debug/auth', 'GET', 'public', 200]
        ];

        foreach ($tests as $test) {
            $result = $this->makeRequest($test[2], $test[1]);
            $this->logTest($test[0], $test[1], $test[2], $test[3], $test[4], $result['http_code'], $result['response'], $result['http_code'] === $test[4]);
        }
    }

    public function testAuthentication()
    {
        echo "🔐 AUTHENTICATION\n";
        echo "=================\n\n";

        // Test login for each role
        foreach (['admin', 'customer', 'fundi'] as $role) {
            $user = $this->testUsers[$role];
            $result = $this->makeRequest('POST', '/auth/login', $user);
            $success = $result['http_code'] === 200;
            
            if ($success) {
                $responseData = json_decode($result['response'], true);
                if (isset($responseData['data']['token'])) {
                    $this->tokens[$role] = $responseData['data']['token'];
                }
            }
            
            $this->logTest("Login as $role", '/auth/login', 'POST', $role, 200, $result['http_code'], $result['response'], $success);
        }
    }

    public function testCoreEndpoints()
    {
        echo "📱 CORE MOBILE ENDPOINTS\n";
        echo "========================\n\n";

        $coreTests = [
            // Authentication & Profile
            ['Get Profile (Admin)', '/auth/me', 'GET', 'admin', 200],
            ['Get Profile (Customer)', '/auth/me', 'GET', 'customer', 200],
            ['Get Profile (Fundi)', '/auth/me', 'GET', 'fundi', 200],
            
            // Categories (accessible to all authenticated users)
            ['Get Categories (Admin)', '/categories', 'GET', 'admin', 200],
            ['Get Categories (Customer)', '/categories', 'GET', 'customer', 200],
            ['Get Categories (Fundi)', '/categories', 'GET', 'fundi', 200],
            
            // Jobs (accessible to all authenticated users)
            ['Get Jobs (Admin)', '/jobs', 'GET', 'admin', 200],
            ['Get Jobs (Customer)', '/jobs', 'GET', 'customer', 200],
            ['Get Jobs (Fundi)', '/jobs', 'GET', 'fundi', 200],
            
            // Notifications (accessible to all authenticated users)
            ['Get Notifications (Admin)', '/notifications', 'GET', 'admin', 200],
            ['Get Notifications (Customer)', '/notifications', 'GET', 'customer', 200],
            ['Get Notifications (Fundi)', '/notifications', 'GET', 'fundi', 200],
            
            // User Profile (accessible to all authenticated users)
            ['Get User Profile (Admin)', '/users/me', 'GET', 'admin', 200],
            ['Get User Profile (Customer)', '/users/me', 'GET', 'customer', 200],
            ['Get User Profile (Fundi)', '/users/me', 'GET', 'fundi', 200]
        ];

        foreach ($coreTests as $test) {
            $result = $this->makeRequest($test[2], $test[1], null, $test[3]);
            $this->logTest($test[0], $test[1], $test[2], $test[3], $test[4], $result['http_code'], $result['response'], $result['http_code'] === $test[4]);
        }
    }

    public function testRoleSpecificEndpoints()
    {
        echo "🎯 ROLE-SPECIFIC ENDPOINTS\n";
        echo "==========================\n\n";

        // Customer-specific endpoints
        $customerTests = [
            ['Create Job (Customer)', '/jobs', 'POST', 'customer', 201],
            ['Get My Applications (Customer)', '/my-applications', 'GET', 'customer', 200],
            ['Get My Ratings (Customer)', '/ratings/my-ratings', 'GET', 'customer', 200]
        ];

        echo "👤 CUSTOMER ENDPOINTS:\n";
        foreach ($customerTests as $test) {
            $result = $this->makeRequest($test[2], $test[1], null, $test[3]);
            $this->logTest($test[0], $test[1], $test[2], $test[3], $test[4], $result['http_code'], $result['response'], $result['http_code'] === $test[4]);
        }

        // Fundi-specific endpoints
        $fundiTests = [
            ['Get My Applications (Fundi)', '/my-applications', 'GET', 'fundi', 200],
            ['Update Fundi Profile (Fundi)', '/users/me/fundi-profile', 'PATCH', 'fundi', 200]
        ];

        echo "\n🔧 FUNDI ENDPOINTS:\n";
        foreach ($fundiTests as $test) {
            $result = $this->makeRequest($test[2], $test[1], null, $test[3]);
            $this->logTest($test[0], $test[1], $test[2], $test[3], $test[4], $result['http_code'], $result['response'], $result['http_code'] === $test[4]);
        }

        // Admin-specific endpoints
        $adminTests = [
            ['Get All Users (Admin)', '/admin/users', 'GET', 'admin', 200],
            ['Get All Jobs (Admin)', '/admin/jobs', 'GET', 'admin', 200],
            ['Get All Payments (Admin)', '/admin/payments', 'GET', 'admin', 200],
            ['Get System Health (Admin)', '/admin/monitor/system-health', 'GET', 'admin', 200],
            ['Get Settings (Admin)', '/admin/settings', 'GET', 'admin', 200]
        ];

        echo "\n👑 ADMIN ENDPOINTS:\n";
        foreach ($adminTests as $test) {
            $result = $this->makeRequest($test[2], $test[1], null, $test[3]);
            $this->logTest($test[0], $test[1], $test[2], $test[3], $test[4], $result['http_code'], $result['response'], $result['http_code'] === $test[4]);
        }
    }

    public function testRoleAccessControl()
    {
        echo "🛡️ ROLE ACCESS CONTROL\n";
        echo "======================\n\n";

        $accessTests = [
            // Customer trying to access Fundi-only endpoints
            ['Customer → Fundi Applications', '/my-applications', 'GET', 'customer', 403],
            ['Customer → Fundi Profile Update', '/users/me/fundi-profile', 'PATCH', 'customer', 403],
            
            // Fundi trying to access Customer-only endpoints
            ['Fundi → Customer Ratings', '/ratings/my-ratings', 'GET', 'fundi', 403],
            
            // Non-admin trying to access Admin endpoints
            ['Customer → Admin Users', '/admin/users', 'GET', 'customer', 403],
            ['Fundi → Admin Settings', '/admin/settings', 'GET', 'fundi', 403]
        ];

        foreach ($accessTests as $test) {
            $result = $this->makeRequest($test[2], $test[1], null, $test[3]);
            $this->logTest($test[0], $test[1], $test[2], $test[3], $test[4], $result['http_code'], $result['response'], $result['http_code'] === $test[4]);
        }
    }

    public function testErrorHandling()
    {
        echo "⚠️ ERROR HANDLING\n";
        echo "=================\n\n";

        $errorTests = [
            ['Access without token', '/auth/me', 'GET', 'public', 401],
            ['Invalid token', '/auth/me', 'GET', 'public', 401],
            ['Non-existent endpoint', '/non-existent', 'GET', 'public', 404],
            ['Invalid login', '/auth/login', 'POST', 'public', 401]
        ];

        // Test without token
        $result = $this->makeRequest('GET', '/auth/me');
        $this->logTest('Access without token', '/auth/me', 'GET', 'public', 401, $result['http_code'], $result['response'], $result['http_code'] === 401);

        // Test with invalid token
        $this->tokens['test'] = 'invalid_token';
        $result = $this->makeRequest('GET', '/auth/me', null, 'test');
        $this->logTest('Invalid token', '/auth/me', 'GET', 'public', 401, $result['http_code'], $result['response'], $result['http_code'] === 401);

        // Test non-existent endpoint
        $result = $this->makeRequest('GET', '/non-existent');
        $this->logTest('Non-existent endpoint', '/non-existent', 'GET', 'public', 404, $result['http_code'], $result['response'], $result['http_code'] === 404);

        // Test invalid login
        $result = $this->makeRequest('POST', '/auth/login', ['phone' => '+255999999999', 'password' => 'wrong']);
        $this->logTest('Invalid login', '/auth/login', 'POST', 'public', 401, $result['http_code'], $result['response'], $result['http_code'] === 401);
    }

    public function generateReport()
    {
        echo "📊 MOBILE CORE TESTING REPORT\n";
        echo "==============================\n\n";

        $totalTests = count($this->testResults);
        $passedTests = array_filter($this->testResults, function($test) { return $test['passed']; });
        $failedTests = array_filter($this->testResults, function($test) { return !$test['passed']; });

        $passedCount = count($passedTests);
        $failedCount = count($failedTests);
        $successRate = $totalTests > 0 ? round(($passedCount / $totalTests) * 100, 2) : 0;

        // Group by role
        $roleStats = [];
        foreach ($this->testResults as $test) {
            $role = $test['role'];
            if (!isset($roleStats[$role])) {
                $roleStats[$role] = ['total' => 0, 'passed' => 0, 'failed' => 0];
            }
            $roleStats[$role]['total']++;
            if ($test['passed']) {
                $roleStats[$role]['passed']++;
            } else {
                $roleStats[$role]['failed']++;
            }
        }

        echo "📈 OVERALL STATISTICS:\n";
        echo "Total Tests: $totalTests\n";
        echo "Passed: $passedCount ✅\n";
        echo "Failed: $failedCount ❌\n";
        echo "Success Rate: $successRate%\n\n";

        echo "📊 ROLE BREAKDOWN:\n";
        foreach ($roleStats as $role => $stats) {
            $roleSuccessRate = $stats['total'] > 0 ? round(($stats['passed'] / $stats['total']) * 100, 2) : 0;
            $icon = $this->getRoleIcon($role);
            echo "$icon $role: {$stats['passed']}/{$stats['total']} ($roleSuccessRate%)\n";
        }
        echo "\n";

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
        if ($successRate >= 90) {
            echo "🌟 EXCELLENT! Mobile API is working perfectly!\n";
        } elseif ($successRate >= 80) {
            echo "✅ GOOD! Mobile API is working well with minor issues.\n";
        } elseif ($successRate >= 70) {
            echo "⚠️ FAIR! Some mobile API issues need attention.\n";
        } else {
            echo "❌ POOR! Significant mobile API problems.\n";
        }

        // Save report
        $reportData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_tests' => $totalTests,
            'passed_tests' => $passedCount,
            'failed_tests' => $failedCount,
            'success_rate' => $successRate,
            'role_statistics' => $roleStats,
            'test_results' => $this->testResults
        ];

        $filename = 'mobile_core_test_report_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($filename, json_encode($reportData, JSON_PRETTY_PRINT));
        echo "\n📄 Report saved to: $filename\n";
    }

    public function runAllTests()
    {
        $this->testPublicEndpoints();
        $this->testAuthentication();
        $this->testCoreEndpoints();
        $this->testRoleSpecificEndpoints();
        $this->testRoleAccessControl();
        $this->testErrorHandling();
        $this->generateReport();
    }
}

// Run the tests
$tester = new MobileCoreTester();
$tester->runAllTests();

?>
