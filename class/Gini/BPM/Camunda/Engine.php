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
        return $data;
    }

    public function get($path, array $data=[]) {
        $response = $this->http
            ->get("{$this->root}/engine/engine/{$this->engine}/$path", $data);
        $status = $response->status();
        $data = json_decode($response->body, true);
        if (floor($status->code/100) != 2) {
            throw new \Gini\BPM\Exception($data['message']);
        }
        return $data;
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

        return $data;
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

        return $data;
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

    /**
     * [deploy Creates a deployment.]
     * @param  [type] $name  [The name for the deployment to be created.]
     * @param  [type] $files [resource]
     * @return [array]        [A JSON object corresponding to the Deployment interface in the engine]
     */
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

    private $_cachedQuery = [];
    /**
     * [searchInstances Queries for the number of process instances that fulfill given parameters]
     * @param  array  $criteria [parameters]
     * @return [array]           [token, total]
     */
    public function searchInstances(array $criteria) {
        $query = [];
        if ($criteria['processInstanceIds']) {
            $query['processInstanceIds'] = $criteria['processInstanceIds'];
        }
        if ($criteria['businessKey']) {
            $query['businessKey'] = $criteria['businessKey'];
        }
        if ($criteria['process']) {
            $query['processDefinitionKey'] = $criteria['process'];
        }
        if ($criteria['deploymentId']) {
            $query['deploymentId'] = $criteria['deploymentId'];
        }
        if ($criteria['superProcessInstance']) {
            $query['superProcessInstance'] = $criteria['superProcessInstance'];
        }
        if ($criteria['subProcessInstance']) {
            $query['subProcessInstance'] = $criteria['subProcessInstance'];
        }
        if ($criteria['active']) {
            $query['active'] = $criteria['active'];
        }

        $path = "process-instance/count";
        if (isset($criteria['history'])) {
            $path = "history/process-instance/count";
            $query['history'] = $criteria['history'];
            $query['sorting'] = $criteria['sorting'];
        }

        $rdata = $this->post($path, $query);
        $token = uniqid();
        $this->_cachedQuery[$token] = $query;
        return (object) [
            'token' => $token,
            'total' => $rdata['count']
        ];
    }

    /**
     * [getInstances Queries for process instances that fulfill given parameters through a JSON object]
     * @param  [type]  $token   [token]
     * @param  integer $start   [start]
     * @param  integer $perPage [perPage]
     * @return [array]           [A JSON array of process instance objects.]
     */
    public function getInstances($token, $start=0, $perPage=25) {
        $instances = [];
        $query = $this->_cachedQuery[$token];
        $path = isset($query['history']) ? "history/process-instance" : "process-instance";
        if (is_array($query)) {
            $rdata = $this->post($path."?firstResult=$start&maxResults=$perPage", $query);
            foreach ((array) $rdata as $d) {
                $instances[$d['id']] = $this->processInstance($d['id'], $d);
            }
        }
        return $instances;
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

    private $_cachedExecutions = [];
    public function execution($id) {
        if (!isset($this->_cachedExecutions[$id])) {
            $this->_cachedExecutions[$id] = new Execution($this, $id);
        }
        return $this->_cachedExecutions[$id];
    }

    /**
     * [searchTasks Retrieves the number of tasks that fulfill a provided filter.]
     * @param  array  $criteria [parameters]
     * @return [object]           [token, total]
     */
    public function searchTasks(array $criteria) {
        $query = [];
        if (isset($criteria['instance'])) {
            $query['processInstanceId'] = $criteria['instance'];
        }
        if (isset($criteria['process'])) {
            $query['processDefinitionKey'] = $criteria['process'];
        }
        if (isset($criteria['group'])) {
            $query['candidateGroup'] = $criteria['group'];
        }
        if (isset($criteria['candidateGroups'])) {
            $query['candidateGroups'] = $criteria['candidateGroups'];
        }
        if (isset($criteria['candidate'])) {
            $query['candidateUser'] = $criteria['candidate'];
        }
        if (isset($criteria['assignee'])) {
            $query['assignee'] = $criteria['assignee'];
        }
        if (isset($criteria['execution'])) {
            $query['executionId'] = $criteria['execution'];
        }
        if (isset($criteria['includeAssignedTasks'])) {
            $query['includeAssignedTasks'] = $criteria['includeAssignedTasks'];
        }

        $path = "task/count";
        if (isset($criteria['history'])) {
            $path = "history/task/count";
            $query['history'] = $criteria['history'];
        }

        $rdata = $this->post($path, $query);
        $token = uniqid();
        $this->_cachedQuery[$token] = $query;
        return (object) [
            'token' => $token,
            'total' => $rdata['count']
        ];
    }

    /**
     * [getTasks Queries for tasks that fulfill a given filter]
     * @param  [type]  $token   [token]
     * @param  integer $start   [start]
     * @param  integer $perPage [perPage]
     * @return [array]           [A JSON array of task objects]
     */
    public function getTasks($token, $start=0, $perPage=25) {
        $tasks = [];
        $query = $this->_cachedQuery[$token];
        $path = isset($query['history']) ? "history/task" : "task";
        if (is_array($query)) {
            $rdata = $this->post($path."?firstResult=$start&maxResults=$perPage", $query);
            foreach ((array) $rdata as $d) {
                $tasks[$d['id']] = $this->task($d['id'], $d);
            }
        }
        return $tasks;
    }

    private function _makeQuery($name)
    {
        $pos = strpos($name, '=');
        if (!$pos) {
            if ($pos === 0) {
                $val = substr($name, $pos+1);
                return ['pattern' => $val];
            } else {
                return ['pattern' => $name];
            }
        }
        else {
            $val = substr($name, $pos+1);
            $pos--;
            $opt = $name[$pos].'=';

            switch ($opt) {
                case '^=': {
                    $pattern = $val.'%';
                }
                break;

                case '$=': {
                    $pattern = '%'.$val;
                }
                break;

                case '*=': {
                    $pattern = '%'.$val.'%';
                }
                break;
            }

            return [
                'like' => true,
                'pattern' => $pattern
            ];
        }
    }

    private $_cachedGroups = [];
    public function group($id = '') {
        if (!isset($this->_cachedGroups[$id])) {
            $this->_cachedGroups[$id] = new Group($this, $id);
        }
        return $this->_cachedGroups[$id];
    }

    /**
     * [searchGroups Queries for groups using a list of parameters and retrieves the count.]
     * @param  array  $criteria [parameters]
     * @return [object]           [token, total]
     */
    public function searchGroups(array $criteria) {
        $groups = [];

        if (!isset($criteria['type'])) return;
        $query['type'] = $criteria['type'];

        if (isset($criteria['name'])) {
            $result = $this->_makeQuery($criteria['name']);
            if ($result['like']) {
                $query['NameLike'] = $result['pattern'];
            } else {
                $query['Name'] = $result['pattern'];
            }
        }

        if (isset($criteria['member'])) {
            $query['member'] = $criteria['member'];
        }

        $rdata = $this->get("group/count", $query);
        $token = uniqid();
        $this->_cachedQuery[$token] = $query;
        return (object) [
            'token' => $token,
            'total' => $rdata['count']
        ];
    }

    /**
     * [getGroups Queries for a list of groups using a list of parameters.]
     * @param  [string]  $token   [token]
     * @param  integer $start   [start]
     * @param  integer $perPage [perPage]
     * @return [array]           [A JSON array of group objects.]
     */
    public function getGroups($token, $start=0, $perPage=25) {
        $groups = [];

        $query = $this->_cachedQuery[$token];
        if (is_array($query)) {
            $rdata = $this->get("group", $query);
            foreach ($rdata as $d) {
                $groups[$d['id']] = $this->group($d['id'], $d);
            }
        }

        return $groups;
    }

    private $_cachedUsers = [];
    public function user($id = '') {
        if (!isset($this->_cachedUsers[$id])) {
            $this->_cachedUsers[$id] = new User($this, $id);
        }
        return $this->_cachedUsers[$id];
    }

    /**
     * [searchUsers Query for users using a list of parameters and retrieves the count.]
     * @param  [array] $criteria [parameters]
     * @return [object]           [token, total]
     */
    public function searchUsers($criteria = []) {
        $query = [];

        if (isset($criteria['name'])) {
            $result = $this->_makeQuery($criteria['name']);
            if ($result['like']) {
                $query['firstNameLike'] = $result['pattern'];
            } else {
                $query['firstName'] = $result['pattern'];
            }
        }

        if (isset($criteria['email'])) {
            $result = $this->_makeQuery($criteria['email']);

            if ($result['like']) {
                $query['emailLike'] = $result['pattern'];
            } else {
                $query['email'] = $result['pattern'];
            }
        }

        if (isset($criteria['group'])) {
            $query['memberOfGroup'] = $criteria['group'];
        }

        if (isset($criteria['sortBy']) && isset($criteria['sortOrder'])) {
            $query['sortBy'] = $criteria['sortBy'];
            $query['sortOrder'] = $criteria['sortOrder'];
        }

        $rdata = $this->get("user/count", $query);

        $token = uniqid();
        $this->_cachedQuery[$token] = $query;
        return (object) [
            'token' => $token,
            'total' => $rdata['count']
        ];
    }

    /**
     * [getUsers Query for a list of users.]
     * @param  [type]  $token   [token]
     * @param  integer $start   [start]
     * @param  integer $perPage [perPage]
     * @return [array]           [A JSON array of user objects]
     */
    public function getUsers($token, $start=0, $perPage=25) {
        $users = [];
        $query = $this->_cachedQuery[$token];
        if (is_array($query)) {
            $rdata = $this->get("user?firstResult=$start&maxResults=$perPage", $query);

            foreach ((array) $rdata as $d) {
                $users[$d['id']] = $this->user($d['id']);
            }
        }
        return $users;
    }
}


