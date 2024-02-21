<?php

namespace Gini\BPM\Camunda720\Tests;

/**
 * yue.cui 创建于 2024/2/6
 */
class TestCamundaBase
{
    protected $config = [];
    static $services = [
        'engine' => \Gini\BPM\Camunda720\Engine::class,
        'decision-definition' => \Gini\BPM\Camunda720\Decision::class,
        'execution'=>\Gini\BPM\Camunda\Execution::class,
        'group' => \Gini\BPM\Camunda\Group::class,
        'process' => \Gini\BPM\Camunda\Process::class,
        'process-instance' => \Gini\BPM\Camunda\ProcessInstance::class,
        'task' => \Gini\BPM\Camunda\Task::class,
        'user' => \Gini\BPM\Camunda\User::class
    ];

    public function getEngineImpl(){
        return $this->getImpl('engine', $this->config);
    }

    public function getList($type)
    {
        // 广义的 process 分为 process-definition 和 process-instance
        // 如果只传 process 那么默认指的是 process-definition
        if($type == 'process') $type = 'process-definition';
        return $this->getEngineImpl()->get($type);
    }

    public function getImpl($type, $val=""){
        return $this->ioc($type, $val);
    }


    /**
     * 这里的 $val 是构造函数的参数。因为没有多个参数的需求所以没有搞的很复杂
     * @param $type
     * @param $val
     * @return object|null
     * @throws \ReflectionException
     */
    protected function ioc($type, $val=""){
        // 广义的 process 分为 process-definition 和 process-instance
        // 这里 process 那么默认指的是 process-definition
        if($type == 'process') $type = 'process-definition';
        $reflectionClass = new \ReflectionClass($this->getEngineImpl());
        if($val) {
            return $reflectionClass->newInstanceArgs($val);
        }else{
            return $reflectionClass->newInstanceWithoutConstructor();
        }
    }

    protected function assert(bool $param)
    {
        if($param){
            error_log("test succeed");
        }else{
            throw new \AssertionError("test failed");
        }
    }

    protected function testStart(string $__FUNCTION__)
    {
        error_log($__FUNCTION__." start");
    }
}