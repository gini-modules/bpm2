<?php

namespace Gini\BPM\Camunda;

class Decision implements \Gini\BPM\Interface\Decision {

    public function __construct($camunda, $id) {
        $this->camunda = $camunda;
        $this->id = $id;
    }

    public function evaluate(array $vars) {
        $key = $this->id;
        $cvars = Engine::convertVariables($vars);
        return $camunda->call("engine/engine/$engine/decision-definition/key/$key/evaluate", [
            'variables' => $cvars,
        ]);
    }
}