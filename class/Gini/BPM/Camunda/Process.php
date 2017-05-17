<?php

namespace Gini\BPM\Camunda;

class Process implements \Gini\BPM\Driver\Process {

    private $camunda;
    private $id;
    private $rdata;

    public function __construct($camunda, $id) {
        $this->camunda = $camunda;
        $this->id = $id;
        $this->rdata = $this->_fetchData();
    }

    private function _fetchData() {
        $process = a('sjtu/bpm/process', ['name' => $this->id]);
        if ($process->id) {
            return $process;
        }
        return ;
    }

    public function __get($name)
    {
        return $this->$name;
    }

    public function start(array $vars) {
        $cvars = Engine::convertVariables($vars);
        $key = $this->id;
        $tag = $vars['tag'];

        $rdata = $this->camunda->post("process-definition/key/$key/start", [
            'variables' => $cvars,
            'businessKey' => $tag,
        ]);
        return new ProcessInstance($this->camunda, $rdata['id'], $rdata);
    }

    public function getGroups(array $criteria)
    {
        $groups = [];

        if (!$criteria['process']) return ;
        $query['type'] = $criteria['process'];

        if ($criteria['user']) {
            $query['member'] = $criteria['user'];
        }
        try {
            $rdata = $this->camunda->get("group", $query);
        } catch (\Gini\BPM\Exception $e) {
            return false;
        }

        foreach ($rdata as $d) {
            $groups[$d['id']] = new Group($this->camunda, $d['id'], $d);
        }

        return $groups;
    }

    public function getGroup($id = '')
    {
        if (!$id) return ;

        try {
            $rdata = $this->camunda->get("group/$id");
            return new Group($this->camunda, $rdata['id'], $rdata);
        } catch (\Gini\BPM\Exception $e) {
            return fasle;
        }
    }

    public function addGroup($id = '', array $criteria)
    {
        if (!$criteria['id'] || !$criteria['name'] || !$criteria['type']) return ;

        try {
            return $this->camunda->post("group/create", $criteria);
        } catch (\Gini\BPM\Exception $e) {
            return ;
        }
    }

    public function updateGroup($id = '', array $criteria)
    {
        if (!$id || !$criteria['id'] || !$criteria['name'] || !$criteria['type']) return ;

        try {
            $group = new Group($this->camunda, $id, $criteria);
            return $group->update($criteria);
        } catch (\Gini\BPM\Exception $e) {
            return ;
        }
    }

    public function getInstances($start=0, $perpage=25, $user=null)
    {
        $pid = $this->rdata->id;
        $sql = "SELECT DISTINCT instance_id AS id FROM sjtu_bpm_process_task WHERE process_id={$pid}";

        if (!is_null($user)) {
            $groups = $this->getGroups(['process' => $this->id]);
            $gids = [];
            foreach ($groups as $group) {
                $gids[] = $group->id;
            }
            if (empty($gids)) return;
            $gids = implode(',', $gids);
            $sql = "{$sql} AND candidate_group_id IN ({$gids})";
        }

        $sql = "{$sql} ORDER BY id DESC LIMIT {$start},{$perpage}";
        $db = \Gini\Database::db();
        $query = $db->query($sql);
        $instances = [];
        if ($query) foreach ($query->rows() as $obj) {
            $instances[$obj->id] = a('sjtu/bpm/process/instance', $obj->id);
        }

        return $instances;
    }

    public function searchInstances($user=null)
    {
        $pid = $this->rdata->id;
        $sql = "SELECT count(DISTINCT instance_id) FROM sjtu_bpm_process_task WHERE process_id={$pid}";

        if (!is_null($user)) {
            $groups = $this->getGroups(['process' => $this->id]);
            $gids = [];
            foreach ($groups as $group) {
                $gids[] = $group->id;
            }
            if (empty($gids)) return;
            $gids = implode(',', $gids);
            $sql = "{$sql} AND candidate_group_id IN ({$gids})";
        }

        $db = \Gini\Database::db();
        $query = $db->query($sql);
        if (!$query) return;

        return $query->value();
    }
}
