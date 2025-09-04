<?php

namespace App\Http\Controllers;

use App\Models\BusinessModelConfig;
use App\Models\User;
use App\Models\Job;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="BusinessModelConfig",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="business_model", type="string", example="c2c"),
 *     @OA\Property(property="allowed_client_roles", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="allowed_provider_roles", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="supported_job_types", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="supported_payment_methods", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="minimum_transaction_amount", type="number", example=10.00),
 *     @OA\Property(property="maximum_transaction_amount", type="number", example=10000.00),
 *     @OA\Property(property="platform_fee_percentage", type="number", example=5.00),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="is_featured", type="boolean", example=true)
 * )
 */
class BusinessModelController extends Controller
{
    /**
     * Get all active business model configurations.
     *
     * @OA\Get(
     *     path="/business-models",
     *     tags={"Business Models"},
     *     summary="Get business model configurations",
     *     description="Get all active business model configurations",
     *     operationId="index",
     *     @OA\Response(
     *         response=200,
     *         description="List of business model configurations",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/BusinessModelConfig"))
     *         )
     *     )
     * )
     */
    public function index()
    {
        $businessModels = BusinessModelConfig::active()->get();

        return response()->json([
            'data' => $businessModels
        ]);
    }

    /**
     * Get a specific business model configuration.
     *
     * @OA\Get(
     *     path="/business-models/{id}",
     *     tags={"Business Models"},
     *     summary="Get business model configuration",
     *     description="Get configuration for a specific business model",
     *     operationId="show",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Business model ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Business model configuration",
     *         @OA\JsonContent(ref="#/components/schemas/BusinessModelConfig")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Business model not found"
     *     )
     * )
     */
    public function show($id)
    {
        $config = BusinessModelConfig::find($id);

        if (!$config) {
            return response()->json(['message' => 'Business model not found'], 404);
        }

        return response()->json($config);
    }

    /**
     * Check user compatibility with a business model.
     *
     * @OA\Post(
     *     path="/business-models/{business_model}/check-compatibility",
     *     tags={"Business Models"},
     *     summary="Check user compatibility",
     *     description="Check if the authenticated user can participate in a business model",
     *     operationId="checkCompatibility",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="business_model",
     *         in="path",
     *         description="Business model type",
     *         required=true,
     *         @OA\Schema(type="string", enum={"c2c", "b2c", "c2b", "b2b"})
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"participation_type"},
     *             @OA\Property(property="participation_type", type="string", enum={"client", "provider"}, example="client")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compatibility check result",
     *         @OA\JsonContent(
     *             @OA\Property(property="compatible", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User can be a client in this business model"),
     *             @OA\Property(property="requirements", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="missing_requirements", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Business model not found"
     *     )
     * )
     */
    public function checkCompatibility(Request $request, $id)
    {
        $user = $request->user();
        $participationType = $request->input('participation_type');

        $config = BusinessModelConfig::find($id);
        if (!$config) {
            return response()->json(['message' => 'Business model not found'], 404);
        }

        $compatible = false;
        $message = '';
        $requirements = [];
        $missingRequirements = [];

        if ($participationType === 'customer') {
            $compatible = $user->canBeCustomerInBusinessModel($config->business_model);
            $message = $compatible 
                ? "User can be a client in this business model" 
                : "User cannot be a client in this business model";
        } elseif ($participationType === 'provider') {
            $compatible = $user->canBeProviderInBusinessModel($config->business_model);
            $message = $compatible 
                ? "User can be a provider in this business model" 
                : "User cannot be a provider in this business model";
        } else {
            return response()->json(['message' => 'Invalid participation type'], 400);
        }

        // Get requirements for this business model
        if ($config->requires_contract) {
            $requirements[] = 'Contract required';
        }
        if ($config->requires_invoice) {
            $requirements[] = 'Invoice required';
        }
        if ($config->requires_insurance) {
            $requirements[] = 'Insurance required';
        }
        if ($config->requires_license) {
            $requirements[] = 'License required';
        }
        if ($config->requires_background_check) {
            $requirements[] = 'Background check required';
        }

        // Check missing requirements based on user profile
        if ($config->requires_contract && !$user->is_verified) {
            $missingRequirements[] = 'User verification required for contracts';
        }
        if ($config->requires_insurance && !$user->is_verified) {
            $missingRequirements[] = 'User verification required for insurance';
        }

        return response()->json([
            'compatible' => $compatible,
            'message' => $message,
            'requirements' => $requirements,
            'missing_requirements' => $missingRequirements,
            'business_model_config' => $config
        ]);
    }

    /**
     * Get jobs for a specific business model.
     *
     * @OA\Get(
     *     path="/business-models/{business_model}/jobs",
     *     tags={"Business Models"},
     *     summary="Get jobs by business model",
     *     description="Get jobs filtered by business model with optional filters",
     *     operationId="getJobs",
     *     @OA\Parameter(
     *         name="business_model",
     *         in="path",
     *         description="Business model type",
     *         required=true,
     *         @OA\Schema(type="string", enum={"c2c", "b2c", "c2b", "b2b"})
     *     ),
     *     @OA\Parameter(
     *         name="job_type",
     *         in="query",
     *         description="Filter by job type",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="payment_type",
     *         in="query",
     *         description="Filter by payment type",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="urgency",
     *         in="query",
     *         description="Filter by urgency",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="min_budget",
     *         in="query",
     *         description="Minimum budget",
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="max_budget",
     *         in="query",
     *         description="Maximum budget",
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of jobs",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Job")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Business model not found"
     *     )
     * )
     */
    public function getJobs(Request $request, $id)
    {
        $config = BusinessModelConfig::find($id);
        if (!$config) {
            return response()->json(['message' => 'Business model not found'], 404);
        }

        $query = Job::byBusinessModel($config->business_model)
            ->with(['customer', 'category'])
            ->where('status', 'open');

        // Apply filters
        if ($request->job_type) {
            $query->byJobType($request->job_type);
        }

        if ($request->payment_type) {
            $query->byPaymentType($request->payment_type);
        }

        if ($request->urgency) {
            $query->byUrgency($request->urgency);
        }

        if ($request->min_budget && $request->max_budget) {
            $query->withinBudget($request->min_budget, $request->max_budget);
        }

        $jobs = $query->latest()->paginate(10);

        return response()->json($jobs);
    }

    /**
     * Get business model dashboard data for authenticated user.
     *
     * @OA\Get(
     *     path="/business-models/dashboard",
     *     tags={"Business Models"},
     *     summary="Get business model dashboard",
     *     description="Get dashboard data showing user compatibility with all business models",
     *     operationId="dashboard",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", ref="#/components/schemas/User"),
     *             @OA\Property(property="business_models", type="array", @OA\Items(ref="#/components/schemas/BusinessModelConfig")),
     *             @OA\Property(property="compatibility", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $businessModels = BusinessModelConfig::active()->get();

        $compatibility = [];
        foreach ($businessModels as $model) {
            $compatibility[$model->business_model] = [
                'can_be_client' => $user->canBeClientInBusinessModel($model->business_model),
                'can_be_provider' => $user->canBeProviderInBusinessModel($model->business_model),
                'supported_job_types' => $model->supported_job_types,
                'supported_payment_methods' => $model->supported_payment_methods,
                'transaction_limits' => [
                    'min' => $model->minimum_transaction_amount,
                    'max' => $model->maximum_transaction_amount
                ],
                'platform_fee' => [
                    'percentage' => $model->platform_fee_percentage,
                    'fixed' => $model->platform_fee_fixed
                ]
            ];
        }

        return response()->json([
            'user' => $user->load(['fundiProfile']),
            'business_models' => $businessModels,
            'compatibility' => $compatibility
        ]);
    }

    /**
     * Calculate platform fee for a transaction.
     *
     * @OA\Post(
     *     path="/business-models/{business_model}/calculate-fee",
     *     tags={"Business Models"},
     *     summary="Calculate platform fee",
     *     description="Calculate platform fee for a transaction amount in a business model",
     *     operationId="calculateFee",
     *     @OA\Parameter(
     *         name="business_model",
     *         in="path",
     *         description="Business model type",
     *         required=true,
     *         @OA\Schema(type="string", enum={"c2c", "b2c", "c2b", "b2b"})
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount"},
     *             @OA\Property(property="amount", type="number", example=1000.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fee calculation result",
     *         @OA\JsonContent(
     *             @OA\Property(property="amount", type="number", example=1000.00),
     *             @OA\Property(property="platform_fee", type="number", example=50.00),
     *             @OA\Property(property="net_amount", type="number", example=950.00),
     *             @OA\Property(property="fee_breakdown", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Amount outside transaction limits"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Business model not found"
     *     )
     * )
     */
    public function calculateFee(Request $request, $id)
    {
        $amount = $request->input('amount');
        $config = BusinessModelConfig::find($id);

        if (!$config) {
            return response()->json(['message' => 'Business model not found'], 404);
        }

        if (!$config->isAmountWithinLimits($amount)) {
            return response()->json([
                'message' => 'Amount outside transaction limits',
                'limits' => [
                    'min' => $config->minimum_transaction_amount,
                    'max' => $config->maximum_transaction_amount
                ]
            ], 400);
        }

        $platformFee = $config->calculatePlatformFee($amount);
        $netAmount = $amount - $platformFee;

        return response()->json([
            'amount' => $amount,
            'platform_fee' => $platformFee,
            'net_amount' => $netAmount,
            'fee_breakdown' => [
                'percentage_fee' => ($amount * $config->platform_fee_percentage) / 100,
                'fixed_fee' => $config->platform_fee_fixed,
                'percentage_rate' => $config->platform_fee_percentage,
                'fixed_rate' => $config->platform_fee_fixed
            ]
        ]);
    }

    /**
     * Create a new business model configuration.
     */
    public function store(Request $request)
    {
        $request->validate([
            'business_model' => 'required|string|in:c2c,b2c,c2b,b2b',
            'allowed_client_roles' => 'required|array|min:1',
            'allowed_client_roles.*' => 'string|in:individual,business,freelancer,contractor,enterprise',
            'allowed_provider_roles' => 'required|array|min:1',
            'allowed_provider_roles.*' => 'string|in:individual,business,freelancer,contractor,enterprise',
            'supported_job_types' => 'required|array|min:1',
            'supported_job_types.*' => 'string|in:consultation,development,design,marketing,writing,translation,data_entry,customer_support,sales',
            'supported_payment_methods' => 'required|array|min:1',
            'supported_payment_methods.*' => 'string|in:credit_card,debit_card,bank_transfer,paypal,stripe,crypto,cash,check',
            'minimum_transaction_amount' => 'required|numeric|min:0',
            'maximum_transaction_amount' => 'required|numeric|gt:minimum_transaction_amount',
            'platform_fee_percentage' => 'required|numeric|min:0|max:100',
            'platform_fee_fixed' => 'required|numeric|min:0',
            'requires_contract' => 'boolean',
            'requires_invoice' => 'boolean',
            'requires_insurance' => 'boolean',
            'requires_license' => 'boolean',
            'requires_background_check' => 'boolean',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'is_featured' => 'boolean'
        ]);

        // Check if business model already exists
        $existingModel = BusinessModelConfig::where('business_model', $request->business_model)->first();
        if ($existingModel) {
            return response()->json([
                'message' => 'Business model already exists',
                'existing_model' => $existingModel
            ], 422);
        }

        $businessModel = BusinessModelConfig::create($request->all());

        return response()->json($businessModel, 201);
    }

    /**
     * Update a business model configuration.
     */
    public function update(Request $request, $id)
    {
        $businessModel = BusinessModelConfig::find($id);
        
        if (!$businessModel) {
            return response()->json(['message' => 'Business model not found'], 404);
        }

        $request->validate([
            'business_model' => 'sometimes|string|in:c2c,b2c,c2b,b2b',
            'allowed_client_roles' => 'sometimes|array|min:1',
            'allowed_client_roles.*' => 'string|in:individual,business,freelancer,contractor,enterprise',
            'allowed_provider_roles' => 'sometimes|array|min:1',
            'allowed_provider_roles.*' => 'string|in:individual,business,freelancer,contractor,enterprise',
            'supported_job_types' => 'sometimes|array|min:1',
            'supported_job_types.*' => 'string|in:consultation,development,design,marketing,writing,translation,data_entry,customer_support,sales',
            'supported_payment_methods' => 'sometimes|array|min:1',
            'supported_payment_methods.*' => 'string|in:credit_card,debit_card,bank_transfer,paypal,stripe,crypto,cash,check',
            'minimum_transaction_amount' => 'sometimes|numeric|min:0',
            'maximum_transaction_amount' => 'sometimes|numeric|gt:minimum_transaction_amount',
            'platform_fee_percentage' => 'sometimes|numeric|min:0|max:100',
            'platform_fee_fixed' => 'sometimes|numeric|min:0',
            'requires_contract' => 'sometimes|boolean',
            'requires_invoice' => 'sometimes|boolean',
            'requires_insurance' => 'sometimes|boolean',
            'requires_license' => 'sometimes|boolean',
            'requires_background_check' => 'sometimes|boolean',
            'description' => 'sometimes|nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean'
        ]);

        $businessModel->update($request->all());

        return response()->json($businessModel);
    }

    /**
     * Delete a business model configuration.
     */
    public function destroy($id)
    {
        $businessModel = BusinessModelConfig::find($id);
        
        if (!$businessModel) {
            return response()->json(['message' => 'Business model not found'], 404);
        }

        $businessModel->delete();

        return response()->json(['message' => 'Business model deleted successfully']);
    }

    /**
     * Toggle business model status.
     */
    public function toggleStatus(Request $request, $id)
    {
        $request->validate([
            'is_active' => 'required|boolean'
        ]);

        $businessModel = BusinessModelConfig::find($id);
        
        if (!$businessModel) {
            return response()->json(['message' => 'Business model not found'], 404);
        }

        $businessModel->update(['is_active' => $request->is_active]);

        return response()->json($businessModel);
    }
} 