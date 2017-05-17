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

    private function _doUpdate($data, $description)
    {
        $task = $this->rdata;
        $customizedMethod = ['\\Gini\\Process\\Engine\\SJTU\\Task', 'doUpdate'];
        if (method_exists('\\Gini\\Process\\Engine\\SJTU\\Task', 'doUpdate')) {
            $bool = call_user_func($customizedMethod, $task, $description);
        }

        if (!$bool) return;
        return $task->update($data);
    }

    public function approve($process, $message=null, $user=null)
    {
        $id = $this->id;
        $now = date('Y-m-d H:i:s');
        $user = $user ?: _G('ME');

        if (!$id || !$user->id) return ;

        $steps = $process->rdata->rules;
        $steps_keys = array_keys($steps);
        $assignee = $this->data['assignee'];
        $step_now = explode('-', $assignee);

        foreach ($steps as $step => $opts) {
            if (in_array($step, $step_now)) {
                $opt = $step.'_'.$opts['opt'];
                $key = array_search($step, $steps_keys);
                $key++;
                $next_step_key = $steps_keys[$key];
                $next_step_opt = $steps[$step]['approved'];
                $callback = $opts['callback'];
                break;
            }
        }

        if (!$callback) return ;

        $params[$opt] = true;
        $search_params['active'] = true;
        $search_params['instance'] = $this->processInstanceId;

        if ($next_step_key && $next_step_opt){
            $params[$next_step_opt] = $next_step_key;
        } else {
            $isComplete =true ;
        }

        //TODO 这里似乎逻辑不太合理，想不到更好的
        $bool = $this->complete($params);
        if ($bool) {
            $task = $this->rdata;
            $upData = [
                'status'=> \Gini\ORM\SJTU\BPM\Process\Task::STATUS_APPROVED,
                'message'=> $message,
                'date'=> $now,
                'group'=> $this->getCandidateGroupTitle($task),
                'user'=> $user->name
            ];
            $description = [
                'a' => T('**:group** **:name** **审核通过**', [
                    ':group'=> $this->getCandidateGroupTitle($task),
                    ':name' => $user->name
                ]),
                't' => $now,
                'u' => $user->id,
                'd' => $message,
            ];

            $ret = $this->_doUpdate($upData, $description);
            if ($ret) {
                if ($isComplete) {
                    $customizedMethod = [$callback, 'pass'];
                    if (method_exists($callback, 'pass')) {
                        $bool = call_user_func($customizedMethod, $task);
                        return $bool;
                    }
                    return ;
                }

                $o = $this->camunda->searchTasks($search_params);
                $tasks = $this->camunda->getTasks($o->token);
                if (count($tasks)) {
                    $process_instance = $task->instance;
                    $result = $this->camunda->createTask($tasks, $process, $process_instance);
                    return $result;
                }
                return true;
            }
        }

        return false;
    }

    public function reject($process, $message=null, $user=null)
    {
        $id = $this->id;
        $now = date('Y-m-d H:i:s');
        $user = $user ?: _G('ME');

        if (!$id || !$user->id) return ;

        $steps = $process->rdata->rules;
        $steps_keys = array_keys($steps);
        $assignee = $this->data['assignee'];
        $step_now = explode('-', $assignee);

        foreach ($steps as $step => $opts) {
            if (in_array($step, $step_now)) {
                $opt = $step.'_'.$opts['opt'];
                $callback = $opts['callback'];
                break;
            }
        }
        $params[$opt] = false;
        $bool = $this->complete($params);

        if ($bool) {
            $task = $this->rdata;
            $upData = [
                'status'=> \Gini\ORM\SJTU\BPM\Process\Task::STATUS_UNAPPROVED,
                'message'=> $message,
                'date'=> $now,
                'group'=> $this->getCandidateGroupTitle($task),
                'user'=> $user->name
            ];
            $description = [
                'a' => T('**:group** **:name** **拒绝**', [
                    ':group'=> $this->getCandidateGroupTitle($task),
                    ':name' => $user->name
                ]),
                't' => $now,
                'u' => $user->id,
                'd' => $message,
            ];
            $ret = $this->_doUpdate($upData, $description);
            if ($ret) {
                $customizedMethod = [$callback, 'reject'];
                if (method_exists($callback, 'reject')) {
                    $bool = call_user_func($customizedMethod, $task);
                    return $bool;
                }
            }
        }
        return false;
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
