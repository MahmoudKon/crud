<?php
namespace CRUD;

use Illuminate\Support\ServiceProvider;
use CRUD\Commands\GenerateClasses;
use CRUD\Commands\CreateController;
use CRUD\Commands\CreateDatatable;
use CRUD\Commands\CreateModel;
use CRUD\Commands\CreateRequest;
use CRUD\Commands\CreateRoutes;
use CRUD\Commands\CreateViews;

class GenerateCrudServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            GenerateClasses::class,
            CreateController::class,
            CreateDatatable::class,
            CreateModel::class,
            CreateRequest::class,
            CreateRoutes::class,
            CreateViews::class,
        ]);

        $this->mergeConfigFrom(
            __DIR__.'/config/crud.php', 'crud'
        );

        $this->publishes([
            __DIR__.'/stubs' => base_path('stubs'),
        ], 'crud-stubs');
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/config' => config_path('/'),
        ], 'crud-config');

        $this->publishes([
            __DIR__.'/commands' => app_path('Console/Commands/CRUD'),
        ], 'crud-commands');

        $this->publishes([
            __DIR__.'/stubs' => base_path('stubs'),
        ], 'crud-stubs');
    }
}
