<?php

/*
 * GitRepo.php
 *
 * @desc 一个对Git仓库实现操作的API库。
 *
 * @package     GitRepo.php
 * @version     v_0.1
 * @author      JSON
 * @copyright   Copyright 2017
 * @URL         http://github.com
 */

if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) die('Bad load order');


class Base {

    /**
     * Git的执行路径 
     *
     * @var string
     */
    protected static $bin = '/usr/bin/git';

    /**
     * 设置Git执行路径 
     *
     * @param string $path 执行路径 
     */
    public static function set_bin($path) {
        self::$bin = $path;
    }

    /**
     * 获取Git执行路径 
     */
    public static function get_bin() {
        return self::$bin;
    }

    /**
     * windows环境的git执行路径设置方式。
     * 注意：需要先在环境变量里将git执行路径添加到path里。或者直接调用self::set_bin('D:\git'). 
     */
    public static function windows_mode() {
        self::set_bin('git');
    }

	/**
	 * Sets custom environment options for calling Git
	 * @desc    设置调用git的环境变量.
     *
	 * @param string key
	 * @param string value
	 */
	public function setenv($key, $value) {
		$this->envopts[$key] = $value;
	}

	/**
	 * 采用命令行方式执行. 
	 *
	 * @access  protected
	 * @param   string  @command  shell命令 
	 * @return  string
	 */
	protected function run_command($cwd, $command) {
		$descriptorspec = array(
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w'),
		);
		$pipes = array();
		 
		if(count($_ENV) === 0) {
			$env = NULL;
			foreach($this->envopts as $k => $v) {
				putenv(sprintf("%s=%s",$k,$v));//用putenv设置环境变量.
			}
		} else {
			$env = array_merge($_ENV, $this->envopts);
		}
		$resource = proc_open($command, $descriptorspec, $pipes, $cwd, $env);

		$stdout = stream_get_contents($pipes[1]);
		$stderr = stream_get_contents($pipes[2]);
		foreach ($pipes as $pipe) {
			fclose($pipe); //切记：在调用 proc_close 之前关闭所有的管道以避免死锁。
		}

		$status = trim(proc_close($resource));
		if ($status) throw new Exception('stderr:'.$stderr . "\n" . 'stdout'.$stdout); 

		return $stdout;
	}

    /**
     * @desc    删除指定目录.
     *          删除目录的条件：1.目录必须存在；2.必须是空目录.
     *
     *
     */
    public function removeDir($dirName) { 
        if(!is_dir($dirName)) 
        { 
            return false; 
        } 
        $handle = @opendir($dirName); 
        while(($file = @readdir($handle)) !== false) 
        { 
            if($file != '.' && $file != '..') 
            { 
                $dir = $dirName . '/' . $file; 
                is_dir($dir) ? removeDir($dir) : @unlink($dir); 
            } 
        } 
        closedir($handle); 
          
        return rmdir($dirName) ; 
    }

}


/**
 * Git仓库类库 
 *
 * @desc 对仓库的初始化、克隆、检出、导出代码等操作。 
 *
 * @class  GitRepo
 */

class GitRepo extends Base {

	protected $local_repo_path = null;
	protected $bare = false;
	protected $is_repo= false;
	protected $envopts = array();


###################################### 仓库相关-start #############################################
	/**
	 * @desc    构造函数 
	 *
	 * @access  public
	 * @return  void
	 */
	public function __construct($local_repo_path) {
        //如果仓库路径存在，则判断该路径对应的是什么仓库
        if (file_exists($local_repo_path) && is_dir($local_repo_path)) {
            if (file_exists($local_repo_path."/.git")) {
                $this->local_repo_path = $local_repo_path;
                $this->is_repo = true;
                $this->bare = false;
            } else if (is_file($local_repo_path."/config")) {
                $parse_ini = parse_ini_file($local_repo_path."/config");
                if ($parse_ini['bare']) {
                    $this->local_repo_path = $local_repo_path;
                    $this->is_repo = true;
                    $this->bare = true;
                }
            } else {
                    $this->local_repo_path = $local_repo_path;
                    $this->is_repo =true;
                    $this->bare = true;
            }
        } else {
            throw new Exception('"'.$local_repo_path.'" 必须是一个目录！');
        }

	}


	/**
	 * 执行一个git命令. 
	 *
	 *
	 * @access  public
	 * @param   string  $command    git的shell命令. 
	 * @return  string
	 */
	public function run($command) {
		return $this->run_command($this->local_repo_path, self::get_bin()." ".$command);
	}


	/**
	 * @desc    初始化本地仓库地址-创建本地仓库:git init. 
	 *
	 * @access  public
	 * @param   string  本地仓库地址，如: /tmp/test 
	 * @param   bool    是否新创建 
	 * @return  void
	 */
	public function repo_init($local_repo_path) {
        if (!file_exists($local_repo_path)){
            if ($parent = realpath(dirname($local_repo_path))) {
                mkdir($local_repo_path);
                $this->local_repo_path = $local_repo_path;
                $this->run('init');
                $this->is_repo = true;
                $this->bare = false;
            } else {
                throw new Exception('"'.$local_repo_path.'" 应该在一个存在的目录下创建创库文件夹！');
            }
        } else {
            throw new Exception('"'.$local_repo_path.'" 目录已经存在！');
        }
	}


    public function getRepoType($local_repo_path){
        //如果仓库路径存在，则判断该路径对应的是什么仓库
        if (file_exists($local_repo_path) && is_dir($local_repo_path)) {
            if (file_exists($local_repo_path."/.git")) {
                $this->local_repo_path = $local_repo_path;
                $this->is_repo = true;
                $this->bare = false;
            } else if (is_file($local_repo_path."/config")) {
                $parse_ini = parse_ini_file($local_repo_path."/config");
                if ($parse_ini['bare']) {
                    $this->local_repo_path = $local_repo_path;
                    $this->is_repo = true;
                    $this->bare = true;
                }
            } else {
                    $this->local_repo_path = $local_repo_path;
                    $this->is_repo =true;
                    $this->bare = true;
            }
        } else {
            throw new Exception('"'.$local_repo_path.'" 必须是一个目录！');
        }
        
    }


	/**
	 *  command: git status 
	 *
	 * @desc    查看git仓库状态.
	 * @access  public
	 * @param   bool  return string with <br />
	 * @return string
	 */
	public function status($html = false) {
		$msg = $this->run("status");
		if ($html == true) {
			$msg = str_replace("\n", "<br />", $msg);
		}
		return $msg;
	}

	
	/**
	 * @desc    获得git仓库的路径(比如.git所在的目录). 
     *
	 * @access  public
	 * @return  string
	 */
	public function git_repo_path() {
        if ($this->bare) {
            return $this->local_repo_path;
        } else if (is_dir($this->local_repo_path."/.git")) {
            return $this->local_repo_path."/.git";
        } else {
            throw new Exception($this->local_repo_path.' 不是一个git仓库！');
        }
	}


	/**
     * @desc    从本地工作目录移除未跟踪的文件。
     *
	 * @access  public
	 * @param   bool    是否移除未跟踪的目录. 
	 * @param   bool    是否强制删除. 
	 * @return  string
	 */
	public function clean($dirs = false, $force = false) {
		return $this->run("clean".(($force) ? " -f" : "").(($dirs) ? " -d" : ""));
	}


	/**
	 * @desc    设置项目描述
     *
	 * @param   string  $desc
	 */
	public function set_description($desc) {
		$path = $this->git_directory_path();
		file_put_contents($path."/description", $new);
	}

	/**
	 * @desc    获取项目描述.
     *
	 * @return  string
	 */
	public function get_description() {
		$path = $this->git_directory_path();
		return file_get_contents($path."/description");
	}



###################################### 仓库相关-end #############################################




###################################### clone-start #############################################


	/**
	 *
	 * @desc    从git仓库克隆到本地的另外一个目录.
	 * @access  public
	 * @param   string  $target 目标目录. 
	 * @return  string
	 */
	public function clone_to($target) {
		return $this->run("clone --local ".$this->local_repo_path." $target");
	}

	/**
	 *
	 * @desc    从指定的本地仓库克隆到当前目录.
	 * @access  public
	 * @param   string  $source 源仓库. 
	 * @return  string
	 */
	public function clone_from($source) {
		return $this->run("clone --local $source ".$this->local_repo_path);
	}

	/**
     * 用法：git clone --reference test/ git@tianyu.src.net:tom/xx.git test2/
	 * 参数--reference：当clone的时候，不用从网络上拉取object了，但是前提是本地也得有这个对应的仓库。
	 *
     * @desc    从远程克隆一个仓库到指定当前目录.
	 * @access  public
	 * @param   string  $source 远程git仓库url. 
	 * @param   string  $reference  引用路径，也就是本地已经存在仓库的路径. 
	 * @return  string
	 */
	public function clone_remote($source, $reference) {
		return $this->run("clone $reference $source ".$this->local_repo_path);
	}

###################################### clone-end #############################################


#################################### branch-start ############################################

	/**
	 * @desc    创建分支.
	 *
	 * @access  public
	 * @param   string  $branch 分支名. 
	 * @return  string
	 */
	public function create_branch($branch) {
		return $this->run("branch " . escapeshellarg($branch));
	}

	/**
     * @desc    删除分支.
     *
	 * @command  git branch -[d|D]
	 *
	 * @access  public
	 * @param   string  $branch 分支名
	 * @param   bool    $force  是否强制删除.-D:强制删除;-d:如果该分支没有被merge则不能被删除.
	 * @return  string
	 */
	public function delete_branch($branch, $force = false) {
		return $this->run("branch ".(($force) ? '-D' : '-d')." $branch");
	}

	/**
	 * @desc    列出分支.
     *
	 * @access  public
	 * @param   bool    是否在活动分支上保持星号. 
	 * @return  array
	 */
	public function list_branches($keep_asterisk = false) {
		$branchArray = explode("\n", $this->run("branch"));
		foreach($branchArray as $i => &$branch) {
			$branch = trim($branch);
			if (! $keep_asterisk) {
				$branch = str_replace("* ", "", $branch);
			}
			if ($branch == "") {
				unset($branchArray[$i]);
			}
		}
		return $branchArray;
	}

	/**
	 *
     * @desc    列出远程分支
     *
     * @command git branch -r
	 *
	 * @access  public
	 * @return  array
	 */
	public function list_remote_branches() {
		$branchArray = explode("\n", $this->run("branch -r"));
		foreach($branchArray as $i => &$branch) {
			$branch = trim($branch);
			if ($branch == "" || strpos($branch, 'HEAD -> ') !== false) {
				unset($branchArray[$i]);
			}
		}
		return $branchArray;
	}

	/**
	 * @desc    获取激活分支.
     *
	 * @access  public
	 * @param   bool    是否在分支名上保留型号. 
	 * @return  string
	 */
	public function active_branch($keep_asterisk = false) {
		$branchArray = $this->list_branches(true);
		$active_branch = preg_grep("/^\*/", $branchArray);
		reset($active_branch);
		if ($keep_asterisk) {
			return current($active_branch);
		} else {
			return str_replace("* ", "", current($active_branch));
		}
	}

	/**
	 * @desc    检出分支.
	 * @access  public
	 * @param   string  $branch 被检出的分支名. 
	 * @return  string
	 */
	public function checkout($branch) {
		return $this->run("checkout " . escapeshellarg($branch));
	}


	/**
	 * @desc    将$branch分支合并到当前分支.
	 * @access  public
	 * @param   string  $branch 被合并的分支名.
	 * @return  string
	 */
	public function merge($branch) {
		return $this->run("merge " . escapeshellarg($branch) . " --no-ff");
	}


##################################### branch-end #############################################


##################################### 更新-start #############################################


	/**
	 * @desc    在当前分支上fetch.
	 * @access  public
	 * @return  string
	 */
	public function fetch() {
		return $this->run("fetch");
	}

	/**
	 *
	 * @desc git pull命令的作用是，取回远程主机某个分支的更新，再与本地的指定分支合并.
     *
     * @command git pull <远程主机名> <远程分支名>:<本地分支名>
	 * @param   string  $origin 远程主机名
	 * @param   string  $remote 远程分支名
	 * @param   string  $local  本地分支名
	 * @return  string
	 */
	public function pull($origin = "", $remoteBranch = "", $localBranch="") {
	    if( strlen($remoteBranch)==0 ){
	    	throw new Exception("remote branch muste be specified!"); 
	    }
	    if( strlen($localBranch)>0 ) {
	        $localBranch = ':'.$localBranch;
	    }
		return $this->run("pull $origin $remoteBranch $localBranch");
	}

	/**
	 * @desc    将本地指定分支推送到远程分支上.
     *
     * @command git push origin master [push的时候，会找到远程同名分支，如果不存在则创建.]
     *
	 * @param   string  $remote
	 * @param   string  $branch
	 * @return string
	 */
	public function push($remote = "", $branch = "") {
		return $this->run("push $remote $branch");
	}

	
	/**
	 * @desc 导出指定版本号的所有文件到指定目录下.
	 * 完整格式：git checkout -b 分支名 版本号(长/短版本号均可)
     *
	 * @param string $dir  导出到的目录名
	 * @param string $version  git版本号
	 * @return string
	 */
	public function export_all($dir, $version) {
	    if( strlen($dir)==0 ){
	        throw new Exception("export dictory must be specified!");
	    }
	    if( strlen($version)==0 ) {
	        throw new Exception("export version number must be specified!");
	    }
        if(file_exists($dir)){
            $this->removeDir($dir);
        }
        if(in_array($version, $this->list_branches())){
	        $this->run("checkout master");
	        $this->run("branch -D {$version}");
        }
	    //【回滚上线之前版本容易出问题】如果该分支存在，则fatal: git checkout: branch 5858b7b already exists
	    $this->run("checkout -b {$version} {$version}");        //创建名为{$version}的分支，且检出该分支.
        $this->run("checkout-index -a -f --prefix={$dir}/");    //Copy files from the index to the working tree.   这里--prefix里的目录必须加/.
        if(in_array($version, $this->list_branches())){
	        $this->run("checkout master");
	        $this->run("branch -D {$version}");
        }
	}

##################################### 更新-end #############################################

##################################### 提交撤销-start #############################################
	/**
	 * @desc    将修改添加到暂存区域staged.
     *
     * @command git add
     *
	 * @access  public
	 * @param   mixed   被添加的文件或者通配符 
	 * @return  string
	 */
	public function add($files = "*") {
		if (is_array($files)) {
			$files = '"'.implode('" "', $files).'"';
		}
		return $this->run("add $files -v");
	}


	/**
	 * @desc    从暂存区域提交到git仓库.
     *
     * @command git commit -m "msg"
     *
	 * @access  public
	 * @param   string  添加的备注信息 
	 * @param   boolean -a参数：如果是修改的或者删除了文件后，不用执行git add，而直接git commit -a即可，-a表示自动暂存文件；
                                但是如果是新增文件则不行. 
	 * @return  string
	 */
	public function commit($message = "", $commit_all = true) {
		$flags = $commit_all ? '-av' : '-v';
		return $this->run("commit ".$flags." -m ".escapeshellarg($message));
	}


	/**
	 * @desc    从工作目录和暂存区域删除文件.
     *
     * @command git rm a.log
     *
	 * @access  public
	 * @param   mixed   被删除的文件 
	 * @param   bool    用参数--cached表示仅仅从暂存区删除. 
	 * @return  string
	 */
	public function rm($files = "*", $cached = false) {
		if (is_array($files)) {
			$files = '"'.implode('" "', $files).'"';
		}
		return $this->run("rm ".($cached ? '--cached ' : '').$files);
	}


	/**
	 * @desc   撤销工作目录中所有未提交文件的修改内容或者回滚到指定版本. 
     *
     * @command git reset --hard HEAD
     *
	 * @access  public
	 * @param   string  指定撤销到的版本.HEAD：将当前修改但未提交的内容都撤销；版本号：撤销到指定的这个版本. 
	 * @return  string  "HEAD is now at 685acd5 备注" 
	 */
	public function reset($version) {
		return $this->run("git reset --hard ".$version);
	}


	/**
	 * @desc  撤销指定的提交，撤销的记录也会成为log的一行commit.注意：可能有冲突. 
     *
     * @command git revert <commit> 
     *
	 * @access  public
	 * @param   string  指定撤销到的版本.
	 * @return  string  失败的时候返回:"Automatic revert failed." 
	 */
	public function revert($version) {
		return $this->run("git revert ".$version);
	}


##################################### 提交撤销-end #############################################


################################### 里程碑-start ###########################################

	/**
	 * @desc    在当前分支上打一个新tag.
	 * @param   string  $tag    tag名称
	 * @param   string  $message    该tag的备注.
	 * @return  string
	 */
	public function add_tag($tag, $message = null) {
		if (is_null($message)) {
			$message = $tag;
		}
		return $this->run("tag -a $tag -m " . escapeshellarg($message));
	}

	/**
	 * @desc    过得所有的tags列表.
	 * @param   string  $pattern    通过通配符匹配对应的tag. 格式：git tag -l 'v*.1'[如果有-l但是没有pattern，则列出所有的tag] 
	 * @access	public
	 * @param	string	$pattern	Shell wildcard pattern to match tags against.
	 * @return	array				Available repository tags.
	 */
	public function list_tags($pattern = null) {
		$tagArray = explode("\n", $this->run("tag -l $pattern"));
		foreach ($tagArray as $i => &$tag) {
			$tag = trim($tag);
			if (empty($tag)) {
				unset($tagArray[$i]);
			}
		}

		return $tagArray;
	}

################################### 里程碑-end ###########################################


################################### 日志-start ###########################################


	/**
	 * @desc 获得指定版本号对应的文件到指定目录下.
     *
	 * @param string $version  git版本号
	 * @return string
	 */
	public function get_version_files($version) {
        $gitLog = $this->getGitLog($version, 1);
        return $gitLog[0]['paths'];
	}

	/**
	 * 
	 * @desc    git日志.
	 * @param   string  $format
	 * @return  string
	 */
	public function log($format = null, $num=3) {
		if ($format === null)
			return $this->run("log -{$num}");
		else
			return $this->run("log -{$num} --pretty=format:'{$format}'");  //注意：这里$format要用引号包含起来.
	}


	/**
	 * 获取最新一条log日志 
	 *
	 *
	 * @access  public
	 * @param   string  $command    git的shell命令. 
	 * @return  string
	 */
	public function getFirstLog() {
		return $this->run("log -1 --abbrev-commit --pretty=oneline");
	}

    /**
     * @desc    返回和php里svn库的日志一样的格式。
     * 
     * @command git log --name-status --pretty=format:'%h||%an||%s||%cd||%d' --abbrev-commit'
     *
     * @param   string  $version    版本号
     * @param   number  $num    获得日志的条数[从最近的日志往前算]
     * @return  array  日志的数组形式. 
     */
     public function getGitLog($version='', $num=3){
        $gitLog = $this->run("log {$version} -{$num} --name-status --pretty=format:'%h||%an||%s||%cd||%d' --abbrev-commit");
        $gitLogArr = explode("\n\n",$gitLog);
        $verInfoList= array();
        foreach($gitLogArr as $v){
            $t= explode("\n",$v,2);
            $gitLogSingle= explode("||",$t[0]);
            $files = explode("\n",$t[1]);
            $filesArr = array();
            foreach($files as $i=>$file){
                if(empty($file)) continue;
                $fileParams = explode("\t",$file);
                $filesArr[$i]['action']   = $fileParams[0];
                $filesArr[$i]['path']     = $fileParams[1];
            }
            array_push($verInfoList,
                array(
                    'rev'=>$gitLogSingle[0],
                    'author'=>$gitLogSingle[1],
                    'msg'=>$gitLogSingle[2],
                    'date'=>$gitLogSingle[3],
                    'paths'=>$filesArr,
                    )
            );
        }
        return $verInfoList;
    }


################################### 日志-end ###########################################


}

?>
