<?php

namespace Gini\Controller\CLI;

class BPM extends \Gini\Controller\CLI {

    // e.g.: gini bpm deploy camunda test.bpmn --name=test
    public function actionDeploy($args) {
        $opt = \Gini\Util::getOpt($args, 'n:', ['name:']);
        list($bpmName, $file) = $opt['_'];
        $name = $opt['n']?:($opt['name']?:basename($file));
        $engine = \Gini\BPM\Engine::of($bpmName);
        $engine->deploy($name, [$file]);
    }

}