<?php

namespace CRUD\Commands;

use CRUD\Traits\HasCRUD;
use Illuminate\Support\Str;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CreateViews extends GeneratorCommand
{
    use HasCRUD;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:views {model} {table?}';
    protected $columns = array();
    protected $relations = array();
    protected $model;
    protected $table;
    protected $inputs;
    protected $dir;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new blade template.';

    protected $type = 'view';

    protected function getStub()
    {
        return  base_path() .DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.'crud'.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->checkModelExists();

        $this->createDir();

        foreach (['index', 'create', 'show', 'edit', 'inputs', 'action'] as $page) {
            $this->createFiles($page);
        }

        $this->appendText();

        $this->line('all <bg=green;fg=white;options=bold>Views</> Created Successfully!');
    }

    /**
     * Get the view full path.
     *
     * @param string $view
     *
     * to replace . to / => [backend.clients.form => backend/clients/form]
     *
     * @return string
     */
    public function createDir()
    {
        $dir =  'resources/views/'.$this->getDir();
        $this->dir = str_replace('/', DIRECTORY_SEPARATOR, $dir);
        $this->model = app( $this->model );
    }

    protected function createFiles($page)
    {
        $file = $this->dir.DIRECTORY_SEPARATOR."{$page}.blade.php";

        if (file_exists($file)) {
            echo "----- The [ $file ] blade already exists!\n";
        } else {
            $this->makeDirectory($file);
            $this->files->put($file, $this->getSourceFile($page));
            echo "----- The [ $file ] blade created successfully!\n";
        }

    }

        /**
     * getSourceFile
     *
     * @param  string $page
     * @return string
     */
    private function getSourceFile(string $page): string
    {
        $model_name = class_basename($this->model);
        $stub_vars = [
            '{{ layout }}'      => config('crud.layout'),
            '{{ table }}'       => $this->model->getTable(),
            '{{ table_title }}' => convertCamelCaseTo( Str::plural( $model_name ), ' ' ),
            '{{ dir }}'         => str_replace(['/', '\\'], '.', $this->getDir()),
            '{{ model }}'       => $model_name,
            '{{ model_var }}'   => convertCamelCaseTo($model_name, '_'),
        ];

        return $this->getStubContent($page, $stub_vars);
    }

    /**
     * getStubContent
     *
     * @param  string $page
     * @param  array $stub_vars
     * @return string
     */
    private function getStubContent(string $page, array $stub_vars = []): String
    {
        $file = $this->getStub().DIRECTORY_SEPARATOR."{$page}.stub";
        if (! file_exists($file)) return '';

        $content  = file_get_contents($file);

        foreach ($stub_vars as $name => $value)
            $content = str_replace($name, $value, $content);

        return $content;
    }

    protected function getDir()
    {
        $dir = convertCamelCaseTo( Str::plural($this->argument('model')) );
        return str_replace('/', DIRECTORY_SEPARATOR, $dir);
    }

    public function appendText()
    {
        $this->getColumns();
        $file = $this->dir.DIRECTORY_SEPARATOR."inputs.blade.php";

        File::put($file, str_replace('{{-- HTML Code --}}', $this->createFormContent(), file_get_contents($file)));
    }

    protected function getColumns() :void
    {
        foreach (DB::select('SHOW FULL COLUMNS FROM '.$this->table) as $column) {
            if (in_array($column->Field, ['id', 'created_at', 'updated_at'])) continue;
            array_push($this->columns, $column);
        }
    }

    protected function createFormContent() :string
    {
        $inputs = '';
        foreach ($this->columns as $column) {
            $inputs .= $this->html($column)."\n\n";
        }
        return $inputs;
    }

    protected function getInputType($column) :string
    {
        if (stripos($column->Type, 'tinyint') !== false)
            return 'checkbox';

        if (stripos($column->Field, '_id') !== false)
            return 'select';

        if (stripos($column->Type, 'date') !== false)
            return 'date';

        if (stripos($column->Comment, 'file') !== false)
            return 'file';

        if (stripos($column->Comment, 'image') !== false)
            return 'image';

        if (stripos($column->Comment, 'audio') !== false)
            return 'audio';

        if (stripos($column->Comment, 'video') !== false)
            return 'video';

        return "input";
    }

    protected function html($column) :string
    {
        $related_table = stripos($column->Field, '_id') !== false ? Str::plural( str_replace('_id', '', $column->Field) ) : '';
        $type = $this->getInputType($column);
        $content = file_get_contents( getStubFile("pages/html.$type.stub") );
        return str_replace([
            '{{ table }}',
            '{{ trans_column }}',
            '{{ column }}',
            '{{ required }}',
            '{{ type }}',
            '{{ related }}',
            '{{ model_var }}',
        ], [
            $related_table,
            "{$this->table}.$column->Field",
            $column->Field,
            stripos($column->Null, 'NO') !== false ? 'required' : '',
            stripos($column->Type, 'varchar') !== false || stripos($column->Type, 'text') !== false
                    ? 'text'
                    : (stripos($column->Type, 'date') !== false || stripos($column->Type, 'timestamp') !== false ? 'date' : 'number'),
            $type == 'select' ? Str::plural( str_replace('_id', '', $column->Field)) : '',
            convertCamelCaseTo( class_basename($this->model) , '_')
        ], $content);
    }

}
