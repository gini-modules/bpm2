<?php

namespace Gini\BPM\Driver;

interface Group {
    public function create(array $vars);
    public function delete();
    public function update(array $vars);
    public function getMembers();
    public function addMember(array $vars);
    public function removeMember(array $vars);
}
