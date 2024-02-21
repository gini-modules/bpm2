<?php
namespace Gini\BPM\Camunda720\Tests\TestTrait;
trait ProcessTrait
{
    public function createProcess($engine, $key, $data)
    {
        var_dump($data);
        return $engine->post('process-definition/key/'.$key.'/submit-form', $data);
    }

    public function startProcess($engine, $key, $id)
    {
        return $engine->post('process-definition/key/'.$key.'/start', [
            'caseInstanceId' => $id,
            'businessKey' => $key
        ]);
    }

    public function deleteProcess($engine, $key)
    {
        return $engine->delete('process-definition/key/'.$key);
    }

    public function getProcess($engine, $key)
    {
        return $engine->get('process-definition/key/'. $key);
    }
}