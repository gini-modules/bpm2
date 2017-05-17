<?php

namespace Gini\BPM\Camunda;

class Engine implements \Gini\BPM\Driver\Engine {

    private $http;
    private $root;
    private $engine;

    private $userId;
    private $authorizedApps;

    // e.g. http://camunda.genee.cn/camunda/api
    public function __construct($config) {
        $this->config = $config;

        $options = & $this->config['options'];
        $options['engine'] = $options['engine'] ?: 'default';

        $this->root = $options['api_root'];
        $this->engine = $options['engine'];

        $this->http = new \Gini\HTTP();
        $this->http->enableCookie()->header('Accept', 'application/json');

        $response = $this->http
            ->header('Content-Type', 'application/x-www-form-urlencoded')
            ->post("{$this->root}/admin/auth/user/default/login/cockpit", [
                'username' => $options['username'],
                'password' => $options['password'],
            ]);
        $rdata = json_decode($response->body, true);
        $this->userId = $rdata['userId'];
        $this->authorizedApps = $data['authorizedApps'];
    }

    public function post($path, array $data=[]) {
        $response = $this->http
            ->header('Content-Type', 'application/json')
            ->post("{$this->root}/engine/engine/{$this->engine}/$path", $data);
        $status = $response->status();
        $data = json_decode($response->body, true);
        if (floor($status->code/100) != 2) {
            throw new \Gini\BPM\Exception($data['message']);
        }

        if (is_array($data)) return $data;
        return true;
    }

    public function get($path, array $data=[]) {
        $response = $this->http
            ->header('Content-Type', '')
            ->get("{$this->root}/engine/engine/{$this->engine}/$path", $data);
        $status = $response->status();
        $data = json_decode($response->body, true);

        if (floor($status->code/100) != 2) {
            throw new \Gini\BPM\Exception($data['message']);
        }

        if (is_array($data)) return $data;
        return true;
    }

    public function delete($path, array $data=[]) {
        $response = $this->http
            ->header('Content-Type', 'application/json')
            ->delete("{$this->root}/engine/engine/{$this->engine}/$path", $data);
        $status = $response->status();
        $data = json_decode($response->body, true);

        if (floor($status->code/100) != 2) {
            throw new \Gini\BPM\Exception($data['message']);
        }

        if (is_array($data)) return $data;
        return true;
    }

    public function put($path, array $data=[]) {
        $response = $this->http
            ->header('Content-Type', 'application/json')
            ->put("{$this->root}/engine/engine/{$this->engine}/$path", $data);
        $status = $response->status();
        $data = json_decode($response->body, true);

        if (floor($status->code/100) != 2) {
            throw new \Gini\BPM\Exception($data['message']);
        }

        if (is_array($data)) return $data;
        return true;
    }

    public static function convertVariables(array $vars) {
        $cvars = [];
        foreach ($vars as $k => $v) {
            if (is_scalar($v)) {
                if (is_null($v)) {
                    continue;
                } elseif (is_bool($v)) {
                    $cvars[$k] = [
                        'value' => !!$v,
                        'type' => 'Boolean'
                    ];
                } elseif (is_int($v)) {
                    $cvars[$k] = [
                        'value' => $v,
                        'type' => 'Integer'
                    ];
                } elseif (is_float($v)) {
                    $cvars[$k] = [
                        'value' => $v,
                        'type' => 'Double'
                    ];
                } else {
                    $cvars[$k] = [
                        'value' => strval($v),
                        'type' => 'String'
                    ];
                }
            } else {
                $cvars[$k] = [
                    'value' => J($v),
                    'type' => 'Json'
                ];
            }
        }
        return (object) $cvars;
    }

    public function deploy($name, $files) {
        $root = $this->config['options']['api_root'];
        $engine = $this->config['options']['engine'];

        $data = [];
        foreach ($files as $file) {
            if (!file_exists($file)) continue;
            $data[basename($file)] = new \CURLFile($file);
        }

        $data['deployment-name'] = $name;
        $data['enable-duplicate-filtering'] = 'true';
        $data['deploy-changed-only'] = 'true';

        $response = $this->http
            ->header('Content-Type', 'multipart/form-data')
            ->post("$root/engine/engine/$engine/deployment/create", $data);
        $rdata = json_decode($response->body, true);
        return $rdata;
    }

    public function fetchProcessInstance($processName, $instancID)
    {
        $process = $this->process($processName);
        if (!$process->id) return false;

        $instance = a('sjtu/bpm/process/instance', (int)$instancID);
        return $instance->id ? $instance : false;
    }

    public function startProcessInstance($processName, $data, $tag)
    {
        $process = $this->process($processName);
        try {
            if (!$process->id) {
                throw new \Gini\BPM\Exception();
            }

            $process_instance = a('sjtu/bpm/process/instance', ['tag'=> $tag]);
            if (!$process_instance->id) {
                $data['tag'] = $tag;
                $instance = $process->start($data);

                if (!$instance->id) {
                    throw new \Gini\BPM\Exception();
                }

                $process_instance->process = $process->rdata;
                $process_instance->data = $data;
                $process_instance->tag = $tag;
                $process_instance->key = $instance->id;

                if (!$process_instance->save()) {
                    throw new \Gini\BPM\Exception();
                }

                $params['active'] = true;
                $params['instance'] = $instance->id;
                $o = $this->searchTasks($params);
                $tasks = $this->getTasks($o->token);
                $bool = $this->createTask($tasks, $process, $process_instance);
                if (!$bool) {
                    throw new \Gini\BPM\Exception();
                }
            }

            return $process_instance;
        } catch (\Gini\BPM\Exception $e) {
            return ;
        }
    }

    public function createTask($tasks = [], $process, $process_instance)
    {
        if (!count($tasks) || !$process->id || !$process_instance->id) {
            return ;
        }

        foreach ($tasks as $data) {
            $task = a('sjtu/bpm/process/task', ['key' => $data->id]);
            if (!$task->id) {
                $task->process = $process->rdata;
                $task->instance = $process_instance;
                $task->candidate_group = $data->assignee;
                $task->position = $data->assignee;
                $task->ctime = $task->run_date = implode(explode('T', $data->created), ' ');
                $task->status = \Gini\ORM\SJTU\BPM\Process\Task::STATUS_PENDING;
                $task->key = $data->id;

                if (!$task->save()) {
                    throw new \Gini\BPM\Exception();
                }
            }
        }

        return true;
    }

    private $_cachedProcesses = [];
    public function process($id) {
        if (!isset($this->_cachedProcesses[$id])) {
            $this->_cachedProcesses[$id] = new Process($this, $id);
        }
        return $this->_cachedProcesses[$id];
    }

    private $_cachedProcessInstances = [];
    public function processInstance($id, $data=null) {
        if (!isset($this->_cachedProcessInstances[$id])) {
            $this->_cachedProcessInstances[$id] = new ProcessInstance($this, $id, $data);
        }
        return $this->_cachedProcessInstances[$id];
    }

    private $_cachedDecisions = [];
    public function decision($id, $data=null) {
        if (!isset($this->_cachedDecisions[$id])) {
            $this->_cachedDecisions[$id] = new Decision($this, $id, $data);
        }
        return $this->_cachedDecisions[$id];
    }

    private $_cachedTasks = [];
    public function task($id, $data=null) {
        if (!isset($this->_cachedTasks[$id])) {
            $this->_cachedTasks[$id] = new Task($this, $id, $data);
        }
        return $this->_cachedTasks[$id];
    }

    public function getTask($id = '')
    {
        if (!$id) return ;
        try {
            $rdata = $this->get("task/$id");
            return $this->task($rdata['id'], $rdata);
        } catch (\Gini\BPM\Exception $e) {
            return ;
        }

    }

    private $_cachedQuery = [];
    public function searchTasks(array $criteria) {
        $query = [];
        if (isset($criteria['process'])) {
            $query['processDefinitionKey'] = $criteria['process'];
        }
        if (isset($criteria['instance'])) {
            $query['processInstanceId'] = $criteria['instance'];
        }
        if (isset($criteria['groups'])) {
            $query['candidateGroups'] = $criteria['groups'];
        }
        if (isset($criteria['candidate'])) {
            $query['candidateUser'] = $criteria['candidate'];
        }
        if (isset($criteria['assignee'])) {
            $query['assignee'] = $criteria['assignee'];
        }
        if (isset($criteria['active'])) {
            $query['active'] = $criteria['active'];
        }
        if (isset($criteria['includeAssignedTasks'])) {
            $query['includeAssignedTasks'] = true;
        }

        $query['sortBy'] = 'created';
        $query['sortOrder'] = 'desc';

        try {
            $rdata = $this->post("task/count", $query);
        } catch (\Gini\BPM\Exception $e) {
            return ;
        }

        $token = uniqid();
        $this->_cachedQuery[$token] = $query;
        return (object) [
            'token' => $token,
            'total' => $rdata['count']
        ];
    }

    public function getTasks($token, $start=0, $perPage=25) {
        $tasks = [];
        $query = $this->_cachedQuery[$token];
        if (is_array($query)) {
            try {
                $rdata = $this->post("task?firstResult=$start&maxResults=$perPage", $query);
            } catch (\Gini\BPM\Exception $e) {
                return $tasks;
            }


            foreach ((array) $rdata as $d) {
                $tasks[$d['id']] = $this->task($d['id'], $d);
            }
        }

        return $tasks;
    }

    public function getHistoryTasks($token, $start=0, $perPage=25) {
        $tasks = [];
        $query = $this->_cachedQuery[$token];
        if ($query['sortBy'] && $query['sortOrder']) {
            // $sorting = [
            //     'sortBy' => $query['sortBy'],
            //     'sortOrder' => $query['sortOrder']
            // ];
            // $query['sorting'][] = $sorting;
            unset($query['sortBy']);
            unset($query['sortOrder']);
        }

        if (is_array($query)) {
            try {
                $rdata = $this->post("history/task?firstResult=$start&maxResults=$perPage", $query);
            } catch (\Gini\BPM\Exception $e) {
                return $tasks;
            }

            foreach ((array) $rdata as $d) {
                $tasks[$d['id']] = $this->task($d['id'], $d);
            }
        }
        return $tasks;
    }
    private $_cachedUsers = [];
    public function user($id) {
        if (!isset($this->_cachedUsers[$id])) {
            $this->_cachedUsers[$id] = new User($this, $id);
        }
        return $this->_cachedUsers[$id];
    }

    public function searchUsers($criteria = [])
    {
        $query = [];

        if (isset($criteria['firstName'])) {
            $query['firstName'] = $criteria['firstName'];
        }

        if (isset($criteria['firstNameLike'])) {
            $query['firstNameLike'] = '%'.$criteria['firstNameLike'].'%';
        }

        if (isset($criteria['lastName'])) {
            $query['lastName'] = $criteria['lastName'];
        }

        if (isset($criteria['lastNameLike'])) {
            $query['lastNameLike'] = '%'.$criteria['lastNameLike'].'%';
        }

        if (isset($criteria['email'])) {
            $query['email'] = $criteria['email'];
        }

        if (isset($criteria['emailLike'])) {
            $query['emailLike'] = '%'.$criteria['emailLike'].'%';
        }

        if (isset($criteria['sortBy']) && isset($criteria['sortOrder'])) {
            $query['sortBy'] = $criteria['sortBy'];
            $query['sortOrder'] = $criteria['sortOrder'];
        }

        $token = uniqid();
        $this->_cachedQuery[$token] = $query;
        return (object) [
            'token' => $token
        ];
    }

    public function getUsers($token, $start=0, $perPage=25)
    {
        $users = [];
        $query = $this->_cachedQuery[$token];
        if (is_array($query)) {
            try {
                $users = $this->get("user?firstResult=$start&maxResults=$perPage", $query);
            } catch (\Gini\BPM\Exception $e) {
                return $users;
            }

            foreach ((array) $users as $d) {
                $users[$d['id']] = $this->user($d['id']);
            }
        }

        return $users;
    }

    public function getUser($id = '')
    {
        if (!$id) {
            return ;
        }

        try {
            $rdata = $this->get("user/$id/profile");
        } catch (\Gini\BPM\Exception $e) {
            return ;
        }

        $user = $this->user($rdata['id']);
        return $user;
    }

    public function addUser($criteria = [])
    {
        if (!isset($criteria['id']) ||
            !isset($criteria['name']) ||
            !isset($criteria['email'])) {
            return ;
        }

        $arr = explode('@', $criteria['email'], 2);
        $password = $arr[0].$criteria['id'];

        $query['profile']['id'] = $criteria['id'];
        $query['profile']['firstName'] = $criteria['name'];
        $query['profile']['lastName'] = $criteria['name'];
        $query['profile']['email'] = $criteria['email'];
        $query['credentials']['password'] = $password;

        try {
            return $this->post("user/create", $query);
        } catch (\Gini\BPM\Exception $e) {
            return ;
        }
    }

    public function deleteUser($id = '')
    {
        if (!$id) return;
        try {
            return $this->delete("user/$id");
        } catch (\Gini\BPM\Exception $e) {
            return ;
        }
    }
}
