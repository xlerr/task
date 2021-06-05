YII2 TASK
===========

### 安装

```shell
composer require xlerr/task
```

### 配置

```php
// console/config/main.php
'controllerMap' => [
    'task' => \xlerr\task\console\TaskController::class,
],

// backend/config/main.php
'modules' => [
    'task' => \xlerr\task\controllers\TaskController::class,
],
```

### 管理页面

启动后台服务
```shell
php -S 127.0.0.1:9900 -t backend/web
```

服务启动后, 访问 `http://127.0.0.1:9900/task` 打开task管理页面

控制台调用`./yii task/process-all`执行所有任务