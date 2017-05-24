<?php

namespace Gini\BPM\Camunda;

class Execution implements \Gini\BPM\Driver\Execution
{
    private $camunda;
    private $id;
    private $data;
    function __construct($camunda, $id, $data = null) {
        $this->camunda = $camunda;
        $this->id = $id;
        if ($data) {
            $this->data = (array) $data;
        }
    }

    private function _fetchData() {
        if (!$this->data) {
            $id = $this->id;
            $this->data = $this->camunda->get("execution/$id");
        }
    }

    public function __get($name) {
        if ($name == 'id') {
            return $this->id;
        }

        $this->_fetchData();
        return $this->data[$name];
    }
}
