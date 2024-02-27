<?php

namespace Gini\BPM\Camunda720;

use Gini\BPM\Camunda\Decision as Decision77;

/**
 * yue.cui 创建于 2024/2/21
 */
class Decision extends Decision77
{
    public function evaluate(array $vars, $engine='default') {
        $key = $this->id;
        $cvars = Engine::convertVariables($vars);
        return $this->camunda->post("engine/$engine/decision-definition/key/$key/evaluate", [
            'variables' => $cvars,
        ]);
    }
}