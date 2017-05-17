<?php

namespace Gini\ORM\SJTU\BPM\Process;

// 任务节点
class Task extends \Gini\ORM\Object
{
    public $process = 'object:sjtu/bpm/process';
    public $instance = 'object:sjtu/bpm/process/instance';
    public $candidate_group = 'string:50';
    public $position = 'string:50';
    public $ctime = 'datetime';
    public $status = 'int';
    public $key = 'string';
    // auto task的开始执行时间
    public $run_date = 'datetime';

    protected static $db_index = [
        'unique:key'
    ];

    const STATUS_PENDING = 0;
    const STATUS_RUNNING = 1;
    const STATUS_APPROVED = 2;
    const STATUS_UNAPPROVED = 3;

    public function isEnd()
    {
        return in_array($this->status, [
            self::STATUS_APPROVED,
            self::STATUS_UNAPPROVED
        ]);
    }

    public function update(array $data=[])
    {
        foreach ($data as $k=>$v) {
            $this->$k = $v;
        }
        return $this->save();
    }
}
