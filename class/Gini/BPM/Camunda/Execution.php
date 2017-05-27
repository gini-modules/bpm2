<?php

namespace Gini\BPM\Camunda;

class Execution implements \Gini\BPM\Driver\Execution
{
    private $camunda;
    function __construct($camunda, $id) {
        $this->camunda = $camunda;
        $this->id = $id;
        $this->_fetchData();
    }

    private function _fetchData() {
        $id = $this->id;
        unset($this->id);
        $rdata = $this->camunda->get("execution/$id");
        if (isset($rdata['id'])) {
            foreach ($rdata as $key => $value) {
                $this->$key = $value;
            }
        }
    }
}
