<?php

namespace Gini\BPM\Camunda;

class Engine implements \Gini\BPM\Interface\Engine {

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

        $root = $options['api_root'];

        $this->http = new \Gini\HTTP();
        $this->http->enableCookie()->header('Accept', 'application/json');

        $response = $this->http
            ->header('Content-Type', 'application/x-www-form-urlencoded')
            ->post("$root/admin/auth/user/default/login/cockpit", [
                'username' => $options['username'],
                'password' => $options['password'],
            ]);
        $rdata = json_decode($response->body, true);
        $this->userId = $rdata['userId'];
        $this->authorizedApps = $data['authorizedApps'];
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
            $this->_cachedProcesses[$id] = new Camunda\Process($this, $id);
        }
        return $this->_cachedProcesses[$id];
    }

    private $_cachedProcessInstances = [];
    public function processInstance($id) {
        if (!isset($this->_cachedProcessInstances[$id])) {
            $this->_cachedProcessInstances[$id] = new Camunda\ProcessInstance($this, $id);
        }
        return $this->_cachedProcessInstances[$id];
    }

    public function call($path, $data) {
        $response = $this->http
            ->header('Content-Type', 'application/json')
            ->post("$root/engine/engine/$engine/process-definition/key/$key/start", $data);
        return json_decode($response->body, true);
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
        return $cvars;
    }
}