<?php

namespace Gini\BPM\Camunda720;

/**
 * yue.cui 创建于 2024/2/21
 */
class Decision extends \Gini\BPM\Camunda\Decision
{
    private $camunda;
    public function evaluate(array $vars, $engine='default') {
        $key = $this->id;
        $cVars = Engine::convertVariables($vars);
        return $this->camunda->post("engine/$engine/decision-definition/key/$key/evaluate", [
            'variables' => $cVars,
        ]);
    }
}