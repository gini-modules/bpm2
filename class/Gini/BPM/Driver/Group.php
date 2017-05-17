<?php

namespace Gini\BPM\Driver;

interface Group {
    public function getMembers();
    public function addMember(array $vars);
    public function removeMember(array $vars);
}
