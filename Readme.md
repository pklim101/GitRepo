## 介绍  
- GieRepo是可以用PHP代码来操作Git的类库。

## 使用方法
1. 引入类库
```php
require_once 'GitRepo.php';
```

2. 初始化类库
```php
$path = '~/test/';
$gitRepo = new GitRepo($path);
$gitRepo->repo_init($path);
```

3. Git各种操作
- $t = $gitRepo->status();
- $t = $gitRepo->getFirstLog();
- $t = $gitRepo->export("~/zz",'1d29449e01');
- $t = $gitRepo->list_branches();
- $t = $gitRepo->getGitLog('685acd5');
- $t = $gitRepo->log('%H||%h');')
