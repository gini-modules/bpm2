<?php

namespace Gini\BPM\Camunda720;

/**
 * yue.cui 创建于 2024/2/25
 */
class Group extends \Gini\BPM\Camunda\Group
{
    private $camunda;
    private function _fetchData() {
        $id = $this->id;
        unset($this->id);
        $rdata = $this->camunda->get("group/$id");
        if (isset($rdata['id'])) {
            foreach ($rdata as $key => $value) {
                $this->$key = $value;
            }
        }
    }
}