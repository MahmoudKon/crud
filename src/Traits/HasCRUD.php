<?php

namespace CRUD\Traits;

use Illuminate\Support\Facades\Schema;

trait HasCRUD
{
    protected function getStub()
    {
        return getStubFile("$this->type.stub");
    }

    /**
     *  checkModelExists
     *
     *  This method to check if model exists or the table not exists
     *
     *  @return bool
     */
    protected function checkModelExists() :bool
    {
        if ($this->isReservedName($this->argument('model'))) {
            $this->error('The name "'.$this->argument('model').'" is reserved by PHP.');
            return true;
        }

        $this->model = $this->qualifyModel( $this->argument('model'));
        $this->table = $this->argument('table') ?? getModelTable(class_basename($this->model));

        if (! Schema::hasTable($this->table)) {
            $this->error("This table {$this->table} not exists!");
            return true;
        }

        if ($this->type == 'model') {
            if ($this->alreadyExists($this->model)) {
                $this->error("This model $this->model already exists!");
                return true;
            }
        } else {
            if (! $this->alreadyExists($this->model)) {
                $this->error("This model $this->model not exists!");
                return true;
            }
        }

        return false;
    }
}
