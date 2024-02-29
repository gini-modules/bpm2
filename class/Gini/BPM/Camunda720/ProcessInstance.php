<?php

namespace Gini\BPM\Camunda720;

/**
 * yue.cui 创建于 2024/2/25
 */
class ProcessInstance extends \Gini\BPM\Camunda\ProcessInstance
{
    private $camunda;
    private $id;
    private $data;
    private function _fetchInstance() {
        if (!$this->data) {
            $id = $this->id;
            try {
                $this->data = $this->camunda->get("history/process-instance/$id");
            } catch (\Gini\BPM\Exception $e) {
                $this->data = [];
            }
        }
    }
    private function _makeQuery($name)
    {
        $pos = strpos($name, '=');
        if (!$pos) {
            if ($pos === 0) {
                $val = substr($name, $pos+1);
                return ['pattern' => $val];
            } else {
                return ['pattern' => $name];
            }
        }
        else {
            $val = substr($name, $pos+1);
            $pos--;
            $opt = $name[$pos].'=';

            switch ($opt) {
                case '^=': {
                    $pattern = $val.'%';
                }
                    break;

                case '$=': {
                    $pattern = '%'.$val;
                }
                    break;

                case '*=': {
                    $pattern = '%'.$val.'%';
                }
                    break;
            }

            return [
                'like' => true,
                'pattern' => $pattern
            ];
        }
    }
}