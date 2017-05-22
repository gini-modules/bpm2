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
        $this->_fetchTask();
        if ($name == 'id') {
            return $this->id;
        }

        return $this->data[$name];
    }

    public function getData() {
        $this->_fetchTask();
        return $this->data;
    }

    public function setAssignee($userId) {
        if (!$this->id) return false;
        try {
            $this->camunda->post("task/{$this->id}/assignee", [
                 'userId' => $userId,
            ]);
            unset($this->data);
            return true;
        } catch (\Gini\BPM\Exception $e) {
            return false;
        }
    }

    public function claim($userId) {
        if (!$this->id) return false;
        try {
            $this->camunda->post("task/{$this->id}/claim", [
                'userId' => $userId,
            ]);
            unset($this->data);
            return true;
        } catch (\Gini\BPM\Exception $e) {
            return false;
        }
    }

    public function unclaim() {
        if (!$this->id) return false;
        try {
            $this->camunda->post("task/{$this->id}/unclaim");
            unset($this->data);
            return true;
        } catch (\Gini\BPM\Exception $e) {
            return false;
        }
    }

    public function submitForm(array $vars) {

    }

    public function complete(array $vars=[]) {
        if (!$this->id) return false;

        $cvars = Engine::convertVariables($vars);
        $id = $this->id;
        try {
            $this->camunda->post("task/$id/complete", [
                 'variables' => $cvars,
            ]);
            unset($this->data); // 让系统能重新抓取数据
            return true;
        } catch (\Gini\BPM\Exception $e) {
            return false;
        }
    }

    //Creates a comment for a task by id.
    public function addComment($message) {
        $id = $this->id;
        if (!$id || !$message) return ;

        $query['message'] = $message;
        try {
            $rdata = $this->camunda->post("task/$id/comment/create", $query);
            return empty($rdata) ? true : $rdata;
        } catch (\Gini\BPM\Exception $e) {
            return ;
        }
    }

    //Gets the comments for a task by id.
    public function getComments() {
        $id = $this->id;
        if (!$id) return ;

        try {
            $rdata = $this->camunda->get("task/$id/comment");
            return empty($rdata) ? true : $rdata;
        } catch (\Gini\BPM\Exception $e) {
            return ;
        }
    }
}
