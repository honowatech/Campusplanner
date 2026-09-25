<?php

namespace Modules\Planning\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class PlanningServiceProvider extends ServiceProvider
{
    /**
     * @var string
     */
    protected $moduleName = 'Planning';

    /**
     * @var string
     */
    protected $moduleNameLower = 'planning';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerAssets();
        $this->loadViewsFrom(__DIR__.'/../Resources/views/components', 'planning');
        require_once __DIR__.'/../helper.php';
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower.'.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'), $this->moduleNameLower
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/'.$this->moduleNameLower);

        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);

        // Register anonymous components
        $this->registerAnonymousComponents();
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/'.$this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
            $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'Resources/lang'));
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (Config::get('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->moduleNameLower)) {
                $paths[] = $path.'/modules/'.$this->moduleNameLower;
            }
        }

        return $paths;
    }

    /**
     * Register anonymous components for the CRM module.
     *
     * @return void
     */
    protected function registerAnonymousComponents()
    {
        $componentsPath = module_path($this->moduleName, 'Resources/views/components');

        if (is_dir($componentsPath)) {
            $components = glob($componentsPath.'/*.blade.php');

            foreach ($components as $component) {
                $componentName = basename($component, '.blade.php');
                $this->loadViewComponentsAs($this->moduleNameLower, [
                    'components.'.$componentName => 'Modules\\Planning\\View\\Components\\'.ucfirst($componentName),
                ]);
            }
        }
    }

    /**
     * Register module assets.
     *
     * @return void
     */
    protected function registerAssets()
    {
        // Publish JavaScript assets
        $this->publishes([
            module_path($this->moduleName, 'Resources/assets/js') => public_path('modules/planning/js'),
        ], 'planning-js');

        // Publish CSS assets if needed
        $this->publishes([
            module_path($this->moduleName, 'Resources/assets/css') => public_path('css/modules/planning/css'),
        ], 'planning-css');
    }
}
