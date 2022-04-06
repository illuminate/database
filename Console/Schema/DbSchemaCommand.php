<?php

namespace Illuminate\Database\Console\Schema;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DbSchemaCommand extends Command
{
    protected $signature = 'db:schema {table_name : The table whose column names and type should be listed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get all the column names and types for a table';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tableName = $this->argument('table_name');

        $this->info('Column names and types for the table: ' . $tableName);

        $schemaBuilder = DB::getSchemaBuilder();
        $data = [];

        foreach ($schemaBuilder->getColumnListing($tableName) as $columnName) {
            $data[] = [
                'name' => $columnName,
                'type' => $schemaBuilder->getColumnType($tableName, $columnName),
            ];
        }

        $headers = ['Column Name', 'Type'];
        $this->table($headers, $data);
    }
}
