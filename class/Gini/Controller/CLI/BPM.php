<?php

namespace Gini\Controller\CLI;

class BPM extends \Gini\Controller\CLI {

    private function getOpt(array $args) {
        $opt = [];
        foreach ($args as $kv) {
            list($k, $v)=array_map('trim', explode('=', $kv));
            if (!$v) {
                $opt['_'][] = $k;
            } else {
                $opt[$k] = $v;
            }
        }
        return $opt;
    }

    private function getEngine(array $opt) {
        $opt['bpm'] or die("You need to specify BPM name!");
        return \Gini\BPM\Engine::of($opt['bpm']);
    }

    // e.g.: gini bpm deploy bpm=camunda name=test test.bpmn
    public function actionDeploy($args) {
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

    // e.g.: gini bpm start bpm=camunda process=test
    public function actionStart($args) {
        $opt = $this->getOpt($args);
        $engine = $this->getEngine($opt);
        
        $opt['process'] or die("You need to specify Process name!");
        $data = reset($opt['_']);

        $process = $engine->process($opt['process']);
        $instance = $process->start((array)json_decode($data));
        if ($instance->id) {
            echo "Started: id={$instance->id}.\n";
        } else {
            echo "Failed.\n";
        }
    }

    // e.g.: gini bpm tasks bpm=camunda process=testProcess assignee=jia.huang
    public function actionTasks($args) {
        $opt = $this->getOpt($args);
        $engine = $this->getEngine($opt);

        $criteria = [];
        if (isset($opt['process'])) {
            $criteria['processDefinitionKey'] = $opt['process'];
        }
        if (isset($opt['assignee'])) {
            $criteria['assignee'] = $opt['assignee'];
        }

        $o = $engine->searchTasks($criteria);
        $tasks = $engine->getTasks($o->token);
        foreach ($tasks as $task) {
            echo yaml_emit($task->getData(), YAML_UTF8_ENCODING);
        }
    }

}