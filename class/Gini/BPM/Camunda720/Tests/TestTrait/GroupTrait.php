<?php
namespace Gini\BPM\Camunda720\Tests\TestTrait;
trait GroupTrait
{
    public function getGroup($engine, $groupId){
        return $engine->get('group/'.$groupId);
    }
    public function deleteGroup($engine, $groupId)
    {
        return $engine->delete('group/'.$groupId);
    }

    public function createGroup($engine, $data){
        $engine->post('group/create', $data);
    }

    public function updateGroup($engine, $groupId, $data){
        $engine->put('group/'.$groupId, $data);
    }
}