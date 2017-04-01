<?php

namespace Gini\BPM\Camunda;

class Decision implements \Gini\BPM\Driver\Decision {

    public function __construct($camunda, $id) {
        $this->camunda = $camunda;
        $this->id = $id;
    }

    public function evaluate(array $vars) {
        $key = $this->id;
        $cvars = Engine::convertVariables($vars);
        return $this->camunda->post("engine/engine/$engine/decision-definition/key/$key/evaluate", [
            'variables' => $cvars,
        ]);
    }
}