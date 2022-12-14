<?php

namespace CRUD\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class GenerateClasses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud {table} {--namespace=}'; // EX => php artisan make:crud clients
    protected $table, $model;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command to generate model, controller, request file, service and datatable class';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->init();

        if (file_exists("app/Models/{$this->model}.php")) {
            $this->error("model class app/Models/{$this->model}.php already exists!");
        } else {
            Artisan::call("crud:model {$this->model}");
            $this->info("model class<options=bold> app/Models/{$this->model}.php </>created successfully!");
        }

        if (file_exists("app/Http/Requests/{$this->model}Request.php")) {
            $this->error("request class app/Http/Requests/{$this->model}Request.php already exists!");
        } else {
            Artisan::call("crud:request {$this->model}");
            $this->info("request class<options=bold> app/Http/Requests/{$this->model}Request.php </>created successfully!");
        }

        if (file_exists("app/DataTables/{$this->model}DataTable.php")) {
            $this->error("datatable class app/DataTables/{$this->model}Datatable already exists!");
        } else {
            Artisan::call("crud:datatable {$this->model}");
            $this->info("datatable class<options=bold> app/DataTables/{$this->model}Datatable.php </>created successfully!");
        }

        if (file_exists("app/Http/Controllers/{$this->model}Controller.php")) {
            $this->error("controller class app/Http/Controllers/{$this->model}Controller already exists!");
        } else {
            Artisan::call("crud:controller {$this->model}");
            $this->info("controller class<options=bold> app/Http/Controllers/{$this->model}Controller.php </>created successfully!");
        }

        $view_path = $this->option('namespace') . '/'.$this->argument('table');
        Artisan::call("crud:views {$this->model} {$this->table}");

        Artisan::call("crud:routes {$this->model}Controller {$this->table}");

        $this->info("<options=bold>All classes genrated successfully!</>");
    }

    protected function init()
    {
        $this->table = $this->argument('table');
        $this->model = getTableModel($this->table);
        if ($this->option('namespace')) $this->model = $this->option('namespace').'/'.$this->model;
    }
}
