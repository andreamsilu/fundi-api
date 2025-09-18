<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use ReflectionMethod;

class GenerateApiDocs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:docs {--output=api-docs.json : Output file path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate comprehensive API documentation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating API documentation...');
        
        $routes = Route::getRoutes();
        $apiRoutes = [];
        $statistics = [
            'total_routes' => 0,
            'api_routes' => 0,
            'missing_methods' => 0,
            'controllers' => [],
            'methods' => []
        ];

        foreach ($routes as $route) {
            $path = $route->uri();
            $methods = $route->methods();
            $controller = $route->getController();
            $action = $route->getActionMethod();
            $middleware = $route->gatherMiddleware();

            // Skip non-API routes
            if (!str_starts_with($path, 'api/')) {
                continue;
            }

            $statistics['total_routes']++;
            $statistics['api_routes']++;

            $routeInfo = [
                'path' => $path,
                'methods' => array_filter($methods, fn($method) => !in_array($method, ['HEAD', 'OPTIONS'])),
                'controller' => $controller ? get_class($controller) : null,
                'action' => $action,
                'middleware' => $middleware,
                'has_method' => $controller && method_exists($controller, $action),
                'parameters' => $this->getRouteParameters($route),
                'validation' => $this->getValidationRules($controller, $action)
            ];

            if ($controller) {
                $controllerClass = get_class($controller);
                $statistics['controllers'][$controllerClass] = ($statistics['controllers'][$controllerClass] ?? 0) + 1;
                
                if (!method_exists($controller, $action)) {
                    $statistics['missing_methods']++;
                    $routeInfo['status'] = 'missing_method';
                } else {
                    $routeInfo['status'] = 'implemented';
                }
            } else {
                $routeInfo['status'] = 'missing_controller';
                $statistics['missing_methods']++;
            }

            $apiRoutes[] = $routeInfo;
        }

        // Generate documentation
        $documentation = [
            'api_info' => [
                'title' => 'Fundi API Documentation',
                'version' => '1.0.0',
                'description' => 'Comprehensive API documentation for Fundi platform',
                'generated_at' => now()->toISOString(),
                'base_url' => config('app.url') . '/api'
            ],
            'statistics' => $statistics,
            'routes' => $apiRoutes,
            'controllers' => $this->getControllerInfo(),
            'endpoints_by_category' => $this->categorizeEndpoints($apiRoutes)
        ];

        $outputFile = $this->option('output');
        file_put_contents($outputFile, json_encode($documentation, JSON_PRETTY_PRINT));

        $this->info("✅ API documentation generated successfully!");
        $this->line("📄 Output file: {$outputFile}");
        $this->line("📊 Statistics:");
        $this->line("   - Total routes: {$statistics['total_routes']}");
        $this->line("   - API routes: {$statistics['api_routes']}");
        $this->line("   - Missing methods: {$statistics['missing_methods']}");
        $this->line("   - Controllers: " . count($statistics['controllers']));

        if ($statistics['missing_methods'] > 0) {
            $this->warn("⚠️  {$statistics['missing_methods']} routes have missing methods!");
        }

        return 0;
    }

    /**
     * Get route parameters
     */
    private function getRouteParameters($route): array
    {
        $parameters = [];
        $parameterNames = $route->parameterNames();
        
        foreach ($parameterNames as $name) {
            $parameters[] = [
                'name' => $name,
                'type' => 'string', // Default type
                'required' => true
            ];
        }

        return $parameters;
    }

    /**
     * Get validation rules for a controller method
     */
    private function getValidationRules($controller, string $method): array
    {
        if (!$controller || !method_exists($controller, $method)) {
            return [];
        }

        try {
            $reflection = new ReflectionMethod($controller, $method);
            $source = file($reflection->getFileName());
            $methodSource = implode('', array_slice($source, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));

            // Extract validation rules (basic implementation)
            if (preg_match('/Validator::make\([^,]+,\s*\[(.*?)\]/s', $methodSource, $matches)) {
                return $this->parseValidationRules($matches[1]);
            }
        } catch (\Exception $e) {
            // Ignore reflection errors
        }

        return [];
    }

    /**
     * Parse validation rules from source code
     */
    private function parseValidationRules(string $rulesString): array
    {
        $rules = [];
        $lines = explode("\n", $rulesString);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/\'([^\']+)\'\s*=>\s*\'([^\']+)\'/', $line, $matches)) {
                $rules[$matches[1]] = $matches[2];
            }
        }

        return $rules;
    }

    /**
     * Get controller information
     */
    private function getControllerInfo(): array
    {
        $controllers = [];
        $controllerPath = app_path('Http/Controllers');
        $files = glob($controllerPath . '/*.php');

        foreach ($files as $file) {
            $className = basename($file, '.php');
            $fullClassName = "App\\Http\\Controllers\\{$className}";
            
            if (class_exists($fullClassName)) {
                $reflection = new ReflectionClass($fullClassName);
                $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
                
                $publicMethods = [];
                foreach ($methods as $method) {
                    if ($method->class === $fullClassName && !$method->isConstructor()) {
                        $publicMethods[] = $method->name;
                    }
                }

                $controllers[$fullClassName] = [
                    'file' => $file,
                    'methods' => $publicMethods,
                    'total_methods' => count($publicMethods)
                ];
            }
        }

        return $controllers;
    }

    /**
     * Categorize endpoints by functionality
     */
    private function categorizeEndpoints(array $routes): array
    {
        $categories = [
            'authentication' => [],
            'users' => [],
            'jobs' => [],
            'applications' => [],
            'portfolio' => [],
            'payments' => [],
            'notifications' => [],
            'admin' => [],
            'monitoring' => [],
            'other' => []
        ];

        foreach ($routes as $route) {
            $path = $route['path'];
            $category = 'other';

            if (str_contains($path, '/auth/')) {
                $category = 'authentication';
            } elseif (str_contains($path, '/users/') || str_contains($path, '/fundi-applications/')) {
                $category = 'users';
            } elseif (str_contains($path, '/jobs/')) {
                $category = 'jobs';
            } elseif (str_contains($path, '/job-applications/') || str_contains($path, '/apply')) {
                $category = 'applications';
            } elseif (str_contains($path, '/portfolio/')) {
                $category = 'portfolio';
            } elseif (str_contains($path, '/payments/')) {
                $category = 'payments';
            } elseif (str_contains($path, '/notifications/')) {
                $category = 'notifications';
            } elseif (str_contains($path, '/admin/monitor/') || str_contains($path, '/admin/logs')) {
                $category = 'monitoring';
            } elseif (str_contains($path, '/admin/')) {
                $category = 'admin';
            }

            $categories[$category][] = $route;
        }

        return $categories;
    }
}
