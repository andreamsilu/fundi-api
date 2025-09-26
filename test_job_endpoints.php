<?php

require_once 'vendor/autoload.php';

use App\Models\Job;
use App\Models\User;

// Test the job endpoints
echo "Testing Job Endpoints\n";
echo "====================\n\n";

// Get admin user
$admin = User::where('phone', '0754289824')->first();
if (!$admin) {
    echo "Admin user not found!\n";
    exit(1);
}

echo "Admin User ID: " . $admin->id . "\n";
echo "Admin Name: " . $admin->full_name . "\n\n";

// Test available jobs (should exclude user's own jobs)
echo "=== Available Jobs (should exclude user's own jobs) ===\n";
$availableJobs = Job::where('customer_id', '!=', $admin->id)->get();
echo "Available jobs count: " . $availableJobs->count() . "\n";
foreach ($availableJobs as $job) {
    echo "Job ID: " . $job->id . ", Title: " . $job->title . ", Customer ID: " . $job->customer_id . ", Status: " . $job->status . "\n";
}

echo "\n=== My Jobs (should only show user's own jobs) ===\n";
$myJobs = Job::where('customer_id', $admin->id)->get();
echo "My jobs count: " . $myJobs->count() . "\n";
foreach ($myJobs as $job) {
    echo "Job ID: " . $job->id . ", Title: " . $job->title . ", Customer ID: " . $job->customer_id . ", Status: " . $job->status . "\n";
}

echo "\n=== All Jobs in Database ===\n";
$allJobs = Job::all();
echo "Total jobs: " . $allJobs->count() . "\n";
foreach ($allJobs as $job) {
    echo "Job ID: " . $job->id . ", Title: " . $job->title . ", Customer ID: " . $job->customer_id . ", Status: " . $job->status . "\n";
}

echo "\n=== Jobs with customer_id = 1 ===\n";
$jobsWithCustomer1 = Job::where('customer_id', 1)->get();
echo "Jobs with customer_id = 1: " . $jobsWithCustomer1->count() . "\n";
foreach ($jobsWithCustomer1 as $job) {
    echo "Job ID: " . $job->id . ", Title: " . $job->title . ", Customer ID: " . $job->customer_id . ", Status: " . $job->status . "\n";
}

echo "\n=== Jobs with customer_id != 1 ===\n";
$jobsNotWithCustomer1 = Job::where('customer_id', '!=', 1)->get();
echo "Jobs with customer_id != 1: " . $jobsNotWithCustomer1->count() . "\n";
foreach ($jobsNotWithCustomer1 as $job) {
    echo "Job ID: " . $job->id . ", Title: " . $job->title . ", Customer ID: " . $job->customer_id . ", Status: " . $job->status . "\n";
}

echo "\nTest completed!\n";
