#!/usr/bin/env python3

"""
Fundi API Endpoint Testing Script (Python Version)
Comprehensive testing of all API endpoints with detailed reporting
"""

import requests
import json
import sys
from datetime import datetime
from typing import Dict, List, Tuple, Optional

class FundiApiTester:
    def __init__(self, base_url: str = "http://localhost:8000/api/v1"):
        self.base_url = base_url
        self.token = None
        self.test_results = []
        self.test_user = {
            'phone': '+255123456789',
            'password': 'password123'
        }
        
    def make_request(self, method: str, endpoint: str, data: Optional[Dict] = None, 
                    expected_status: int = 200) -> Tuple[bool, int, str]:
        """Make HTTP request and return success status, status code, and response"""
        url = f"{self.base_url}{endpoint}"
        headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
        
        if self.token:
            headers['Authorization'] = f'Bearer {self.token}'
        
        try:
            if method.upper() == 'GET':
                response = requests.get(url, headers=headers, timeout=30)
            elif method.upper() == 'POST':
                response = requests.post(url, headers=headers, json=data, timeout=30)
            elif method.upper() == 'PUT':
                response = requests.put(url, headers=headers, json=data, timeout=30)
            elif method.upper() == 'DELETE':
                response = requests.delete(url, headers=headers, timeout=30)
            else:
                return False, 0, "Unsupported HTTP method"
            
            success = response.status_code == expected_status
            return success, response.status_code, response.text
            
        except requests.exceptions.RequestException as e:
            return False, 0, str(e)
    
    def log_test(self, test_name: str, endpoint: str, method: str, 
                expected_status: int, actual_status: int, response: str, success: bool):
        """Log test result"""
        status_icon = "✅" if success else "❌"
        print(f"{status_icon} {test_name:<50} {'PASS' if success else 'FAIL'}")
        
        if not success:
            print(f"   Expected: HTTP {expected_status}")
            print(f"   Actual: HTTP {actual_status}")
            print(f"   Response: {response[:200]}{'...' if len(response) > 200 else ''}")
        
        self.test_results.append({
            'test_name': test_name,
            'endpoint': endpoint,
            'method': method,
            'expected_status': expected_status,
            'actual_status': actual_status,
            'success': success,
            'response': response
        })
        print()
    
    def test_public_endpoints(self):
        """Test public endpoints that don't require authentication"""
        print("🌐 TESTING PUBLIC ENDPOINTS")
        print("=" * 30)
        print()
        
        # Test API health check
        success, status, response = self.make_request('GET', '/test', expected_status=200)
        self.log_test("API Health Check", "/test", "GET", 200, status, response, success)
        
        # Test debug auth endpoint
        success, status, response = self.make_request('GET', '/debug/auth', expected_status=200)
        self.log_test("Debug Auth Endpoint", "/debug/auth", "GET", 200, status, response, success)
    
    def test_authentication_endpoints(self):
        """Test authentication-related endpoints"""
        print("🔐 TESTING AUTHENTICATION ENDPOINTS")
        print("=" * 35)
        print()
        
        # Test user registration
        register_data = {
            'phone': '+255999888777',
            'password': 'password123',
            'password_confirmation': 'password123',
            'role': 'admin',
            'nida_number': '99988877766655544433'
        }
        success, status, response = self.make_request('POST', '/auth/register', register_data, 200)
        self.log_test("User Registration", "/auth/register", "POST", 200, status, response, success)
        
        # Test user login
        login_data = {
            'phone': self.test_user['phone'],
            'password': self.test_user['password']
        }
        success, status, response = self.make_request('POST', '/auth/login', login_data, 200)
        
        if success:
            try:
                response_data = json.loads(response)
                if 'data' in response_data and 'token' in response_data['data']:
                    self.token = response_data['data']['token']
            except json.JSONDecodeError:
                pass
        
        self.log_test("User Login", "/auth/login", "POST", 200, status, response, success)
        
        # Test get user profile
        success, status, response = self.make_request('GET', '/auth/me', expected_status=200)
        self.log_test("Get User Profile", "/auth/me", "GET", 200, status, response, success)
        
        # Test token info
        if self.token:
            token_data = {'token': self.token}
            success, status, response = self.make_request('POST', '/auth/token-info', token_data, 200)
            self.log_test("Token Information", "/auth/token-info", "POST", 200, status, response, success)
    
    def test_protected_endpoints(self):
        """Test protected endpoints that require authentication"""
        if not self.token:
            print("❌ Cannot test protected endpoints - no valid token")
            print()
            return
        
        print("🛡️ TESTING PROTECTED ENDPOINTS")
        print("=" * 32)
        print()
        
        protected_tests = [
            ("Protected Test Route", "/test-protected", "GET"),
            ("Get Categories", "/categories", "GET"),
            ("Get Jobs", "/jobs", "GET"),
            ("Get Notifications", "/notifications", "GET"),
            ("Get User Profile (Alternative)", "/users/me", "GET"),
            ("Get My Applications", "/my-applications", "GET"),
            ("Get Payments", "/payments", "GET"),
            ("Get My Ratings", "/ratings/my-ratings", "GET")
        ]
        
        for test_name, endpoint, method in protected_tests:
            success, status, response = self.make_request(method, endpoint, expected_status=200)
            self.log_test(test_name, endpoint, method, 200, status, response, success)
    
    def test_job_endpoints(self):
        """Test job-related endpoints"""
        if not self.token:
            print("❌ Cannot test job endpoints - no valid token")
            print()
            return
        
        print("💼 TESTING JOB ENDPOINTS")
        print("=" * 25)
        print()
        
        job_tests = [
            ("Get Specific Job", "/jobs/1", "GET"),
            ("Get Job Applications", "/jobs/1/applications", "GET")
        ]
        
        for test_name, endpoint, method in job_tests:
            success, status, response = self.make_request(method, endpoint, expected_status=200)
            self.log_test(test_name, endpoint, method, 200, status, response, success)
        
        # Test create new job
        job_data = {
            'title': 'Test Job from Python Script',
            'description': 'This is a test job created by the Python testing script',
            'category_id': 1,
            'budget': 50000,
            'budget_type': 'fixed',
            'deadline': '2025-12-31',
            'location_lat': -6.7924,
            'location_lng': 39.2083
        }
        success, status, response = self.make_request('POST', '/jobs', job_data, 200)
        self.log_test("Create New Job", "/jobs", "POST", 200, status, response, success)
    
    def test_admin_endpoints(self):
        """Test admin-only endpoints"""
        if not self.token:
            print("❌ Cannot test admin endpoints - no valid token")
            print()
            return
        
        print("👑 TESTING ADMIN ENDPOINTS")
        print("=" * 28)
        print()
        
        admin_tests = [
            ("Get All Users (Admin)", "/admin/users", "GET"),
            ("Get All Jobs (Admin)", "/admin/jobs", "GET"),
            ("Get All Payments (Admin)", "/admin/payments", "GET"),
            ("Get System Health", "/admin/monitor/system-health", "GET"),
            ("Get Settings", "/admin/settings", "GET")
        ]
        
        for test_name, endpoint, method in admin_tests:
            success, status, response = self.make_request(method, endpoint, expected_status=200)
            self.log_test(test_name, endpoint, method, 200, status, response, success)
    
    def test_error_handling(self):
        """Test error handling scenarios"""
        print("⚠️ TESTING ERROR HANDLING")
        print("=" * 25)
        print()
        
        # Test without token
        self.token = None
        success, status, response = self.make_request('GET', '/auth/me', expected_status=401)
        self.log_test("Access Protected Route Without Token", "/auth/me", "GET", 401, status, response, success)
        
        # Test with invalid token
        self.token = "invalid_token_123"
        success, status, response = self.make_request('GET', '/test-protected', expected_status=401)
        self.log_test("Access Protected Route With Invalid Token", "/test-protected", "GET", 401, status, response, success)
        
        # Test invalid login
        invalid_login_data = {
            'phone': '+255999999999',
            'password': 'wrongpassword'
        }
        success, status, response = self.make_request('POST', '/auth/login', invalid_login_data, 401)
        self.log_test("Login With Invalid Credentials", "/auth/login", "POST", 401, status, response, success)
        
        # Test non-existent endpoint
        success, status, response = self.make_request('GET', '/non-existent-endpoint', expected_status=404)
        self.log_test("Access Non-existent Endpoint", "/non-existent-endpoint", "GET", 404, status, response, success)
    
    def test_logout(self):
        """Test logout functionality"""
        # Get fresh token for logout test
        login_data = {
            'phone': self.test_user['phone'],
            'password': self.test_user['password']
        }
        success, status, response = self.make_request('POST', '/auth/login', login_data, 200)
        
        if success:
            try:
                response_data = json.loads(response)
                if 'data' in response_data and 'token' in response_data['data']:
                    self.token = response_data['data']['token']
            except json.JSONDecodeError:
                pass
        
        if not self.token:
            print("❌ Cannot test logout - no valid token")
            print()
            return
        
        print("🚪 TESTING LOGOUT")
        print("=" * 18)
        print()
        
        # Test logout
        success, status, response = self.make_request('POST', '/auth/logout', expected_status=200)
        self.log_test("User Logout", "/auth/logout", "POST", 200, status, response, success)
        
        # Test access after logout
        success, status, response = self.make_request('GET', '/auth/me', expected_status=401)
        self.log_test("Access Protected Route After Logout", "/auth/me", "GET", 401, status, response, success)
    
    def generate_report(self):
        """Generate comprehensive test report"""
        print("📊 TESTING REPORT")
        print("=" * 18)
        print()
        
        total_tests = len(self.test_results)
        passed_tests = sum(1 for test in self.test_results if test['success'])
        failed_tests = total_tests - passed_tests
        success_rate = (passed_tests / total_tests * 100) if total_tests > 0 else 0
        
        print(f"Total Tests: {total_tests}")
        print(f"Passed: {passed_tests} ✅")
        print(f"Failed: {failed_tests} ❌")
        print(f"Success Rate: {success_rate:.2f}%")
        print()
        
        if failed_tests > 0:
            print("❌ FAILED TESTS:")
            print("=" * 16)
            for test in self.test_results:
                if not test['success']:
                    print(f"- {test['test_name']} ({test['method']} {test['endpoint']})")
                    print(f"  Expected: HTTP {test['expected_status']}, Got: HTTP {test['actual_status']}")
            print()
        
        print("🎯 SUMMARY:")
        print("=" * 12)
        if success_rate >= 95:
            print("🌟 EXCELLENT! API is performing exceptionally well!")
        elif success_rate >= 90:
            print("✅ GOOD! API is working well with minor issues.")
        elif success_rate >= 80:
            print("⚠️ FAIR! API has some issues that need attention.")
        else:
            print("❌ POOR! API has significant issues that need immediate attention.")
        
        # Save detailed report to file
        report_data = {
            'timestamp': datetime.now().isoformat(),
            'total_tests': total_tests,
            'passed_tests': passed_tests,
            'failed_tests': failed_tests,
            'success_rate': success_rate,
            'test_results': self.test_results
        }
        
        filename = f"test_report_{datetime.now().strftime('%Y-%m-%d_%H-%M-%S')}.json"
        with open(filename, 'w') as f:
            json.dump(report_data, f, indent=2)
        
        print(f"\n📄 Detailed report saved to: {filename}")
    
    def run_all_tests(self):
        """Run all test suites"""
        print("🚀 Starting Fundi API Comprehensive Testing")
        print("=" * 45)
        print()
        
        self.test_public_endpoints()
        self.test_authentication_endpoints()
        self.test_protected_endpoints()
        self.test_job_endpoints()
        self.test_admin_endpoints()
        self.test_error_handling()
        self.test_logout()
        self.generate_report()

if __name__ == "__main__":
    tester = FundiApiTester()
    tester.run_all_tests()
