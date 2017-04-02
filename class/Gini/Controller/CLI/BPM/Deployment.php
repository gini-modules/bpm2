<?php

namespace Gini\Controller\CLI\BPM;

class Deployment extends \Gini\Controller\CLI {

    use \Gini\Controller\CLI\BPM\Base;

    // e.g.: gini bpm deployment create bpm=camunda name=test test.bpmn
    public function actionCreate($args) {
        $opt = $this->getOpt($args);
        $engine = $this->getEngine($opt);

        $files = [];
        foreach ((array)$opt['_'] as $file) {
            if (file_exists($file)) {
                $files[] = $file;
            }
        }

        count($files) > 0 or die("You need to specify files!");

        $opt['name'] = $opt['name'] ?: date('YmdHis');
        $response = $engine->deploy($opt['name'], $files);
        echo yaml_emit($response);
    }

}