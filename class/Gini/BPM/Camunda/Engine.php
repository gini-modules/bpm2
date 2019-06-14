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
            throw new \Gini\BPM\Exception($status->code . ': '. $data['message']);
        }
        return $data;
    }

    public function get($path, array $data=[]) {
        $response = $this->http
            ->get("{$this->root}/engine/engine/{$this->engine}/$path", $data);
        $status = $response->status();
        $data = json_decode($response->body, true);
        if (floor($status->code/100) != 2) {
            throw new \Gini\BPM\Exception($status->code . ': '. $data['message']);
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
            throw new \Gini\BPM\Exception($status->code . ': '. $data['message']);
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
            throw new \Gini\BPM\Exception($status->code . ': '. $data['message']);
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
    public function searchProcessInstances(array $criteria) {
        $query = [];

        /**
         * process:进程唯一标识 key
		 * type: string
         */
        if (isset($criteria['process'])) {
            $query['processDefinitionKey'] = $criteria['process'];
        }

        /**
         * sortBy: 排序规则
         * type: array
         * format:['title1' => 'asc', 'title2' => 'desc']
         */
        if (isset($criteria['sortBy'])) {
            $sorting = [];
            foreach ($criteria['sortBy'] as $sortBy => $sortOrder) {
                $sorting[] = [
                    'sortBy' => $sortBy,
                    'sortOrder' => $sortOrder
                ];
            }
            $query['sorting'] = $sorting;
        }

        if (isset($criteria['variables'])) {
            $variables = [];
            foreach ($criteria['variables'] as $name => $exp) {
                list($operator, $value) = $this->_makeVariable($exp);
                $variables[] = [
                    'name' => $name,
                    'operator' => $operator,
                    'value' => $value
                ];
            }
            $query['variables'] = $variables;
        }
        /**
         * processInstance: 包含 单个实例 ID 或 多个实例 ID 的数组
         * type: array
         */
        if (isset($criteria['processInstance'])) {
            $query['processInstanceIds'] = $this->_normalizeToArray($criteria['processInstance']);
        }

        /**
         * history: 是否检索历史记录, true 检索历史数据， false 检索活动中的数据
         * type: bool
         */
        if (isset($criteria['history'])) {
            $query['history'] = $criteria['history'];
            $path = "history/process-instance/count";
        } else {
            $path = "process-instance/count";
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
    public function getProcessInstances($token, $start=0, $perPage=25) {
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

    private function _normalizeToArray($criteria)
    {
        return array_map('trim',  is_array($criteria) ? $criteria : explode($criteria, ','));
    }

    private function _normalizeToSingle($criteria)
    {
        return is_array($criteria) ? $criteria[0] : $criteria;
    }

    private function _makeVariable($expression = '')
    {
        $pattern = '/^\s*(?:(\^=|\$=|\*=|!=|<=|>=|<|>|=)\s*)?(.+)\s*$/';
        preg_match($pattern, trim($expression), $matches);

        $opt = $matches[1];
        $val = $matches[2];
        switch ($opt) {
            case '>=':
                return ['gteq', $val];
                break;
            case '>':
                return ['gt', $val];
                break;
            case '<=':
                return ['lteq', $val];
                break;
            case '<':
                return ['lt', $val];
                break;
            case '=':
                return ['eq', $val];
                break;
            case '!=':
                return ['neq', $val];
                break;
            case '*=':
                return ['like', $val];
                break;
        }

        return false;
    }

    /**
     * [searchTasks Retrieves the number of tasks that fulfill a provided filter.]
     * @param  array  $criteria [parameters]
     * @return [object]           [token, total]
     */
    public function searchTasks(array $criteria) {
        $query = [];

        /**
         * processInstance: 实例唯一 ID
         * type: string
         */
        if (isset($criteria['processInstance'])) {
            $query['processInstanceId'] = $criteria['processInstance'];
        }

        /**
         * process: 进程唯一标识 key
         * type: string
         */
        if (isset($criteria['process'])) {
            $query['processDefinitionKey'] = $criteria['process'];
        }

        /**
         * assignee: 任务所分配的 用户 或者 组
         * type: string
         */
        if (isset($criteria['assignee'])) {
            $query['assignee'] = $criteria['assignee'];
        }

        /**
         * execution: 任务所在的执行流程 ID
         * type: string
         */
        if (isset($criteria['execution'])) {
            $query['executionId'] = $criteria['execution'];
        }

        /**
         * sortBy: 排序规则
         * type: array
         * format:['title1' => 'asc', 'title2' => 'desc']
         */
        if (isset($criteria['sortBy'])) {
            $sorting = [];
            foreach ($criteria['sortBy'] as $sortBy => $sortOrder) {
                $sorting[] = [
                    'sortBy' => $sortBy,
                    'sortOrder' => $sortOrder
                ];
            }
            $query['sorting'] = $sorting;
        }

        /**
         * candidateGroup: 任务所属单个组或多个组
         * type: array
         */
        if (isset($criteria['candidateGroup'])) {
            if (isset($criteria['history'])) {
                $query['taskHadCandidateGroup'] = $this->_normalizeToSingle($criteria['candidateGroup']);
            } else {
                $query['candidateGroups'] = $this->_normalizeToArray($criteria['candidateGroup']);
            }
        }

        /**
         * candidate: 任务所属的用户
         * type: string
         */
        if (isset($criteria['candidate'])) {
            if (isset($criteria['history'])) {
                $query['taskHadCandidateUser'] = $this->_normalizeToSingle($criteria['candidate']);
            } else {
                $query['candidateUser'] = $this->_normalizeToSingle($criteria['candidate']);
            }
        }

        /**
         * history: 是否检索历史记录, true 检索历史数据， false 检索活动中的数据
         * type: bool
         */
        if (isset($criteria['history'])) {
            $query['history'] = $criteria['history'];
            $path = "history/task/count";
        } else {
            /**
             * includeAssignedTasks:是否包含被分配的任务,只有活动中的任务支持该参数
             * type: bool
             */
            if (isset($criteria['includeAssignedTasks'])) {
                $query['includeAssignedTasks'] = $criteria['includeAssignedTasks'];
            }
            $path = "task/count";
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

        /**
         * type: 组所属的类型
         * type: string
         */
        $query['type'] = $criteria['type'];

        /**
         * name: 组名称，可以是模糊查询
         * type: string
         */
        if (isset($criteria['name'])) {
            $result = $this->_makeQuery($criteria['name']);
            if ($result['like']) {
                $query['NameLike'] = $result['pattern'];
            } else {
                $query['Name'] = $result['pattern'];
            }
        }

        /**
         * member: 用户ID，返回包含该用户的组
         * type: string
         */
        if (isset($criteria['member'])) {
            $query['member'] = $criteria['member'];
        }

        /**
         * sortBy: 排序规则,包含一个元素的数组
         * type: array
         * format: ['title' => 'desc']
         */
        if (isset($criteria['sortBy'])) {
            $sorting = [];
            foreach ($criteria['sortBy'] as $sortBy => $sortOrder) {
                $query['sortBy'] = $sortBy;
                $query['sortOrder'] = $sortOrder;
            }
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

        /**
         * name: 用户名称，可以是模糊查询
         * type: string
         */
        if (isset($criteria['name'])) {
            $result = $this->_makeQuery($criteria['name']);
            if ($result['like']) {
                $query['firstNameLike'] = $result['pattern'];
            } else {
                $query['firstName'] = $result['pattern'];
            }
        }

        /**
         * email: 用户邮箱， 可以模糊查询
         * type: string
         */
        if (isset($criteria['email'])) {
            $result = $this->_makeQuery($criteria['email']);

            if ($result['like']) {
                $query['emailLike'] = $result['pattern'];
            } else {
                $query['email'] = $result['pattern'];
            }
        }

        /**
         * group: 用户所属的组 ID
         * type: string
         */
        if (isset($criteria['group'])) {
            $query['memberOfGroup'] = $criteria['group'];
        }

        /**
         * sortBy: 排序规则,包含一个元素的数组
         * type: array
         * format: ['title' => 'desc']
         */
        if (isset($criteria['sortBy'])) {
            $sorting = [];
            foreach ($criteria['sortBy'] as $sortBy => $sortOrder) {
                $query['sortBy'] = $sortBy;
                $query['sortOrder'] = $sortOrder;
            }
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
