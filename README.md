# Gini BPM 
旨在提供一个BPM工作流引擎的抽象层

```php
$engine = \Gini\BPM\Engine::of('camunda');
$instance1 = $engine->process('invoice')->start(['refNo'=>'01923019283']);
$instance2 = $engine->processInstance($instance1->id); // 'd1085971-882d-11e6-819e-0242ac112a06'
if ($instance->exists()) {
}

$task = $engine->task('1234-5678-9012');
$task->complete();

$o = $engine->searchTasks(['processDefinitionKey'=>'invoice', 'candidateGroups' => ['sbc']]);
$tasks = $engine->getTasks($o->token, 0, 10);

```