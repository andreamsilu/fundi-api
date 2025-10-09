<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use App\Models\Payment;
use App\Models\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Dashboard Controller
 * Provides analytics and statistics for the dashboard
 */
class DashboardController extends Controller
{
    /**
     * Get dashboard overview statistics
     */
    public function getOverview(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $data = [];

            if ($user->isCustomer()) {
                $data = $this->getCustomerOverview($user);
            } elseif ($user->isFundi()) {
                $data = $this->getFundiOverview($user);
            } elseif ($user->isAdmin()) {
                $data = $this->getAdminOverview($user);
            }

            return response()->json([
                'success' => true,
                'message' => 'Dashboard overview retrieved successfully',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard overview',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Get customer-specific dashboard data
     */
    private function getCustomerOverview($user): array
    {
        $totalJobs = Job::where('customer_id', $user->id)->count();
        $activeJobs = Job::where('customer_id', $user->id)
            ->whereIn('status', ['open', 'in_progress'])
            ->count();
        $completedJobs = Job::where('customer_id', $user->id)
            ->where('status', 'completed')
            ->count();
        
        $totalApplications = JobApplication::whereHas('job', function($query) use ($user) {
            $query->where('customer_id', $user->id);
        })->count();

        $totalSpent = Payment::where('payer_id', $user->id)
            ->where('status', 'completed')
            ->sum('amount');

        return [
            'user_type' => 'customer',
            'statistics' => [
                'total_jobs_posted' => $totalJobs,
                'active_jobs' => $activeJobs,
                'completed_jobs' => $completedJobs,
                'total_applications_received' => $totalApplications,
                'total_spent' => (float) $totalSpent,
            ],
            'recent_jobs' => Job::where('customer_id', $user->id)
                ->with(['category:id,name', 'applications'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
            'recent_applications' => JobApplication::whereHas('job', function($query) use ($user) {
                $query->where('customer_id', $user->id);
            })
                ->with(['job:id,title', 'fundi:id,full_name'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Get fundi-specific dashboard data
     */
    private function getFundiOverview($user): array
    {
        $totalApplications = JobApplication::where('fundi_id', $user->id)->count();
        $acceptedApplications = JobApplication::where('fundi_id', $user->id)
            ->where('status', 'accepted')
            ->count();
        $pendingApplications = JobApplication::where('fundi_id', $user->id)
            ->where('status', 'pending')
            ->count();

        $activeJobs = JobApplication::where('fundi_id', $user->id)
            ->where('status', 'accepted')
            ->whereHas('job', function($query) {
                $query->where('status', 'in_progress');
            })
            ->count();

        $completedJobs = JobApplication::where('fundi_id', $user->id)
            ->whereHas('job', function($query) {
                $query->where('status', 'completed');
            })
            ->count();

        $totalEarned = Payment::where('payee_id', $user->id)
            ->where('status', 'completed')
            ->sum('amount');

        $portfolioCount = Portfolio::where('fundi_id', $user->id)
            ->where('status', 'approved')
            ->count();

        $averageRating = DB::table('ratings_reviews')
            ->where('fundi_id', $user->id)
            ->avg('rating');

        return [
            'user_type' => 'fundi',
            'statistics' => [
                'total_applications' => $totalApplications,
                'accepted_applications' => $acceptedApplications,
                'pending_applications' => $pendingApplications,
                'active_jobs' => $activeJobs,
                'completed_jobs' => $completedJobs,
                'total_earned' => (float) $totalEarned,
                'portfolio_items' => $portfolioCount,
                'average_rating' => round($averageRating, 1),
            ],
            'recent_applications' => JobApplication::where('fundi_id', $user->id)
                ->with(['job:id,title,budget', 'job.customer:id,full_name'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
            'active_jobs' => JobApplication::where('fundi_id', $user->id)
                ->where('status', 'accepted')
                ->whereHas('job', function($query) {
                    $query->where('status', 'in_progress');
                })
                ->with(['job:id,title,budget,deadline'])
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Get admin-specific dashboard data
     */
    private function getAdminOverview($user): array
    {
        $totalUsers = User::count();
        $totalCustomers = User::role('customer')->count();
        $totalFundis = User::role('fundi')->count();
        
        $totalJobs = Job::count();
        $activeJobs = Job::whereIn('status', ['open', 'in_progress'])->count();
        $completedJobs = Job::where('status', 'completed')->count();

        $totalApplications = JobApplication::count();
        $totalPayments = Payment::where('status', 'completed')->sum('amount');

        $pendingPortfolio = Portfolio::where('status', 'pending')->count();
        $pendingFundiApplications = \App\Models\FundiApplication::where('status', 'pending')->count();

        // Get statistics for last 7 days
        $last7Days = now()->subDays(7);
        $newUsersLast7Days = User::where('created_at', '>=', $last7Days)->count();
        $newJobsLast7Days = Job::where('created_at', '>=', $last7Days)->count();

        return [
            'user_type' => 'admin',
            'statistics' => [
                'total_users' => $totalUsers,
                'total_customers' => $totalCustomers,
                'total_fundis' => $totalFundis,
                'new_users_last_7_days' => $newUsersLast7Days,
                'total_jobs' => $totalJobs,
                'active_jobs' => $activeJobs,
                'completed_jobs' => $completedJobs,
                'new_jobs_last_7_days' => $newJobsLast7Days,
                'total_applications' => $totalApplications,
                'total_payments' => (float) $totalPayments,
                'pending_portfolio_approvals' => $pendingPortfolio,
                'pending_fundi_applications' => $pendingFundiApplications,
            ],
            'recent_users' => User::orderBy('created_at', 'desc')->limit(5)->get(),
            'recent_jobs' => Job::with(['customer:id,full_name', 'category:id,name'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
            'pending_approvals' => [
                'portfolio' => Portfolio::where('status', 'pending')
                    ->with('fundi:id,full_name')
                    ->limit(5)
                    ->get(),
                'fundi_applications' => \App\Models\FundiApplication::where('status', 'pending')
                    ->with('user:id,full_name,phone')
                    ->limit(5)
                    ->get(),
            ],
        ];
    }

    /**
     * Get job statistics over time
     */
    public function getJobStatistics(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'month'); // day, week, month, year
            $user = Auth::user();

            $query = Job::query();

            // Filter by user if not admin
            if (!$user->isAdmin()) {
                if ($user->isCustomer()) {
                    $query->where('customer_id', $user->id);
                } elseif ($user->isFundi()) {
                    // Get jobs where fundi has accepted applications
                    $query->whereHas('applications', function($q) use ($user) {
                        $q->where('fundi_id', $user->id)->where('status', 'accepted');
                    });
                }
            }

            $groupBy = match($period) {
                'day' => DB::raw('DATE(created_at)'),
                'week' => DB::raw('YEARWEEK(created_at)'),
                'month' => DB::raw('DATE_FORMAT(created_at, "%Y-%m")'),
                'year' => DB::raw('YEAR(created_at)'),
                default => DB::raw('DATE_FORMAT(created_at, "%Y-%m")'),
            };

            $statistics = $query
                ->select(
                    $groupBy . ' as period',
                    DB::raw('COUNT(*) as total_jobs'),
                    DB::raw('SUM(CASE WHEN status = "open" THEN 1 ELSE 0 END) as open_jobs'),
                    DB::raw('SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as in_progress_jobs'),
                    DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_jobs'),
                    DB::raw('AVG(budget) as average_budget')
                )
                ->groupBy('period')
                ->orderBy('period', 'desc')
                ->limit(12)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Job statistics retrieved successfully',
                'data' => [
                    'period' => $period,
                    'statistics' => $statistics
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve job statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Get payment statistics
     */
    public function getPaymentStatistics(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $period = $request->get('period', 'month');

            $query = Payment::where('status', 'completed');

            // Filter by user role
            if ($user->isCustomer()) {
                $query->where('payer_id', $user->id);
            } elseif ($user->isFundi()) {
                $query->where('payee_id', $user->id);
            }

            $groupBy = match($period) {
                'day' => DB::raw('DATE(created_at)'),
                'week' => DB::raw('YEARWEEK(created_at)'),
                'month' => DB::raw('DATE_FORMAT(created_at, "%Y-%m")'),
                'year' => DB::raw('YEAR(created_at)'),
                default => DB::raw('DATE_FORMAT(created_at, "%Y-%m")'),
            };

            $statistics = $query
                ->select(
                    $groupBy . ' as period',
                    DB::raw('COUNT(*) as total_payments'),
                    DB::raw('SUM(amount) as total_amount'),
                    DB::raw('AVG(amount) as average_amount')
                )
                ->groupBy('period')
                ->orderBy('period', 'desc')
                ->limit(12)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Payment statistics retrieved successfully',
                'data' => [
                    'period' => $period,
                    'statistics' => $statistics
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Get application statistics
     */
    public function getApplicationStatistics(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $query = JobApplication::query();

            if ($user->isFundi()) {
                $query->where('fundi_id', $user->id);
            } elseif ($user->isCustomer()) {
                $query->whereHas('job', function($q) use ($user) {
                    $q->where('customer_id', $user->id);
                });
            }

            $statistics = [
                'total' => $query->count(),
                'pending' => (clone $query)->where('status', 'pending')->count(),
                'accepted' => (clone $query)->where('status', 'accepted')->count(),
                'rejected' => (clone $query)->where('status', 'rejected')->count(),
                'acceptance_rate' => 0,
            ];

            if ($statistics['total'] > 0) {
                $statistics['acceptance_rate'] = round(($statistics['accepted'] / $statistics['total']) * 100, 1);
            }

            return response()->json([
                'success' => true,
                'message' => 'Application statistics retrieved successfully',
                'data' => $statistics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve application statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }
}

