<?php

namespace Gini\BPM\Camunda;

class ProcessInstance implements \Gini\BPM\Interface\ProcessInstance {

    private $camunda;
    private $id;
    private $data;

    public function __construct($camunda, $id) {
        $this->camunda = $camunda;
        $this->id = $id;
    }

    private function _fetchInstance() {
        if (!$this->data) {
            $id = $this->id;
            try {
                $this->data = $this->camunda->get("process-instance/$id");
            } catch (\Gini\BPM\Exception $e) {
                $this->data = [];
            }
        }
    }

    public function exists() {
        $this->_fetchInstance();
        return isset($this->data['id']);
    }

    
}