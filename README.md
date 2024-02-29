# Gini BPM
旨在提供一个BPM工作流引擎的抽象层, 同时提供一些命令行工具

## Camunda 7.20 支持
如果您使用的是 Camunda 7.20 以上的版本， 你只需要在使用的时候调用 Camunda720 驱动就好， 如下所示
```php
$engine = \Gini\BPM\Engine::of('Camunda720')
```
如果您使用 Camunda720 驱动，您需要注意以下几点：
1. Camunda7.20 服务的 bpm id 不支持特殊字符分割， 您需要修改 BPMN 流程图中 General 下的 ID 表单项，及代码中的相关配置
2. Camunda7.20 的验证方式和以前有所变化，之前是通过 cookie 进行验证，现在是通过 token 验证，默认使用了 HTTP Basic Authentication，你也可以通过重写 Gini\BPM\Camunda720\Engine auth 方法的方式使用您自己喜欢的验证方式
```php
  protected function auth($http, $username, $password){
        $token = 'Basic '.base64_encode($username.'_'.$password);
        $http->header('Authorization', $token);
        return $http;
  }
```


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
//search tasks
$o = $engine->searchTasks(['process'=>'order-review', 'group' => 'school-of-chemistry']);
$tasks = $engine->getTasks($o->token, 0, 10);

//get a task
$task = $engine->task('1234-5678-9012');
$task->setAssignee('jia.huang');
$task->claim('jia.huang');
$task->unclaim();

//complete a task
$task->complete(['foo'=>'bar']);

//add a comment for a task
$task->addComment('comment');

//get comments for a task
$task->getComments()
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

###操作组
##### 程序
```php
//search groups
$o = $engine->searchGroups(['type' => 'order-review-process']);
$groups = $engine->getGroups($o->token);

//create a group
$engine->group()->create(['id' => 'school-of-chemistry', 'name' => '化工学院', type => 'order-review-process']);

//get group by ID
$group = $engine->group('school-of-chemistry');

//update group
$group->update(['id' => 'school-of-chemistry', 'name' => '化工学院', type => 'order-review-process']);

//delete group
$group->delete();

//get group's members
$group->getMembers();

//add a member to the group
$group->addMember('user_id');

//remove a member from a group
$group->removeMember('user_id');
```

###操作用户
##### 程序
```php
//search users
$o = $engine->searchUsers(['name' => 'Demo']);
$users = $engine->getUsers($o->token);

//create a user
$engine->user()->create(['id' => 1, 'firstName' => 'demo', 'lastName' => 'Genee', 'email' => 'demo@geneegroup.com', 'password' => 'password']);

//get user by ID
$user = $engine->user(1);

//update user
$user->update(['id' => 1, 'firstName' => 'demo', 'lastName' => 'Genee', 'email' => 'demo@geneegroup.com']);

//delete user
$user->delete();

//change user's password
$user->changePassword('password', 'newpassword');
```

