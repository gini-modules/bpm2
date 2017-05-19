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
            ->header('Content-Type', 'application/x-www-form-urlencoded')
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

    private $_cachedQuery = [];
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

        $path = "task/count";
        if (isset($criteria['history'])) {
            $path = "history/task/count";
            $query['history'] = $criteria['history'];
        }

        try {
            $rdata = $this->get($path, $query);
        } catch (Exception $e) {
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

        $path = isset($query['history']) ? "history/task" : "task";
        if (is_array($query)) {
            try {
                $rdata = $this->get($path."?firstResult=$start&maxResults=$perPage", $query);
                foreach ((array) $rdata as $d) {
                    $tasks[$d['id']] = $this->task($d['id'], $d);
                }
            } catch (\Gini\BPM\Exception $e) {
            }
        }
        return $tasks;
    }

    private $_cachedGroups = [];
    public function group($id = '') {
        if (!isset($this->_cachedGroups[$id])) {
            $this->_cachedGroups[$id] = new Group($this, $id);
        }
        return $this->_cachedGroups[$id];
    }

    //Queries for groups using a list of parameters and retrieves the count.
    public function searchGroups(array $criteria) {
        $groups = [];

        if (!isset($criteria['type'])) return;
        $query['type'] = $criteria['type'];

        if (isset($criteria['name'])) {
            $query['name'] = $criteria['name'];
        }

        if (isset($criteria['nameLike'])) {
            $query['nameLike'] = '%'.$criteria['nameLike'].'%';
        }

        if (isset($criteria['member'])) {
            $query['member'] = $criteria['member'];
        }

        try {
            $rdata = $this->get("group/count", $query);
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

    //Queries for a list of groups using a list of parameters.
    public function getGroups($token, $start=0, $perPage=25) {
        $groups = [];

        $query = $this->_cachedQuery[$token];
        if (is_array($query)) {
            try {
                $rdata = $this->get("group", $query);
            } catch (\Gini\BPM\Exception $e) {
                return ;
            }

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

    //Query for users using a list of parameters and retrieves the count.
    public function searchUsers($criteria = []) {
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

        if (isset($criteria['group'])) {
            $query['memberOfGroup'] = $criteria['group'];
        }

        if (isset($criteria['sortBy']) && isset($criteria['sortOrder'])) {
            $query['sortBy'] = $criteria['sortBy'];
            $query['sortOrder'] = $criteria['sortOrder'];
        }

        try {
            $rdata = $this->get("user/count", $query);
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

    //Query for a list of users using a list of parameters.
    public function getUsers($token, $start=0, $perPage=25) {
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
}
