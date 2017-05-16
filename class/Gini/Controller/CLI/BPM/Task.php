<?php

namespace Gini\Controller\CLI\BPM;

class Task extends \Gini\Controller\CLI {

    use \Gini\Controller\CLI\BPM\Base;

    // e.g.: gini bpm task search bpm=camunda process=testProcess assignee=jia.huang
    public function actionSearch($args) {
        $opt = $this->getOpt($args);
        $engine = $this->getEngine($opt);

        $criteria = array_diff_key($opt, ['_'=>1, 'bpm'=>1]);

        $o = $engine->searchTasks($criteria);
        $tasks = $engine->getTasks($o->token);
        foreach ($tasks as $task) {
            echo yaml_emit($task->getData(), YAML_UTF8_ENCODING);
        }
    }

    // e.g.: gini bpm task show bpm=camunda id=ec62ef49-16c3-11e7-a73c-0242ac112a08
    public function actionShow($args) {
        $opt = $this->getOpt($args);
        $engine = $this->getEngine($opt);

        $opt['id'] or die("You need to specify a task id!");
        $task = $engine->task($opt['id']);
        echo yaml_emit($task->getData(), YAML_UTF8_ENCODING);
    }

    // e.g.: gini bpm task complete bpm=camunda id=ec62ef49-16c3-11e7-a73c-0242ac112a08 approved:=false
    public function actionComplete($args) {
        $opt = $this->getOpt($args);
        $engine = $this->getEngine($opt);

        $opt['id'] or die("You need to specify a task id!");

        $task = $engine->task($opt['id']);
        $vars = array_diff_key($opt, ['_'=>1, 'bpm'=>1, 'id'=>1]);
        $success = $task->complete($vars);
        if ($success) {
            echo "Completed\n";
        } else {
            echo "Failed to complete\n";
        }
    }

    // e.g.: gini bpm task assign bpm=camunda id=ec62ef49-16c3-11e7-a73c-0242ac112a08 to=user
    public function actionAssign($args) {
        $opt = $this->getOpt($args);
        $engine = $this->getEngine($opt);

        $opt['id'] or die("You need to specify a task id!");

        $task = $engine->task($opt['id']);
        $vars = array_diff_key($opt, ['_'=>1, 'bpm'=>1, 'id'=>1]);
        $success = $task->setAssignee($vars['to']);
        if ($success) {
            echo "Assigned to {$vars['to']}\n";
        } else {
            echo "Failed to assign the task to {$vars['to']}\n";
        }
    }

}
