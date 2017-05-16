<?php

namespace Gini\Controller\CLI\BPM;

class Process extends \Gini\Controller\CLI {

    use \Gini\Controller\CLI\BPM\Base;

    // e.g.: gini bpm process start bpm=camunda key=test
    public function actionStart($args) {
        $opt = $this->getOpt($args);
        $engine = $this->getEngine($opt);

        $opt['key'] or die("You need to specify Process key!");
        $data = array_diff_key($opt, ['_'=>1, 'bpm'=>1, 'key'=>1]);
        $data['school'] = (int) 42;
        $process = $engine->process($opt['key']);
        $instance = $process->start($data);
        if ($instance->id) {
            echo "Started: id={$instance->id}.\n";
        } else {
            echo "Failed.\n";
        }
    }

    public function actionGet($args)
    {
        $opt = $this->getOpt($args);
        $engine = $this->getEngine($opt);
        $process = $engine->process($opt['key']);
        $instance = $process->get($opt['key']);
        var_dump($instance);
    }
}
