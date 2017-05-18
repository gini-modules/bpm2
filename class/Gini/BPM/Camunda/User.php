<?php

namespace Gini\BPM\Camunda;

class User implements \Gini\BPM\Driver\User
{
    private $camunda;

    public function __construct($camunda, $id) {
        $this->camunda = $camunda;
        $this->id = $id;
        $this->_fetchData();
    }

    private function _fetchData() {
        $id = $this->id;
        try {
            $rdata = $this->camunda->get("user/$id/profile");
            foreach ($rdata as $key => $d) {
                $this->$key = $d;
            }
        } catch (\Gini\BPM\Exception $e) {
        }
    }

    //Deletes a user by id.
    public function delete()
    {
        $id = $this->id;
        if (!$id) return;
        try {
            $rdata = $this->camunda->delete("user/$id");
            return empty($rdata) ? true : $rdata;
        } catch (\Gini\BPM\Exception $e) {
            return ;
        }
    }

    //Updates the profile information of an already existing user.
    public function update(array $criteria)
    {
        $id = $this->id;
        if (!$id ||
            !$criteria['id'] ||
            !$criteria['firstName'] ||
            !$criteria['lastName'] ||
            !$criteria['email']
        ) return;

        $query['id'] = $criteria['id'];
        $query['firstName'] = $criteria['firstName'];
        $query['lastName'] = $criteria['lastName'];
        $query['email'] = $criteria['email'];

        try {
            $rdata = $this->camunda->put("user/$id/profile", $query);
            return empty($rdata) ? true : $rdata;
        } catch (\Gini\BPM\Exception $e) {
            return ;
        }
    }

    //Updates a user’s credentials (password).
    public function updatePassWord(array $criteria)
    {
        $id = $this->id;
        if (!$id ||
            !$criteria['newpassword'] ||
            !$criteria['password']
        ) return;

        $query['password'] = $criteria['newpassword'];
        $query['authenticatedUserPassword'] = $criteria['password'];

        try {
            $rdata = $this->camunda->put("user/$id/credentials", $query);
            return empty($rdata) ? true : $rdata;
        } catch (\Gini\BPM\Exception $e) {
            return ;
        }
    }
}
