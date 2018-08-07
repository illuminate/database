<?php

namespace Illuminate\Database\Query\Processors;

class MySqlProcessor extends Processor
{
    /**
     * Process the results of a column listing query.
     *
     * @param  array  $results
     * @return array
     */
    public function processColumnListing($results)
    {
        return array_map(function ($result) {
            $obj = (object) $result;
            
            return isset($obj->column_name) ? $obj->column_name : $obj->COLUMN_NAME;
        }, $results);
    }
}
