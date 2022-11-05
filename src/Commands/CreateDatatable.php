<?php

namespace CRUD\Commands;

use CRUD\Traits\HasCRUD;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CreateDatatable extends GeneratorCommand
{
    use HasCRUD;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:datatable {model} {table?}';
    protected $datatable, $model, $table, $table_details = array();

    protected $type = 'datatable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'To create datatable class for model';

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\DataTables';
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->checkModelExists()) return;
        if ($this->checkDatatableExists()) return;

        $this->addTranslations();

        $this->copyTransFiles();

        $path = $this->getPath( $this->datatable );
        $this->makeDirectory($path);
        $this->files->put($path, $this->getSourceFile());

        $this->info("$this->type class<options=bold> [ {$this->datatable}.php ] </>created successfully!");
    }

    /**
     * getSourceFile
     *
     * @return string
     */
    private function getSourceFile():string
    {
        $name = class_basename($this->datatable);
        $namespace = str_replace("\\$name", '', $this->datatable);
        $dir = convertCamelCaseTo( Str::plural($this->argument('model')) );
        $dir = str_replace('/', DIRECTORY_SEPARATOR, $dir);

        $stub_vars = [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => $name,
            '{{ modelNamespace }}' => get_class($this->model),
            '{{ modelName }}' => class_basename($this->model),
            '{{ table }}' => $this->table,
            '{{ withRelations }}' => $this->getRelatedTables(),
            '{{ columns }}' => $this->getTableColumns(),
            '{{ dir }}'     => str_replace(['/', '\\'], '.', $dir),
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
     * getRelatedTables
     * To load relation in with method query
     * @return void
     */
    protected function getRelatedTables()
    {
        $relations = '';
        foreach (getTableRelations($this->table) as $column)
            $relations .= "'".getRelationName($column->fk_table)."', ";

        return $relations !== '' ? "->with([". trim($relations, ', ') ."])" : '';
    }

    /**
     * getTableColumns
     * To draw datatable columns
     * @return void
     */
    protected function getTableColumns()
    {
        $fk_columns = getTableRelations($this->table);
        $rows = '';
        foreach ($this->model->getFillable() as $column) {
            $translate = config('crud.translation-file-name').".$this->table.$column";
            $name = $column;

            if( isset( $fk_columns[$column] ) ) {
                $related_table = $fk_columns[$column]->fk_table;
                $first_column = getFirstStringColumn( DB::select("SHOW FULL COLUMNS FROM $related_table") );
                $translate = config('crud.translation-file-name').".$related_table.$first_column";
                $name = getRelationName($related_table).".{$first_column}";
            }

            $rows .= "\n\t\t\tColumn::make('$name')->title(trans('$translate')),";
        }

        return $rows;
    }

    /**
     * addTranslations
     *
     * @return void
     */
    protected function addTranslations()
    {
        $trans = '';
        foreach($this->model->getFillable() as $column) {
            if (stripos($column, '_id') !== false) continue;
            $trans .= "\t\t'$column' => '". ucwords( str_replace('_', ' ', $column) ) ."',\n";
        }
        $trans .= "\n\t],\n\n";

        foreach (config('crud.languages') as $lang) {
            $file = $this->getTransFile($lang);
            if (!file_exists($file)) continue;
            $contents = file($file);
            $size = count($contents);
            $contents[$size -1] = $trans.$contents[$size-1];
            file_put_contents($file, $contents);
        }

        $this->info('translation inputs added successfully');
    }

    protected function getTransFile($lang)
    {
        $file = base_path("lang/$lang/".config('crud.translation-file-name').".php");
        if (!File::exists($file)) {
            $this->makeDirectory($file);
            $content = "<?php\n\nreturn [\n];";
            $this->files->put($file, $content);
        }

        return $file;
    }

    protected function copyTransFiles()
    {
        $path = base_path() . DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.'crud'.DIRECTORY_SEPARATOR."trans-datatable.stub";
        foreach (config('crud.languages') as $lang) {
            lang_path($lang);
            copy($path, lang_path($lang).DIRECTORY_SEPARATOR.'datatable.php');
        }

        $this->info('datatable translation file added successfully');
    }

    /**
     * checkDatatableExists
     * check if datatable class is exists
     * @return void
     */
    protected function checkDatatableExists()
    {
        $this->datatable = $this->qualifyClass( $this->argument('model').'DataTable' );
        $this->model = $this->qualifyModel( $this->argument('model') );

        if ($this->alreadyExists($this->datatable)) {
            $this->error("$this->type [ $this->datatable ] already exists!");
            return true;
        }
        $this->model = app($this->model);
        $this->table = $this->model->getTable();
    }
}
