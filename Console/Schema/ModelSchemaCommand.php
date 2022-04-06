<?php

namespace Illuminate\Database\Console\Schema;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class ModelSchemaCommand extends Command
{
    protected $signature = 'model:schema {model_class_name : The Model class name whose column names and type should be listed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get all the column names and types for a model';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $modelClassName = $this->argument('model_class_name');

        if (!Str::startsWith($modelClassName, '\\')) {
            $modelClassName = '\\' . $modelClassName;
        }

        $tableName = with(new $modelClassName)->getTable();

        return Artisan::call('db:schema', ['table_name' => $tableName], $this->getOutput());
    }
}
