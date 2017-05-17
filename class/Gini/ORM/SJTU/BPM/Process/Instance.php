<?php

namespace Gini\ORM\SJTU\BPM\Process;

class Instance extends \Gini\ORM\Object
{
    public $process = 'object:sjtu/bpm/process';
    public $data = 'array';
    public $status = 'int,default:0';
    public $last_run_time = 'datetime';
    public $tag = 'string:30';
    public $process_name = 'string';
    public $key = 'string';

    const STATUS_END = '-1';

    protected static $db_index = [
        'unique:tag',
        'unique:key',
        'last_run_time',
    ];

    public function getVariable($key)
    {
        $data = (array)$this->data;
        return $data[$key];
    }

    public function update(array $data=[])
    {
        foreach ($data as $k=>$v) {
            $this->$k = $v;
        }
        return $this->save();
    }
}
