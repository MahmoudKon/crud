<?php

namespace CRUD\Commands;

use CRUD\Traits\HasCRUD;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\Schema;

class CreateModel extends GeneratorCommand
{
    use HasCRUD;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:model {model} {table?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'To create model class';

    protected $model, $table, $namespaces, $timestamps = "\n\tpublic \$timestamps = false;\n";

    protected $type = 'model';

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . "\Models";
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ( $this->checkModelExists() ) return;

        $path = $this->getPath( $this->model );
        $this->makeDirectory($path);

        $this->files->put($path, $this->getSourceFile());
        $this->info("model class<options=bold> {$this->model}.php </>created successfully!");
    }

    /**
     * getSourceFile
     *
     * @return string
     */
    private function getSourceFile():string
    {
        $name = class_basename($this->model);
        $namespace = str_replace("\\$name", '', $this->model);

        $stub_vars = [
            '{{ namespace }}' => $namespace,
            '{{ relations }}' => $this->relations(),
            '{{ namespaces }}' => $this->namespaces,
            '{{ class }}' => $name,
            '{{ table }}' => $this->table,
            '{{ fillable }}' => $this->fillable(),
            '{{ timestamps }}' => $this->timestamps
        ];

        return $this->getStubContent($stub_vars);
    }

    /**
     * getStubContent
     *
     * @param  array $stub_vars
     * @return string
     */
    private function getStubContent(array $stub_vars = []):String
    {
        $content  = file_get_contents($this->getStub());

        foreach ($stub_vars as $name => $value)
            $content = str_replace($name, $value, $content);

        return $content;
    }

    /**
     * relations
     *  This method to draw relations in model
     * @return void
     */
    protected function relations()
    {
        $relations = '';
        foreach (getTableRelations($this->table) as $column) {
            $model_name = "self";

            if ($column->fk_table != $this->table) {
                $model_name = getTableModel($column->fk_table);
                $class_namespace   = getFilesInDir(app_path('Models'), $model_name);
                $this->namespaces .= $class_namespace ? "use {$class_namespace};\n" : '';
            }

            $relations .= $this->drawRelation($column, $model_name);
        }
        return $relations;
    }

    /**
     * drawRelation
     *  return relation method in string
     * @param  object $column
     * @param  string $fk_model
     * @return void
     */
    protected function drawRelation(object $column, string $fk_model):string
    {
        $first_column = getFirstStringColumn( getTableColumns( $column->fk_table ) );
        $relation  = "\n\tpublic function ".getRelationName($column->fk_table)."() \n\t{";
        $relation .= "\n\t\treturn \$this->belongsTo({$fk_model}::class, '{$column->column_name}', '{$column->fk_column}')->withDefault(['{$column->fk_column}' => null, '$first_column' => '']);";
        $relation .= "\n\t}\n";
        return $relation;
    }

    /**
     * relations
     *  This method to draw relations in model
     * @return void
    */
    protected function fillable()
    {
        $fillables = '';
        $columns = Schema::getColumnListing($this->table);
        if (in_array('created_at', $columns)) $this->timestamps = '';
        foreach ($columns as $column) {
            if (in_array($column, ['id', 'created_at', 'updated_at'])) continue;
            $fillables .= "'$column', ";
        }

        return rtrim($fillables, ', ');
    }
}
