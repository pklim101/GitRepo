<?php
require_once 'GitRepo.php';
$path = '~/test/';
$gitRepo = new GitRepo($path);
//$gitRepo->repo_init($path);
//$t = $gitRepo->status();
//$t = $gitRepo->getFirstLog();
//$t = $gitRepo->export("~/zz",'1d29449e01');
//$t = $gitRepo->list_branches();
//$t = $gitRepo->getGitLog('685acd5');
$t = $gitRepo->log('%H||%h');
var_dump($t);
