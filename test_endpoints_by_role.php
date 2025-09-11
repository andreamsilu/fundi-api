<?php

/**
 * Mobile API Endpoint Testing Script - Grouped by Role
 * Tests all endpoints based on their specific role requirements
 */

class MobileApiRoleTester
{
    private $baseUrl = 'http://localhost:8000/api/v1';
    private $tokens = [];
    private $testResults = [];
    
    // Test users for each role
    private $testUsers = [
        'admin' => [
            'phone' => '+255123456789',
            'password' => 'password123',
            'role' => 'admin'
        ],
        'customer' => [
            'phone' => '+255111111111',
            'password' => 'password123',
            'role' => 'customer'
        ],
        'fundi' => [
            'phone' => '+255222222222',
            'password' => 'password123',
            'role' => 'fundi'
        ]
    ];

    public function __construct()
    {
        echo "📱 Starting Mobile API Role-Based Testing\n";
        echo "==========================================\n\n";
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
        $error = curl_error($ch);
        curl_close($ch);
        
        return [
            'http_code' => $httpCode,
            'response' => $response,
            'error' => $error
        ];
    }

    private function logTest($testName, $endpoint, $method, $role, $expectedCode, $actualCode, $response, $passed)
    {
        $status = $passed ? '✅ PASS' : '❌ FAIL';
        $roleIcon = $this->getRoleIcon($role);
        echo sprintf("%-60s %s %s\n", $testName, $roleIcon, $status);
        
        if (!$passed) {
            echo "   Expected: HTTP $expectedCode, Got: HTTP $actualCode\n";
            echo "   Response: " . substr($response, 0, 150) . (strlen($response) > 150 ? '...' : '') . "\n";
        }
        
        $this->testResults[] = [
            'test' => $testName,
            'endpoint' => $endpoint,
            'method' => $method,
            'role' => $role,
            'expected_code' => $expectedCode,
            'actual_code' => $actualCode,
            'passed' => $passed,
            'response' => $response
        ];
        
        echo "\n";
    }

    private function getRoleIcon($role)
    {
        $icons = [
            'admin' => '👑',
            'customer' => '👤',
            'fundi' => '🔧',
            'public' => '🌐'
        ];
        return $icons[$role] ?? '❓';
    }

    private function authenticateUser($role)
    {
        if (isset($this->tokens[$role])) {
            return true; // Already authenticated
        }

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
        echo "🌐 TESTING PUBLIC ENDPOINTS (No Authentication Required)\n";
        echo "======================================================\n\n";

        $publicTests = [
            ['API Health Check', '/test', 'GET', 200],
            ['Debug Auth Endpoint', '/debug/auth', 'GET', 200]
        ];

        foreach ($publicTests as $test) {
            $result = $this->makeRequest($test[2], $test[1]);
            $this->logTest(
                $test[0],
                $test[1],
                $test[2],
                'public',
                $test[3],
                $result['http_code'],
                $result['response'],
                $result['http_code'] === $test[3]
            );
        }
    }

    public function testAdminEndpoints()
    {
        echo "👑 TESTING ADMIN ENDPOINTS\n";
        echo "==========================\n\n";

        if (!$this->authenticateUser('admin')) {
            echo "❌ Failed to authenticate admin user\n\n";
            return;
        }

        $adminTests = [
            // User Management
            ['Get All Users', '/admin/users', 'GET', 200],
            ['Get User Details', '/admin/users/1', 'GET', 200],
            ['Update User', '/admin/users/1', 'PATCH', 200],
            
            // Job Management
            ['Get All Jobs (Admin)', '/admin/jobs', 'GET', 200],
            ['Get Job Details (Admin)', '/admin/jobs/1', 'GET', 200],
            ['Update Job (Admin)', '/admin/jobs/1', 'PATCH', 200],
            ['Delete Job (Admin)', '/admin/jobs/1', 'DELETE', 200],
            
            // Payment Management
            ['Get All Payments (Admin)', '/admin/payments', 'GET', 200],
            ['Update Payment (Admin)', '/admin/payments/1', 'PATCH', 200],
            ['Get Payment Reports', '/admin/payments/reports', 'GET', 200],
            
            // Fundi Management
            ['Get Fundi Profiles', '/admin/fundi_profiles', 'GET', 200],
            ['Verify Fundi', '/admin/fundi_profiles/1/verify', 'PATCH', 200],
            
            // Application Management
            ['Get All Applications', '/admin/job_applications', 'GET', 200],
            ['Update Application', '/admin/job_applications/1', 'PATCH', 200],
            ['Delete Application', '/admin/job_applications/1', 'DELETE', 200],
            
            // Category Management
            ['Create Category', '/admin/categories', 'POST', 200],
            ['Update Category', '/admin/categories/1', 'PATCH', 200],
            ['Delete Category', '/admin/categories/1', 'DELETE', 200],
            
            // Notification Management
            ['Send Notification', '/admin/notifications', 'POST', 200],
            ['Update Notification', '/admin/notifications/1', 'PATCH', 200],
            ['Delete Notification', '/admin/notifications/1', 'DELETE', 200],
            
            // Portfolio Management
            ['Update Portfolio', '/admin/portfolio/1', 'PATCH', 200],
            ['Delete Portfolio', '/admin/portfolio/1', 'DELETE', 200],
            
            // Rating Management
            ['Get All Ratings', '/admin/ratings', 'GET', 200],
            
            // Session Management
            ['Get Sessions', '/admin/sessions', 'GET', 200],
            ['Force Logout', '/admin/sessions/1', 'DELETE', 200],
            
            // Settings
            ['Get Settings', '/admin/settings', 'GET', 200],
            ['Update Settings', '/admin/settings', 'PATCH', 200],
            
            // Monitoring
            ['Get Active Users', '/admin/monitor/active-users', 'GET', 200],
            ['Get Jobs Summary', '/admin/monitor/jobs-summary', 'GET', 200],
            ['Get Payments Summary', '/admin/monitor/payments-summary', 'GET', 200],
            ['Get System Health', '/admin/monitor/system-health', 'GET', 200],
            ['Get API Logs', '/admin/monitor/api-logs', 'GET', 200],
            ['Get Laravel Logs', '/admin/logs', 'GET', 200],
            
            // Audit Logs
            ['Get Audit Logs', '/admin/audit-logs', 'GET', 200],
            ['Get API Errors', '/admin/audit-logs/api-errors', 'GET', 200],
            ['Get Failed Actions', '/admin/audit-logs/failed-actions', 'GET', 200],
            ['Get Security Events', '/admin/audit-logs/security-events', 'GET', 200],
            ['Get Statistics', '/admin/audit-logs/statistics', 'GET', 200],
            ['Get User Activity', '/admin/audit-logs/user-activity/1', 'GET', 200],
            ['Export Audit Logs', '/admin/audit-logs/export', 'GET', 200],
            ['Get Audit Log Details', '/admin/audit-logs/1', 'GET', 200]
        ];

        foreach ($adminTests as $test) {
            $result = $this->makeRequest($test[2], $test[1], null, 'admin');
            $this->logTest(
                $test[0],
                $test[1],
                $test[2],
                'admin',
                $test[3],
                $result['http_code'],
                $result['response'],
                $result['http_code'] === $test[3]
            );
        }
    }

    public function testCustomerEndpoints()
    {
        echo "👤 TESTING CUSTOMER ENDPOINTS\n";
        echo "=============================\n\n";

        if (!$this->authenticateUser('customer')) {
            echo "❌ Failed to authenticate customer user\n\n";
            return;
        }

        $customerTests = [
            // Authentication
            ['Get Customer Profile', '/auth/me', 'GET', 200],
            ['Get User Profile', '/users/me', 'GET', 200],
            
            // Public Protected Endpoints (accessible to all authenticated users)
            ['Categories List', '/categories', 'GET', 200],
            ['Jobs List', '/jobs', 'GET', 200],
            ['Fundi Profile', '/fundi/1', 'GET', 200],
            ['Fundi Portfolio', '/portfolio/1', 'GET', 200],
            ['Fundi Ratings', '/ratings/fundi/1', 'GET', 200],
            
            // Job Management
            ['Create Job', '/jobs', 'POST', 200],
            ['Get My Jobs', '/jobs', 'GET', 200],
            ['Get Job Details', '/jobs/1', 'GET', 200],
            ['Update Job', '/jobs/1', 'PUT', 200],
            ['Delete Job', '/jobs/1', 'DELETE', 200],
            
            // Job Applications
            ['Get Job Applications', '/jobs/1/applications', 'GET', 200],
            ['Update Application Status', '/applications/1/status', 'PATCH', 200],
            ['Delete Application', '/applications/1', 'DELETE', 200],
            
            // Payments
            ['Get My Payments', '/payments', 'GET', 200],
            ['Create Payment', '/payments', 'POST', 200],
            ['Check Payment Required', '/payments/check-required', 'POST', 200],
            ['Get Payment Requirements', '/payments/requirements', 'GET', 200],
            
            // Ratings & Reviews
            ['Create Rating', '/ratings', 'POST', 200],
            ['Get My Ratings', '/ratings/my-ratings', 'GET', 200],
            ['Update Rating', '/ratings/1', 'PUT', 200],
            ['Delete Rating', '/ratings/1', 'DELETE', 200],
            
            // Notifications
            ['Get Notifications', '/notifications', 'GET', 200],
            ['Mark Notification as Read', '/notifications/1/read', 'PATCH', 200],
            ['Delete Notification', '/notifications/1', 'DELETE', 200],
            
            // File Uploads
            ['Upload Job Media', '/upload/job-media', 'POST', 200],
            ['Get Media URL', '/upload/media/1/url', 'GET', 200],
            ['Delete Media', '/upload/media/1', 'DELETE', 200]
        ];

        foreach ($customerTests as $test) {
            $result = $this->makeRequest($test[2], $test[1], null, 'customer');
            $this->logTest(
                $test[0],
                $test[1],
                $test[2],
                'customer',
                $test[3],
                $result['http_code'],
                $result['response'],
                $result['http_code'] === $test[3]
            );
        }
    }

    public function testFundiEndpoints()
    {
        echo "🔧 TESTING FUNDI ENDPOINTS\n";
        echo "==========================\n\n";

        if (!$this->authenticateUser('fundi')) {
            echo "❌ Failed to authenticate fundi user\n\n";
            return;
        }

        $fundiTests = [
            // Authentication
            ['Get Fundi Profile', '/auth/me', 'GET', 200],
            ['Get User Profile', '/users/me', 'GET', 200],
            ['Update Fundi Profile', '/users/me/fundi-profile', 'PATCH', 200],
            
            // Public Protected Endpoints (accessible to all authenticated users)
            ['Categories List', '/categories', 'GET', 200],
            ['Jobs List', '/jobs', 'GET', 200],
            ['Fundi Profile', '/fundi/1', 'GET', 200],
            ['Fundi Portfolio', '/portfolio/1', 'GET', 200],
            ['Fundi Ratings', '/ratings/fundi/1', 'GET', 200],
            
            // Job Applications
            ['Apply for Job', '/jobs/1/apply', 'POST', 200],
            ['Get My Applications', '/my-applications', 'GET', 200],
            ['Update Application Status', '/applications/1/status', 'PATCH', 200],
            ['Delete Application', '/applications/1', 'DELETE', 200],
            
            // Payments
            ['Get My Payments', '/payments', 'GET', 200],
            ['Create Payment', '/payments', 'POST', 200],
            ['Check Payment Required', '/payments/check-required', 'POST', 200],
            ['Get Payment Requirements', '/payments/requirements', 'GET', 200],
            
            // Portfolio Management
            ['Create Portfolio', '/portfolio', 'POST', 200],
            ['Get Fundi Portfolio', '/portfolio/1', 'GET', 200],
            ['Update Portfolio', '/portfolio/1', 'PUT', 200],
            ['Delete Portfolio', '/portfolio/1', 'DELETE', 200],
            ['Upload Portfolio Media', '/portfolio-media', 'POST', 200],
            
            // Ratings & Reviews
            ['Get Fundi Ratings', '/ratings/fundi/1', 'GET', 200],
            ['Update Rating', '/ratings/1', 'PUT', 200],
            ['Delete Rating', '/ratings/1', 'DELETE', 200],
            
            // Notifications
            ['Get Notifications', '/notifications', 'GET', 200],
            ['Mark Notification as Read', '/notifications/1/read', 'PATCH', 200],
            ['Delete Notification', '/notifications/1', 'DELETE', 200],
            
            // File Uploads
            ['Upload Portfolio Media', '/upload/portfolio-media', 'POST', 200],
            ['Upload Profile Document', '/upload/profile-document', 'POST', 200],
            ['Get Media URL', '/upload/media/1/url', 'GET', 200],
            ['Delete Media', '/upload/media/1', 'DELETE', 200]
        ];

        foreach ($fundiTests as $test) {
            $result = $this->makeRequest($test[2], $test[1], null, 'fundi');
            $this->logTest(
                $test[0],
                $test[1],
                $test[2],
                'fundi',
                $test[3],
                $result['http_code'],
                $result['response'],
                $result['http_code'] === $test[3]
            );
        }
    }

    public function testCrossRoleAccess()
    {
        echo "🔄 TESTING CROSS-ROLE ACCESS (Should Fail)\n";
        echo "==========================================\n\n";

        $crossRoleTests = [
            // Customer trying to access Fundi-only endpoints
            ['Customer accessing Fundi Applications', '/my-applications', 'GET', 'customer', 403],
            ['Customer accessing Portfolio Creation', '/portfolio', 'POST', 'customer', 403],
            ['Customer accessing Fundi Profile Update', '/users/me/fundi-profile', 'PATCH', 'customer', 403],
            
            // Fundi trying to access Customer-only endpoints
            ['Fundi accessing Job Creation', '/jobs', 'POST', 'fundi', 403],
            ['Fundi accessing Customer Ratings', '/ratings/my-ratings', 'GET', 'fundi', 403],
            
            // Non-admin trying to access Admin endpoints
            ['Customer accessing Admin Users', '/admin/users', 'GET', 'customer', 403],
            ['Fundi accessing Admin Jobs', '/admin/jobs', 'GET', 'fundi', 403],
            ['Customer accessing Admin Settings', '/admin/settings', 'GET', 'customer', 403]
        ];

        foreach ($crossRoleTests as $test) {
            if ($this->authenticateUser($test[3])) {
                $result = $this->makeRequest($test[1], $test[0], null, $test[3]);
                $this->logTest(
                    $test[0],
                    $test[1],
                    'GET',
                    $test[3],
                    $test[4],
                    $result['http_code'],
                    $result['response'],
                    $result['http_code'] === $test[4]
                );
            }
        }
    }

    public function generateReport()
    {
        echo "📊 ROLE-BASED TESTING REPORT\n";
        echo "============================\n\n";

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

        // Group results by role
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

        echo "📊 ROLE-BASED BREAKDOWN:\n";
        foreach ($roleStats as $role => $stats) {
            $roleSuccessRate = $stats['total'] > 0 ? round(($stats['passed'] / $stats['total']) * 100, 2) : 0;
            $icon = $this->getRoleIcon($role);
            echo "$icon $role: {$stats['passed']}/{$stats['total']} ($roleSuccessRate%)\n";
        }
        echo "\n";

        if ($failedCount > 0) {
            echo "❌ FAILED TESTS BY ROLE:\n";
            echo "========================\n";
            foreach ($roleStats as $role => $stats) {
                if ($stats['failed'] > 0) {
                    echo "\n$icon $role:\n";
                    foreach ($failedTests as $test) {
                        if ($test['role'] === $role) {
                            echo "  - {$test['test']} ({$test['method']} {$test['endpoint']})\n";
                            echo "    Expected: HTTP {$test['expected_code']}, Got: HTTP {$test['actual_code']}\n";
                        }
                    }
                }
            }
            echo "\n";
        }

        echo "🎯 SUMMARY:\n";
        echo "===========\n";
        if ($successRate >= 95) {
            echo "🌟 EXCELLENT! All role-based endpoints working perfectly!\n";
        } elseif ($successRate >= 90) {
            echo "✅ GOOD! Most endpoints working with minor role issues.\n";
        } elseif ($successRate >= 80) {
            echo "⚠️ FAIR! Some role-based access issues need attention.\n";
        } else {
            echo "❌ POOR! Significant role-based access problems.\n";
        }

        // Save detailed report
        $reportData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_tests' => $totalTests,
            'passed_tests' => $passedCount,
            'failed_tests' => $failedCount,
            'success_rate' => $successRate,
            'role_statistics' => $roleStats,
            'test_results' => $this->testResults
        ];

        $filename = 'role_based_test_report_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($filename, json_encode($reportData, JSON_PRETTY_PRINT));
        echo "\n📄 Detailed report saved to: $filename\n";
    }

    public function runAllTests()
    {
        $this->testPublicEndpoints();
        $this->testAdminEndpoints();
        $this->testCustomerEndpoints();
        $this->testFundiEndpoints();
        $this->testCrossRoleAccess();
        $this->generateReport();
    }
}

// Run the tests
$tester = new MobileApiRoleTester();
$tester->runAllTests();

?>
