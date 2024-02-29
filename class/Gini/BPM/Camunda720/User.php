<?php

namespace Gini\BPM\Camunda720;

/**
 * yue.cui 创建于 2024/2/25
 */
class User extends \Gini\BPM\Camunda\User
{
    private $camunda;
    private function _fetchData() {
        $id = $this->id;
        unset($this->id);
        $rdata = $this->camunda->get("user/$id/profile");
        if (isset($rdata['id'])) {
            foreach ($rdata as $key => $value) {
                $this->$key = $value;
            }
        }
    }
}