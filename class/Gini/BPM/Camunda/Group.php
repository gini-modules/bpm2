<?php

namespace Gini\BPM\Camunda;

class Group implements \Gini\BPM\Driver\Group
{
    private $camunda;
    public function __construct($camunda, $id = '') {
        $this->camunda = $camunda;
        if ($id) {
            $this->id = $id;
            $this->_fetchData();
        }
    }

    private function _fetchData() {
        $id = $this->id;
        unset($this->id);
        $rdata = $this->camunda->get("group/$id");
        if (isset($rdata['id'])) {
            foreach ($rdata as $key => $value) {
                $this->$key = $value;
            }
        }
    }

    /**
     * [create Creates a new group.]
     * @param  array  $criteria [The array contains the following properties: id (String), name (String), type (String)]
     * @return [bool]           [true/false]
     */
    public function create(array $criteria) {
        if (!$criteria['id'] || !$criteria['name'] || !$criteria['type']) return ;

        $query['id'] = $criteria['id'];
        $query['name'] = $criteria['name'];
        $query['type'] = $criteria['type'];

        $result = $this->camunda->post("group/create", $query);
        return empty($result) ? true : false;
    }

    /**
     * [delete Deletes a group by id.]
     * @return [bool] [true/false]
     */
    public function delete() {
        if (!$id = $this->id) return ;

        $result = $this->camunda->delete("group/$id");
        return empty($result) ? true : false;
    }

    /**
     * [update Updates a given group by id.]
     * @param  array  $criteria [The array contains the following properties: id (String), name (String), type (String)]
     * @return [bool]           [true/false]
     */
    public function update(array $criteria) {
        $id = $this->id;
        if (!$id ||
            !$criteria['id'] ||
            !$criteria['name'] ||
            !$criteria['type']
        ) return ;

        $query['id'] = $criteria['id'];
        $query['name'] = $criteria['name'];
        $query['type'] = $criteria['type'];

        $result = $this->camunda->put("group/$id", $query);
        return empty($result) ? true : false;
    }

    /**
     * [getMembers Query for a list of users using a list of parameters.]
     * @return [array] [A JSON array of user objects.]
     */
    public function getMembers() {
        $members = [];

        $query['memberOfGroup'] = $this->id;
        if (is_array($query)) {
            $rdata = $this->camunda->get("user", $query);
            foreach ($rdata as $key => $d) {
                $members[$d['id']] = new User($this->camunda, $d['id'], $d);
            }
        }

        return $members;
    }

    /**
     * [addMember Adds a member to a group.]
     * @param [int] $user_id [The id of user to add to the group.]
     * @return [bool] [true/false]
     */
    public function addMember($user_id) {
        $group_id = $this->id;
        if (!$group_id || !$user_id) return ;

        $result = $this->camunda->put("group/$group_id/members/$user_id");
        return empty($result) ? true : false;
    }

    /**
     * [removeMember Removes a member from a group.]
     * @param  [type] $user_id [The id of user to remove from the group.]
     * @return [bool]          [true/false]
     */
    public function removeMember($user_id) {
        $group_id = $this->id;
        if (!$group_id || !$user_id) return ;

        $result = $this->camunda->delete("group/$group_id/members/$user_id");
        return empty($result) ? true : false;
    }
 
    /**
     * [hasMember if the user is in this group]
     * @param  [string]  $user_id [user_id]
     * @return bool      [true | false]
     */
    public function hasMember($user_id) {
        $group_id = $this->id;
        if (!$group_id || !$user_id) return ;

        $query['memberOfGroup'] = $group_id;
        $query['id'] = $user_id;
        $result = $this->camunda->get("user", $query);
        return empty($result) ? false : true;
    }
}

