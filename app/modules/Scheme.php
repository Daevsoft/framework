<?php
class Scheme
{
    private $model;
    private $columns;
    function __construct($tableName)
    {
        $model = new dsModel();
        $indexName = config('driver') == 'mysql' ? 'Field' : 0;
        $listColumns = $model->get_column($tableName);
        foreach ($listColumns as $value) {
            $columnName = $value[$indexName];
            $this->{$columnName} = STRING_EMPTY;
            $this->columns[] = $columnName;
        }
    }
    public function inflate($arrayData)
    {
        if($arrayData){
            foreach ($this->columns as $column) {
                $this->{$column} = $arrayData[$column];
            }
        }
    }
}
