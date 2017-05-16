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

    public function getCandidateGroupTitle($task = null)
    {
        if (!$task->id) return ;
        $id = $task->candidate_group;
        $rdata = $this->camunda->get("group/$id");
        return $rdata['name'];
    }

    public function update($task, array $data=[])
    {
        foreach ($data as $k=>$v) {
            $task->$k = $v;
        }
        return $task->save();
    }

    private function _doUpdate($user, $task, $message)
    {
        $now = date('Y-m-d H:i:s');
        $user = $user ?: _G('ME');
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

        $customizedMethod = ['\\Gini\\Process\\Engine\\SJTU\\Task', 'doUpdate'];
        if (method_exists('\\Gini\\Process\\Engine\\SJTU\\Task', 'doUpdate')) {
            $bool = call_user_func($customizedMethod, $task, $description);
        }

        if (!$bool) return;
        return $this->update($task, $upData);
    }

    public function approve($comment = '')
    {
        $id = $this->id;
        $me = _G('ME');
        if (!$id || !$me->id) return ;

        if ($comment) {
            $bool = $this->_addComment($id, $comment);
            if (!$bool) return ;
        }

        $steps = \Gini\Config::get('app.order_review_process_steps');
        if (!count($steps)) return ;

        try {
            $task = $this->camunda->getTask($id);
            if (!$task->id) return ;
        } catch (\Gini\BPM\Exception $e) {
            return ;
        }

        $assignee = $task->data['assignee'];
        $step_now = explode('-', $assignee);

        foreach ($steps as $step) {
            if (in_array($step, $step_now)) {
                $opt = $step.'_approved';
                $key = array_search($step, $steps);
                $key++;
                $next_step = $steps[$key];
                break;
            }
        }

        $params[$opt] = true;
        $params['delegationState'] = 'complete';
        $search_params['active'] = true;
        $search_params['instance'] = $task->processInstanceId;

        if ($next_step){
            $params[$next_step.'_approve'] = $next_step;
        } else {
            $isComplete =true ;
        }

        $bool = $this->complete($params);
        if ($bool) {
            $task = a('sjtu/bpm/process/task', ['key' => $id]);
            $ret = $this->_doUpdate($me, $task, $comment);
            if ($ret) {
                $o = $this->camunda->searchTasks($search_params);
                $tasks = $this->camunda->getTasks($o->token);
                if (count($tasks)) {
                    $processName = \Gini\Config::Get('app.order_review_process');
                    $process = a('sjtu/bpm/process', ['name' => $processName]);
                    $process_instance = $task->instance;
                    $this->camunda->createTask($tasks, $process, $process_instance);
                }

                if ($isComplete) {

                }

                return true;
            }
        }

        return false;
    }

    public function reject($comment = '')
    {
        $id = $this->id;
        if (!$comment || !$id) return ;

        $bool = $this->_addComment($id, $comment);
        if (!$bool) {
            return ;
        }

        $steps = \Gini\Config::get('app.order_review_process_steps');
        if (!count($steps)) return ;
        try {
            $task = $this->camunda->getTask($id);
            if (!$task->id) {
                return ;
            }
        } catch (\Gini\BPM\Exception $e) {
            return ;
        }

        $assignee = $task->data['assignee'];
        $step_now = explode('-', $assignee);

        foreach ($steps as $step) {
            if (in_array($step, $step_now)) {
                $opt = $step.'_approved';
                break;
            }
        }

        $params[$opt] = false;

        return $this->complete($params);
    }

    private function _addComment($task_id = '', $comment = '')
    {
        if (!$task_id || !$comment) return ;

        $query['message'] = $comment;
        try {
            return $this->camunda->post("task/$task_id/comment/create", $query);
        } catch (\Gini\BPM\Exception $e) {
            return ;
        }
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
