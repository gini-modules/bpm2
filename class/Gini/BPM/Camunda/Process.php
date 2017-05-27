<?php

namespace Gini\BPM\Camunda;

class Process implements \Gini\BPM\Driver\Process {

    private $camunda;
    public function __construct($camunda, $id) {
        $this->camunda = $camunda;
        $this->id = $id;
    }

    public function start(array $vars) {
        $cvars = Engine::convertVariables($vars);
        $key = $this->id;
        $rdata = $this->camunda->post("process-definition/key/$key/start", [
            'variables' => $cvars,
            'businessKey' => $key . '_'.uniqid(),
        ]);
        return new ProcessInstance($this->camunda, $rdata['id'], $rdata);
    }
}
