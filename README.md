# Gini BPM 
旨在提供一个BPM工作流引擎的抽象层

```php
$engine = \Gini\BPM\Engine::of('camunda');
$instance = $engine->process('invoice')->start(['refNo'=>'01923019283']);
$engine->task('1234-5678-9012')->complete();
if ($engine->processInstance('d1085971-882d-11e6-819e-0242ac112a06')->isStarted()) {
}
```