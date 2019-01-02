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

        $process = $engine->process($opt['key']);
        $instance = $process->start($data);
        if ($instance->id) {
            echo "Started: id={$instance->id}.\n";
        } else {
            echo "Failed.\n";
        }
    }

    public function actionDelete($args) {
        $opt = $this->getOpt($args);
        $engine = $this->getEngine($opt);
        
        $opt['id'] or die("You need to specify Process Instance Id!");
        $data = array_diff_key($opt, ['_'=>1, 'bpm'=>1, 'id'=>1]);

        $instance = $engine->processInstance($opt['id']);
        try {
            $instance->delete();
            echo "\e[1;32mDeleted: id={$instance->id}.\e[0m\n";
        } catch (\Gini\BPM\Exception $e) {
            echo "\e[1;31mFailed: " . $e->getMessage() . "\e[0m\n";
        }

    }

}