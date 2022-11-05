<?php

namespace CRUD\Commands;

use CRUD\Traits\HasCRUD;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\DB;

class CreateRequest extends GeneratorCommand
{
    use HasCRUD;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:request {model} {table?}';

    protected $model, $table, $request, $validations, $translations;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create request file from table migration';

    protected $type = 'request';

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Http\Requests';
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->checkModelExists()) return;

        if ($this->checkRequestExists()) return;

        $path = $this->getPath($this->request);

        $this->makeDirectory($path);

        $this->files->put($path, $this->getSourceFile());

        $this->info("model class<options=bold> {$this->request}.php </>created successfully!");
    }

    /**
     * checkModelExists
     *
     *  This method to check is model is alleady exists
     *
     * @return bool
     */
    protected function checkRequestExists(): bool
    {
        $this->request = $this->qualifyClass($this->argument('model') . 'Request');

        if ($this->alreadyExists($this->request)) {
            $this->error("This $this->type $this->request already exists!");
            return true;
        }

        $this->model = app($this->model);

        $this->getColumns();
        return false;
    }

    /**
     * getColumns
     * this method to create array of validations and translations
     * @return void
     */
    protected function getColumns()
    {
        $fk_columns = getTableRelations($this->table);
        foreach (DB::select('SHOW FULL COLUMNS FROM ' . $this->table) as $column) {
            if (!in_array($column->Field, $this->model->getFillable())) continue;

            $trans = "trans('".config('crud.translation-file-name').".$this->table.{$column->Field}')";
            $fk_column = false;

            if (isset($fk_columns[$column->Field])) {
                $fk_column = $fk_columns[$column->Field];
                $trans = "trans('".config('crud.translation-file-name').".{$fk_column->fk_table}')";
            }

            if ($column->Type == "json" && stripos($column->Comment, 'translations') !== false) {
                foreach (config('crud.languages') as $key => $lang) {
                    $this->validations  .= "'$column->Field.{$lang}' => '" . $this->getValidation($column, $fk_column) . "',\n\t\t\t";
                    $this->translations .= "'$column->Field.{$lang}' => $trans,\n\t\t\t";
                }
            } else {
                $this->validations  .= "'$column->Field' => '" . $this->getValidation($column, $fk_column) . "',\n\t\t\t";
                $this->translations .= "'$column->Field' => $trans,\n\t\t\t";
            }
        }
    }

    /**
     * getValidation
     *
     * @param  object $column
     * @param  object|string $fk_column
     * @return string
     */
    protected function getValidation(object $column, object|string $fk_column):string
    {
        $validate = [];

        if (stripos($column->Null, "Yes") !== false || stripos($column->Type, "tinyint") !== false) array_push($validate, 'nullable');
        else array_push($validate, 'required');

        if (stripos($column->Type, "tinyint") !== false)
            array_push($validate, 'boolean');

        else if (stripos($column->Type, "int") !== false || stripos($column->Type, "decimal") !== false)
            array_push($validate, 'numeric');

        else if (stripos($column->Type, "timestamp") !== false || stripos($column->Type, "date") !== false)
            array_push($validate, 'date');

        else {
            $this->getFileValidation($column, $validate);
        }

        if ($fk_column)
            array_push($validate, "exists:$fk_column->fk_table,$fk_column->fk_column");

        if (stripos($column->Key, "UNI") !== false) // is unique
            array_push($validate, "unique:{$this->table},$column->Field,'.request()->id.'");

        return implode('|', $validate);
    }

    /**
     * getFileValidation
     *  To handle validation for file column
     * @param  object $column
     * @param  array $validate
     * @return void
     */
    protected function getFileValidation(object $column, array &$validate)
    {
        if (stripos($column->Comment, 'image') !== false)
            array_push($validate, 'image|mimes:png,jpg,jpeg');

        else if (stripos($column->Comment, 'audio') !== false)
            array_push($validate, 'file|mimes:mp3');

        else if (stripos($column->Comment, 'video') !== false)
            array_push($validate, 'file|mimes:mp4');

        else if (stripos($column->Comment, 'file') !== false)
            array_push($validate, 'file|mimes:pdf,docx,xsl');
        else
            array_push($validate, 'string');
    }

    /**
     * getSourceFile
     *
     * @return string
     */
    private function getSourceFile(): string
    {
        $name = class_basename($this->request);
        $namespace = str_replace("\\$name", '', $this->request);

        $stub_vars = [
            '{{ namespace }}'   => $namespace,
            '{{ class }}'       => $name,
            '{{ validation }}'  => $this->validations,
            '{{ translation }}' => $this->translations,
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
}
