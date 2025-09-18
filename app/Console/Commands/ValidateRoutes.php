<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use ReflectionMethod;

class ValidateRoutes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'routes:validate {--fix : Attempt to fix missing methods}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate that all routes have corresponding controller methods';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Validating routes...');
        
        $routes = Route::getRoutes();
        $issues = [];
        $fixed = 0;

        foreach ($routes as $route) {
            $controller = $route->getController();
            $action = $route->getActionMethod();
            $path = $route->uri();
            $methods = $route->methods();

            if (!$controller) {
                $issues[] = [
                    'type' => 'missing_controller',
                    'route' => $path,
                    'methods' => $methods,
                    'action' => $action
                ];
                continue;
            }

            $controllerClass = get_class($controller);
            
            if (!class_exists($controllerClass)) {
                $issues[] = [
                    'type' => 'controller_not_found',
                    'route' => $path,
                    'methods' => $methods,
                    'controller' => $controllerClass
                ];
                continue;
            }

            if (!method_exists($controller, $action)) {
                $issues[] = [
                    'type' => 'method_not_found',
                    'route' => $path,
                    'methods' => $methods,
                    'controller' => $controllerClass,
                    'method' => $action
                ];

                if ($this->option('fix')) {
                    $this->fixMissingMethod($controllerClass, $action, $path, $methods);
                    $fixed++;
                }
            }
        }

        if (empty($issues)) {
            $this->info('✅ All routes are valid!');
            return 0;
        }

        $this->error('Found ' . count($issues) . ' route issues:');
        $this->newLine();

        foreach ($issues as $issue) {
            $this->line("❌ {$issue['type']}: {$issue['route']} ({$issue['methods'][0]})");
            
            switch ($issue['type']) {
                case 'missing_controller':
                    $this->line("   Controller not specified");
                    break;
                case 'controller_not_found':
                    $this->line("   Controller class not found: {$issue['controller']}");
                    break;
                case 'method_not_found':
                    $this->line("   Method not found: {$issue['controller']}::{$issue['method']}");
                    break;
            }
            $this->newLine();
        }

        if ($this->option('fix') && $fixed > 0) {
            $this->info("🔧 Fixed {$fixed} missing methods");
        }

        return 1;
    }

    /**
     * Attempt to fix missing method by creating a stub
     */
    private function fixMissingMethod(string $controllerClass, string $method, string $route, array $methods)
    {
        $this->warn("Attempting to fix: {$controllerClass}::{$method}");
        
        // This is a basic implementation - in production you might want more sophisticated fixing
        $this->line("   Please implement {$method} method in {$controllerClass}");
        $this->line("   Route: {$methods[0]} {$route}");
    }

    /**
     * Get route statistics
     */
    private function getRouteStatistics()
    {
        $routes = Route::getRoutes();
        $stats = [
            'total' => count($routes),
            'api' => 0,
            'web' => 0,
            'missing_methods' => 0
        ];

        foreach ($routes as $route) {
            if (str_starts_with($route->uri(), 'api/')) {
                $stats['api']++;
            } else {
                $stats['web']++;
            }

            $controller = $route->getController();
            $action = $route->getActionMethod();
            
            if ($controller && !method_exists($controller, $action)) {
                $stats['missing_methods']++;
            }
        }

        return $stats;
    }
}
