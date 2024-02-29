<?php

namespace Gini\BPM\Camunda720;

/**
 * yue.cui 创建于 2024/2/25
 */
class Task extends \Gini\BPM\Camunda\Task
{
    private $camunda;
    private $id;
    private $data;

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
}