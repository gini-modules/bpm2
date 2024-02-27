<?php
namespace Gini\BPM\Camunda720\Tests\TestTrait;
trait UserTrait
{
    public function getUser($engine, $userId){
        return $engine->get('user/'.$userId.'/profile');
    }
    public function deleteUser($engine, $userId)
    {
        return $engine->delete('user/'.$userId);
    }

    public function createUser($engine, $data){
        return $engine->post('user/create', $data);
    }

    public function updateUser($engine, $userId, $data, $type='profile'){
        return $engine->put('user/'.$userId.'/' .$type, $data);
    }
}