<?php

namespace Gini\BPM\Camunda720\Tests;

use Gini\BPM\Camunda720\Tests\TestTrait\ProcessTrait;
use Gini\BPM\Camunda720\Tests\TestTrait\UserTrait;

/**
 * Gini\BPM\Driver\Engine 接口的公开方法进行了测试，目前的测试用例并不全
 * yue.cui 创建于 2024/2/6
 */
class TestEngine extends TestCamundaBase
{
    use UserTrait, ProcessTrait;

    public function setConfig($config)
    {
        $this->config = $config;
    }
    public function testCanInit()
    {
        $this->testStart(__FUNCTION__);
        $obj = $this->getEngineImpl();
        $this->assert(get_class($obj) == \Gini\BPM\Camunda\Engine::class);
    }

    public function testCanGet(){
        $this->testStart(__FUNCTION__);
        $user = $this->getEngineImpl()->get('user/test/profile');
        if(!$user['id']){
            error_log('user not found');
        }
        $this->assert($user['id'] && $user['id'] == 'test');
    }
    public function testCanPut(){
        $this->testStart(__FUNCTION__);
        $this->updateUser($this->getEngineImpl(),'test',["id"=> "test","email"=> "test@test.com"]);
        $userNext = $this->getUser($this->getEngineImpl(),'cuiyue');
        $this->assert($userNext['email'] == 'test@test.com');
    }
    public function testCanPost()
    {
        $this->testStart(__FUNCTION__);
        $this->createUser($this->getEngineImpl(),[
            "profile" => [
                "id" => "test",
                "firstName" => "test",
                "lastName" => "test",
                "email" => "test@test.cn"
            ],
            "credentials" => [
                "password" => "12345678"
            ]
        ]);
        $userNext = $this->getUser($this->getEngineImpl(),'test');
        $this->assert($userNext['id'] == 'test');
    }
    public function testCanDelete()
    {
        $this->testStart(__FUNCTION__);
        // 这里失败会抛出异常，没有异常则为成功
        $this->deleteUser($this->getEngineImpl(),'test');
        $this->assert(true);
    }
    public function testCanDeploy(){
        $this->testStart(__FUNCTION__);
        // 这里需要注意 测试流程图的 id 应该为 test-process-definition
        $this->getEngineImpl()->deploy(date('YmdHis'), ['./test-process-definition.bpmn']);
        $this->assert(true);
    }

    public function testCanProcess(){
        $this->testStart(__FUNCTION__);
        $key = "test-process-definition";
        $process = $this->getEngineImpl()->process($key);
        // 这里需要根据测试用的流程图和实际的回调地址修改参数
        $process->start([
            'domainpath'=> 'http://test.test.cn/',
            'secret' => 'secret'
        ]);
        // 如果 start 失败会返回异常并中断脚本执行， 能执行到这里说明没问题
        $this->assert(true);
    }

    public function testCanProcessInstance(){
        $this->testStart(__FUNCTION__);
        $id = "test-process-definition";
        $process = $this->getEngineImpl()->process($id);
        $this->assert($process && $process->id == $id);
    }

    public function testCanSearchProcessInstances(){
        $this->testStart(__FUNCTION__);
        $key = "test-process-definition";
        $process = $this->getEngineImpl()->searchProcessInstances(['process' =>$key]);
        $this->assert($process && $process->token);
    }
}