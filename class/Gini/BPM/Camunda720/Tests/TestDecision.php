<?php

namespace Gini\BPM\Camunda720\Tests;

/**
 * yue.cui 创建于 2024/2/6
 */
class TestDecision extends TestCamundaBase
{
    public function testCanInit(){
        $obj = $this->getImpl('decision');
        $this->assert(get_class($obj) == \Gini\BPM\Camunda\Decision::class);
    }
}