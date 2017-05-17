<?php

namespace Gini\BPM\Camunda;

class Group implements \Gini\BPM\Driver\Group
{
    private $camunda;
    private $id;
    private $name;
    private $type;

    public function __construct($camunda, $id, $data=null)
    {
        $this->camunda = $camunda;
        $this->id = $id;

        if ($data) {
            $this->name = $data['name'];
            $this->type = $data['type'];
        }
    }

    public function __get($name) {
        return $this->$name;
    }

    public function remove()
    {
        if (!$id = $this->id) return ;

        try {
            $result = $this->camunda->delete("group/$id");
            return $result;
        } catch (\Gini\BPM\Exception $e) {
            return false;
        }
    }

    public function update(array $criteria)
    {
        $id = $this->id;

        if (!$id || !$criteria['id'] || !$criteria['name'] || !$criteria['type']) return ;
        try {
            $result = $this->camunda->put("group/$id", $criteria);
            return $result;
        } catch (\Gini\BPM\Exception $e) {
            return false;
        }
    }

    public function getMembers()
    {
        $members = [];

        $query['memberOfGroup'] = $this->id;
        if (is_array($query)) {
            try {
                $rdata = $this->camunda->get("user", $query);
            } catch (\Gini\BPM\Excetion $e) {
                return $members;
            }

            foreach ($rdata as $key => $d) {
                $members[$d['id']] = new User($this->camunda, $d['id'], $d);
            }
        }

        return $members;
    }

    public function addMember(array $criteria = [])
    {
        $group_id = $criteria['group'];
        $user_id = $criteria['user'];

        if (!$group_id || !$group_id) return ;

        try {
            return $this->camunda->put("group/$group_id/members/$user_id");
        } catch (\Gini\BPM\Exception $e) {
            return ;
        }
    }

    public function removeMember(array $criteria = [])
    {
        $group_id = $criteria['group'];
        $user_id = $criteria['user'];

        if (!$group_id || !$group_id) return ;

        try {
            return $this->camunda->delete("group/$group_id/members/$user_id");
        } catch (\Gini\BPM\Exception $e) {
            return ;
        }
    }
}
