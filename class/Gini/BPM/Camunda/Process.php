<?php

namespace Gini\BPM\Camunda;

class Process implements \Gini\BPM\Interface\Process {

    private $camunda;
    private $id;

    public function __construct($camunda, $id) {
        $this->camunda = $camunda;
        $this->id = $id;
    }

    public function start(array $vars) {
        $cvars = Engine::convertVariables($vars);
        $key = $this->id;
        $rdata = $camunda->call("engine/engine/$engine/process-definition/key/$key/start", [
            'variables' => $cvars,
            'businessKey' => $key . '_'.uniqid(),
        ]);
        return ProcessInstance($rdata['id']);
    }
}