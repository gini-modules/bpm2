<?php

namespace Gini\Controller\CLI\BPM;

class Group extends \Gini\Controller\CLI
{
    use \Gini\Controller\CLI\BPM\Base;
    public function actionGetGroups($args)
    {
        $opt = $this->getOpt($args);
        $engine = $this->getEngine($opt);

        $o = $engine->searchGroups($opt);
        $groups = $engine->getGroups($o->token);

        var_dump($groups);
    }

    public function actionGetGroup($args)
    {
        $opt = $this->getOpt($args);
        $engine = $this->getEngine($opt);

        $id = $opt['id'];
        $group = $engine->getGroup($id);
        error_log(print_r($group,true));
    }

    public function actionAddGroup($args)
    {
        $opt = $this->getOpt($args);
        $engine = $this->getEngine($opt);

        $opt['name'] = '化工学院';
        $group = $engine->addGroup($opt);
        var_dump($group);
    }

    public function actionDeleteGroup($args)
    {
        $opt = $this->getOpt($args);
        $engine = $this->getEngine($opt);

        $id = $opt['id'];
        $result = $engine->deleteGroup($id);
        var_dump($result);
    }

    public function actionUpdateGroup($args)
    {
        $opt = $this->getOpt($args);
        $engine = $this->getEngine($opt);
        $process = $engine->process($opt['bpm']);

        $id = $opt['id'];
        $opt['name'] = '化学学院';
        $result = $process->updateGroup($id, $opt);
        var_dump($result);
    }

    public function actionGetGroupMembers($args)
    {
        $opt = $this->getOpt($args);
        $engine = $this->getEngine($opt);

        $o = $engine->searchGroupMembers($opt);
        $group_members = $engine->getGroupMembers($o->token);
        var_dump($group_members);
    }

    public function actionAddMember($args)
    {
        $opt = $this->getOpt($args);
        $engine = $this->getEngine($opt);

        $user = $engine->addMember($opt);
        var_dump($user);
    }

    public function actionRemoveMember($args)
    {
        $opt = $this->getOpt($args);
        $engine = $this->getEngine($opt);

        $user = $engine->removeMember($opt);
        var_dump($user);
    }

    public function actionGetUsers($args)
    {
        $opt = $this->getOpt($args);
        $engine = $this->getEngine($opt);

        $o = $engine->searchUsers($opt);
        $users = $engine->getUsers($o->token);
        var_dump($users);
    }

    public function actionAddUser($args)
    {
        $opt = $this->getOpt($args);
        $engine = $this->getEngine($opt);

        $result = $engine->addUser($opt);
        var_dump($result);
    }

    public function actionDeleteUser($args)
    {
        $opt = $this->getOpt($args);
        $engine = $this->getEngine($opt);

        $id = $opt['id'];
        $result = $engine->deleteUser($id);
        var_dump($result);
    }
}
