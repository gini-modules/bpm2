# Gini BPM 
旨在提供一个BPM工作流引擎的抽象层, 同时提供一些命令行工具

### 基础操作
##### 程序
```php
$engine = \Gini\BPM\Engine::of('camunda');
$instance1 = $engine->process('invoice')->start(['refNo'=>'01923019283']);
$instance2 = $engine->processInstance($instance1->id); // 'd1085971-882d-11e6-819e-0242ac112a06'
if ($instance->exists()) {
}
```

##### 命令行
```bash
gini bpm deployment create bpm=camunda test.bpmn
gini bpm process start bpm=camunda key=testProcess foo=bar
```

### 操作任务
##### 程序
```php
$o = $engine->searchTasks(['process'=>'order-review', 'group' => 'school-of-chemistry']);
$tasks = $engine->getTasks($o->token, 0, 10);

$task = $engine->task('1234-5678-9012');
$task->setAssignee('jia.huang');
$task->claim('jia.huang');
$task->unclaim();
$task->complete(['foo'=>'bar']);
```

##### 命令行
```bash
gini bpm task search bpm=camunda process=testProcess assignee=jia.huang
gini bpm task search bpm=camunda process=testProcess group=school-of-chemistry
gini bpm task complete bpm=camunda id=4d7f09c9-17ff-11e7-a73c-0242ac112a08 foo=bar
gini bpm task assign bpm=camunda id=4d7f09c9-17ff-11e7-a73c-0242ac112a08 to=jia.huang
gini bpm task claim bpm=camunda id=4d7f09c9-17ff-11e7-a73c-0242ac112a08 by=jia.huang
gini bpm task unclaim bpm=camunda id=4d7f09c9-17ff-11e7-a73c-0242ac112a08
```
