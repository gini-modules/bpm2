<?php

namespace Gini\BPM\Camunda;

class User implements \Gini\BPM\Driver\User
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
        $rdata = $this->camunda->get("user/$id/profile");
        if (isset($rdata['id'])) {
            foreach ($rdata as $key => $value) {
                $this->$key = $value;
            }
        }
    }

    /**
     * [create Create a new user.]
     * @param  array  $criteria [The array contains the following properties: id (Int), firstName (String), lastName (String) , email (String) and password (String). ]
     * @return [bool]           [true/false]
     */
    public function create(array $criteria) {
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

        $result = $this->camunda->post("user/create", $query);
        return empty($result) ? true : false;
    }

    /**
     * [delete Deletes a user by id]
     * @return [bool] [true/false]
     */
    public function delete() {
        $id = $this->id;
        if (!$id) return;

        $result = $this->camunda->delete("user/$id");
        return empty($result) ? true : false;
    }

    /**
     * [update Updates the profile information of an already existing user.]
     * @param  array  $criteria [The array contains the following properties:id (Int), firstName (String), lastName (String), email (String)]
     * @return [bool]           [true/false]
     */
    public function update(array $criteria) {
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

        $result = $this->camunda->put("user/$id/profile", $query);
        return empty($result) ? true : false;
    }

    /**
     * [changePassword Updates a user’s credentials (password).]
     * @param  [string] $password    [The password of the authenticated user who changes the password of the user ]
     * @param  [string] $newpassword [The user's new password.]
     * @return [bool]              [true/false]
     */
    public function changePassword($password, $newpassword) {
        $id = $this->id;
        if (!$id || !$password || !$newpassword) return;

        $query['password'] = $newpassword;
        $query['authenticatedUserPassword'] = $password;

        $result = $this->camunda->put("user/$id/credentials", $query);
        return empty($result) ? true : false;
    }
}

