<?php

namespace CRUD\Commands;

use CRUD\Traits\HasCRUD;
use Illuminate\Console\GeneratorCommand;

class CreateController extends GeneratorCommand
{
    use HasCRUD;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature  = 'crud:controller {model} {table?}';

    protected $controller, $model, $table;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'this command to create a controller from database table';

    protected $type = 'controller';

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . "\Http\Controllers";
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ( $this->checkModelExists() ) return;

        if ( $this->checkControllerExists() ) return;

        $path = $this->getPath($this->controller);

        $this->makeDirectory($path);

        $this->files->put($path, $this->getSourceFile());

        $this->info("model class<options=bold> {$this->controller}.php </>created successfully!");
    }

    /**
     * getSourceFile
     *
     * @return string
     */
    private function getSourceFile(): string
    {
        $controller  = class_basename($this->controller);
        $namespace = str_replace("\\$controller", '', $this->controller);
        $model = class_basename($this->model);
        $model_namespace = str_replace('/', '\\', $this->argument('model'));
        $sub_folder = trim( str_replace([$model, '/'], ['', '.'], $this->argument('model')), '.');
        $dir = trim( $sub_folder.'.'.$this->table, '.');

        $stub_vars = [
            '{{ namespace }}'       => trim($namespace, '\\'),
            '{{ class }}'           => $controller,
            '{{ model_namespace }}' => $model_namespace,
            '{{ model }}'           => $model,
            '{{ model_var }}'       => convertCamelCaseTo($model, '_'),
            '{{ table }}'           => $this->table,
            '{{ dir }}'             => strtolower($dir),
            '{{ appends }}'         => $this->createAppends(),
        ];

        return $this->getStubContent($stub_vars);
    }

    /**
     * getStubContent
     *
     * @param  array $stub_vars
     * @return string
     */
    private function getStubContent(array $stub_vars = []): String
    {
        $content  = file_get_contents($this->getStub());

        foreach ($stub_vars as $name => $value)
            $content = str_replace($name, $value, $content);

        return $content;
    }

    protected function createAppends()
    {
        $appends = "";

        foreach (getTableRelations($this->table) as $column) {
            $model = getFilesInDir(base_path('app/Models'), getTableModel($column->fk_table));
            if (! $model) continue;
            $model_Class = app($model);

            $fk_column_name = $model_Class->getFillable()[0] ?? getFirstStringColumn( getTableColumns( $column->fk_table ) );
            $appends .= "\n\t\t\t'{$column->fk_table}' => \\{$model}::pluck('{$fk_column_name}', 'id'),";
        }
        return $appends;
    }


    /**
     * checkModelExists
     *
     *  This method to check is model is alleady exists
     *
     * @return bool
     */
    protected function checkControllerExists(): bool
    {
        $this->controller = $this->qualifyClass($this->argument('model') . 'Controller');

        if ($this->alreadyExists($this->controller)) {
            $this->error("This $this->type $this->controller already exists!");
            return true;
        }

        $this->model = app($this->model);

        return false;
    }
}
