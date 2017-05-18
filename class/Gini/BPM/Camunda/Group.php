<?php

namespace Gini\BPM\Camunda;

class Group implements \Gini\BPM\Driver\Group
{
    private $camunda;
    public function __construct($camunda, $id = '')
    {
        $this->camunda = $camunda;
        if ($id) {
            $this->id = $id;
            $this->_fetchData();
        }
    }

    private function _fetchData() {
        $id = $this->id;
        try {
            $rdata = $this->camunda->get("group/$id");
            foreach ($rdata as $key => $value) {
                $this->$key = $value;
            }
        } catch (\Gini\BPM\Exception $e) {
        }
    }

    //Creates a new group.
    public function create(array $criteria)
    {
        if (!$criteria['id'] || !$criteria['name'] || !$criteria['type']) return ;

        $query['id'] = $criteria['id'];
        $query['name'] = $criteria['name'];
        $query['type'] = $criteria['type'];

        try {
            $rdata = $this->camunda->post("group/create", $query);
            return empty($rdata) ? true : $rdata;
        } catch (\Gini\BPM\Exception $e) {
            return ;
        }
    }

    //Deletes a group by id.
    public function delete()
    {
        if (!$id = $this->id) return ;

        try {
            $rdata = $this->camunda->delete("group/$id");
            return empty($rdata) ? true : $rdata;
        } catch (\Gini\BPM\Exception $e) {
            return ;
        }
    }

    //Updates a given group by id.
    public function update(array $criteria)
    {
        $id = $this->id;
        if (!$id ||
            !$criteria['id'] ||
            !$criteria['name'] ||
            !$criteria['type']
        ) return ;

        $query['id'] = $criteria['id'];
        $query['name'] = $criteria['name'];
        $query['type'] = $criteria['type'];

        try {
            $rdata = $this->camunda->put("group/$id", $query);
            return empty($rdata) ? true : $rdata;
        } catch (\Gini\BPM\Exception $e) {
            return ;
        }
    }

    // Query for a list of users using a list of parameters.
    public function getMembers()
    {
        $members = [];

        $query['memberOfGroup'] = $this->id;
        if (is_array($query)) {
            try {
                $rdata = $this->camunda->get("user", $query);
            } catch (\Gini\BPM\Excetion $e) {
                return ;
            }

            foreach ($rdata as $key => $d) {
                $members[$d['id']] = new User($this->camunda, $d['id'], $d);
            }
        }

        return $members;
    }

    //Adds a member to a group.
    public function addMember(array $criteria = [])
    {
        $group_id = $this->id;
        $user_id = $criteria['user'];

        if (!$group_id || !$group_id) return ;

        try {
            $rdata = $this->camunda->put("group/$group_id/members/$user_id");
            return empty($rdata) ? true : $rdata;
        } catch (\Gini\BPM\Exception $e) {
            return ;
        }
    }

    //Removes a member from a group.
    public function removeMember(array $criteria = [])
    {
        $group_id = $this->id;
        $user_id = $criteria['user'];

        if (!$group_id || !$user_id) return ;

        try {
            $rdata = $this->camunda->delete("group/$group_id/members/$user_id");
            return empty($rdata) ? true : $rdata;
        } catch (\Gini\BPM\Exception $e) {
            return ;
        }
    }
}
