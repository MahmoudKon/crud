<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * getFilesInDir
 *
 * @param  string $dir
 * @param  string|null $specific_class
 * @return array|string
 */
function getFilesInDir(string $dir, $specific_class = null) :array|string
{
    $files = [];

    foreach (File::allFiles($dir) as $file) {
        $file_path = str_replace(['/', '.php', 'app'], ['\\', '', 'App'], strstr($file->getPathname(), 'app'));

        if ($specific_class && "$specific_class.php" == $file->getFilename())
            return $file_path;

        $files[$file->getRelativePathname()] = $file_path;
    }

    return $specific_class ? '' : $files;
}

/**
 * getRelationsDetails
 *
 *  This query to get all fk columns with table name
 *
 * @param  string $table
 * @return array
 */
function getTableRelations($table) :array
{
    $data = DB::select("SELECT `column_name`, `referenced_table_name` AS fk_table, `referenced_column_name`  AS fk_column
                            FROM `information_schema`.`KEY_COLUMN_USAGE` WHERE `constraint_schema` = SCHEMA()
                            AND `table_name` = '$table' AND `referenced_column_name` IS NOT NULL"
                    );

    return array_combine(collect($data)->pluck('column_name')->toArray(), $data);
}

/**
 * getTableColumns
 *
 * @param  string $table
 * @return array
 */
function getTableColumns(string $table):array
{
    return DB::select("SHOW FULL COLUMNS FROM $table");
}

/**
 * getTableModel
 *
 *  Convert database table name to model class name
 *  users => User
 *
 * @param  string $table
 * @return string
 */
function getTableModel(string $table) :string
{
    return Str::studly( Str::singular($table) );
}

/**
 * getModelTable
 *
 *  Convert model name to table name
 *  users => User
 *
 * @param  string $model
 * @return string
 */
function getModelTable(string $model) :string
{
    return Str::plural( Str::snake($model) );
}

/**
 * getRelationName
 *
 *  To convert table name to relation name
 *  users => user
 *
 * @param  string $table
 * @return string
 */
function getRelationName(string $table) :string
{
    return Str::lcfirst( getTableModel($table) );
}

/**
 * getFirstStringColumn
 *
 *  get first column his type is string from table
 *  EX => [id, date, name, email]  return name
 *
 * @param  array $columns
 * @return string
 */
function getFirstStringColumn(array $columns) :string
{
    foreach ($columns as $column) {
        if (stripos($column->Type, 'varchar') !== false)
            return $column->Field;
    }

    return $columns[0]->Field;
}

function convertCamelCaseTo(string $string, string $us = '_') :string
{
    return strtolower( preg_replace('/([a-z]+)([A-Z]+)/', '$1'.$us.'$2', $string) );
}

function getStubFile($file)
{
    $stub = 'stubs'.DIRECTORY_SEPARATOR.'crud'.DIRECTORY_SEPARATOR.$file;
    if (file_exists( base_path($stub) ))
        return  base_path($stub);
    return __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.$stub;
}
