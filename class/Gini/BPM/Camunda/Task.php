<?php

namespace Gini\BPM\Camunda;

class Task implements \Gini\BPM\Driver\Task {

    private $camunda;
    private $id;
    private $data;
    private $rdata;

    public function __construct($camunda, $id, $data=null) {
        $this->camunda = $camunda;
        $this->id = $id;
        $this->rdata = $this->_fetchRdata();
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

    private function _fetchRdata() {
        return a('sjtu/bpm/process/task', ['key' => $this->id]);
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

    public function getCandidateGroupTitle($task = null)
    {
        if (!$task->id) return ;
        $id = $task->candidate_group;
        try {
            $rdata = $this->camunda->get("group/$id");
            return $rdata['name'];
        } catch (\Gini\BPM\Exception $e) {
            return ;
        }
    }

    private function _doUpdate(array $vars)
    {
        $now = date('Y-m-d H:i:s');
        $user = $vars['user'];
        $message = $vars['message'];
        $status = $vars['status'];
        $opt = $vars['opt'];
        $rtask = $this->rdata;
        $candidate_group_title = $this->getCandidateGroupTitle($rtask);

        $upData = [
            'status'=> $status,
            'message'=> $message,
            'date'=> $now,
            'group'=> $candidate_group_title,
            'user'=> $user->name
        ];

        $description = [
            'a' => T('**:group** **:name** **:opt**', [
                ':group'=> $candidate_group_title,
                ':name' => $user->name,
                ':opt' => $opt
            ]),
            't' => $now,
            'u' => $user->id,
            'd' => $message,
        ];

        $customizedMethod = ['\\Gini\\Process\\Engine\\SJTU\\Task', 'doUpdate'];
        if (method_exists('\\Gini\\Process\\Engine\\SJTU\\Task', 'doUpdate')) {
            $bool = call_user_func($customizedMethod, $rtask, $description);
        }

        if (!$bool) return;
        return $rtask->update($upData);
    }

    private function _doComplete($process, $opt = false)
    {
        $rules = $process->rdata->rules;
        $steps = $rules['steps'];
        $option = $rules['option'];
        $callback = $rules['callback'];
        $assignee = $this->data['assignee'];
        $step_now = explode('-', $assignee);

        if (!count($steps)) return ;

        foreach ($steps as $step) {
            if (in_array($step, $step_now)) {
                $full_option = $step.'_'.$option;
                break;
            }
        }

        $params[$full_option] = $opt ? true : false;
        return $this->complete($params);
    }

    public function approve($process, $message=null, $user=null)
    {
        $user = $user ?: _G('ME');
        $rules = $process->rdata->rules;
        $callback = $rules['callback'];

        if (!$this->id || !$user->id || !$callback) return ;

        $search_params['active'] = true;
        $search_params['instance'] = $this->processInstanceId;

        $bool = $this->_doComplete($process, true);
        if ($bool) {

            $rtask = $this->rdata;
            $params['status'] = \Gini\ORM\SJTU\BPM\Process\Task::STATUS_APPROVED;
            $params['user'] = $user;
            $params['message'] = $message;
            $params['opt'] = T('审核通过');

            $ret = $this->_doUpdate($params);
            if ($ret) {
                $o = $this->camunda->searchTasks($search_params);
                $tasks = $this->camunda->getTasks($o->token);

                if ($o->total) {
                    $process_instance = $rtask->instance;
                    $result = $this->camunda->createTask($tasks, $process, $process_instance);
                    return $result;
                }

                $customizedMethod = [$callback, 'pass'];
                if (method_exists($callback, 'pass')) {
                    return call_user_func($customizedMethod, $rtask);
                }
            }
        }
        return ;
    }

    public function reject($process, $message=null, $user=null)
    {
        $user = $user ?: _G('ME');
        $rules = $process->rdata->rules;
        $callback = $rules['callback'];

        if (!$this->id || !$user->id || !$callback) return ;

        $bool = $this->_doComplete($process);
        if ($bool) {
            $rtask = $this->rdata;
            $params['status'] = \Gini\ORM\SJTU\BPM\Process\Task::STATUS_UNAPPROVED;
            $params['user'] = $user;
            $params['message'] = $message;
            $params['opt'] = T('拒绝');

            $ret = $this->_doUpdate($params);
            if ($ret) {
                $customizedMethod = [$callback, 'reject'];
                if (method_exists($callback, 'reject')) {
                    $bool = call_user_func($customizedMethod, $rtask);
                    return $bool;
                }
            }
        }

        return ;
    }

    public function complete(array $vars=[]) {
        if (!$this->id) return false;

        $cvars = Engine::convertVariables($vars);

        $id = $this->id;
        try {
            $result = $this->camunda->post("task/$id/complete", [
                 'variables' => $cvars,
            ]);

            if (!$result) {
                throw new \Gini\Process\Engine\Exception();
            }

            unset($this->data); // 让系统能重新抓取数据
            return true;
        } catch (\Gini\BPM\Exception $e) {
            return false;
        }
    }
}
