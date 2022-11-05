<?php

namespace CRUD\Commands;

use CRUD\Traits\HasCRUD;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreateRoutes extends Command
{
    use HasCRUD;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:routes {controller} {table}';

    protected $routes, $model, $table, $file;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ( $this->checkFileExsists() ) return;

        if (stripos(file_get_contents($this->file), $this->routes) === false) {
            File::append($this->file, $this->routes);
            $this->info("Routes inserted in [ $this->file ] successfully!");
        } else {
            $this->error("Routes already exists in [ $this->file ]");
        }

        echo "Routes inserted in [ routes".DIRECTORY_SEPARATOR.config('crud.route-file').".php ] successfully!\n";
    }

    /**
     * checkFileExsists
     * To check routes file is exsists
     * @return void
     */
    protected function checkFileExsists()
    {
        $this->file = base_path('routes'.DIRECTORY_SEPARATOR.config('crud.route-file')).'.php';

        if (! file_exists($this->file)) {
            echo "This file [ $this->file ] not exsists\n";
            $this->error("This file [ $this->file ] not exsists");
            return true;
        }

        $this->controller = str_replace('/', '\\', $this->argument('controller'));
        $this->table      = $this->argument('table');

        $this->drawRoutes();
    }

    /**
     * drawRoutes
     *
     * To create routes
     *
     * @return void
     */
    protected function drawRoutes():void
    {
        $this->routes = "\nRoute::resource('{$this->table}', '{$this->controller}');";
    }
}
