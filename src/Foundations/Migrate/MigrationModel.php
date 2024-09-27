<?php
namespace Ds\Foundations\Migrate;

use Ds\Foundations\Connection\Models\DsModel;

class MigrationModel extends DsModel
{
    public $table = 'migrations';
    public $fillable = [
        'filename', 'raw', 'batch',
    ];

    public function getBatch($filename)
    {
        return $this->select('batch', $this->table)
            ->where('filename', $filename)
            ->count() + 1;
    }
    public function tableExist()
    {
        $tables = $this->query('SHOW TABLES')->get_object();
        foreach ($tables as $key => $value) {
            $tableName = array_values((array) $value)[0];
            if ($tableName == $this->table) {
                return true;
            }
        }
        return false;
    }
}
