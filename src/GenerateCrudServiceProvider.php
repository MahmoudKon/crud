<?php
namespace CRUD;

use Illuminate\Support\ServiceProvider;
use CRUD\Commands\GenerateClasses;
use CRUD\Commands\CreateController;
use CRUD\Commands\CreateDatatable;
use CRUD\Commands\CreateModel;
use CRUD\Commands\CreateRequest;
use CRUD\Commands\CreateRoute;
use CRUD\Commands\CreateService;
use CRUD\Commands\CreateView;

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
            CreateRoute::class,
            CreateService::class,
            CreateView::class,
        ]);
        
        $this->mergeConfigFrom(
            __DIR__.'/config/crud.php', 'messenger'
        );
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
            __DIR__.'/stubs' => base_path('stubs/crud'),
        ], 'crud-stubs');
    }
}