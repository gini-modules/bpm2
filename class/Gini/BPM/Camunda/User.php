<?php

namespace Gini\BPM\Camunda;

class User implements \Gini\BPM\Driver\User
{
    private $camunda;

    public function __construct($camunda, $id = '') {
        $this->camunda = $camunda;
        if ($id) {
            $this->_fetchData();
        }
    }

    private function _fetchData() {
        $id = $this->id;
        try {
            $rdata = $this->camunda->get("user/$id/profile");
            foreach ($rdata as $key => $value) {
                $this->$key = $value;
            }
        } catch (\Gini\BPM\Exception $e) {
        }
    }

    public function create(array $criteria)
    {
        if (!$criteria['id'] ||
            !$criteria['firstName'] ||
            !$criteria['lastName'] ||
            !$criteria['email'] ||
            !$criteria['password']
        ) return ;

        $query['profile']['id'] = $criteria['id'];
        $query['profile']['firstName'] = $criteria['firstName'];
        $query['profile']['lastName'] = $criteria['lastName'];
        $query['profile']['email'] = $criteria['email'];
        $query['credentials']['password'] = $criteria['password'];

        try {
            $rdata = $this->camunda->post("user/create", $query);
            return empty($rdata) ? true : $rdata;
        } catch (\Gini\BPM\Exception $e) {
            return ;
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
    public function changePassword($password, $newpassword)
    {
        $id = $this->id;
        if (!$id || !$password || !$newpassword) return;

        $query['password'] = $newpassword;
        $query['authenticatedUserPassword'] = $password;

        try {
            $rdata = $this->camunda->put("user/$id/credentials", $query);
            return empty($rdata) ? true : $rdata;
        } catch (\Gini\BPM\Exception $e) {
            return ;
        }
    }
}
