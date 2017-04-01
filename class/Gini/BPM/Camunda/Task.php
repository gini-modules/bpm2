<?php

namespace Gini\BPM\Camunda;

class Task implements \Gini\BPM\Driver\Task {

    private $camunda;
    private $id;
    private $data;

    public function __construct($camunda, $id, $data=null) {
        $this->camunda = $camunda;
        $this->id = $id;
        if ($data) {
            $this->data = (array) $data;
        }
    }

    private function _fetchTask() {
        if (!$this->data) {
            $id = $this->id;
            try {
                $this->data = $this->camunda->get("task/$id");
            } catch (\Gini\BPM\Exception $e) {
                $this->data = [];
            }
        }
    }

    public function __get($name) {
        if ($name == 'id') {
            return $this->id;
        }
        
        return $this->data[$name];
    }

    public function getData() {
        return $this->data;
    }

    public function setAssignee($userId) {
        
    }

    public function submitForm(array $vars) {
        
    }

    public function complete() {
    }
}