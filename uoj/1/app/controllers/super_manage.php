<?php
requirePHPLib('form');
requirePHPLib('judger');

if ($myUser == null || !isSuperUser($myUser)) {
    become403Page();
}

$user_form = new UOJForm('user');
$user_form->addInput('username', 'text', '用户名', '',
    function ($username) {
        if (!validateUsername($username)) {
            return '用户名不合法';
        }
        if (!queryUser($username)) {
            return '用户不存在';
        }
        return '';
    },
    null
);
$options = array(
    'ban' => '封禁',
    'deblocking' => '解封',
    'login' => '登录'
);
$user_form->addSelect('op-type', $options, '操作类型', '');
$user_form->handle = function() {
    global $user_form;

    $username = $_POST['username'];
    switch ($_POST['op-type']) {
        case 'ban':
            DB::update("update user_info set usergroup = 'B' where username = '{$username}'");
            break;
        case 'deblocking':
            DB::update("update user_info set usergroup = 'U' where username = '{$username}'");
            break;
        case 'login':
            Auth::login($username);
            $user_form->succ_href = "/";
            break;
    }
};
$user_form->runAtServer();

$blog_link_contests = new UOJForm('blog_link_contests');
$blog_link_contests->addInput('blog_id', 'text', '博客ID', '',
    function ($x) {
        if (!validateUInt($x)) return 'ID不合法';
        if (!queryBlog($x)) return '博客不存在';
        return '';
    },
    null
);
$blog_link_contests->addInput('contest_id', 'text', '比赛ID', '',
    function ($x) {
        if (!validateUInt($x)) return 'ID不合法';
        if (!queryContest($x)) return '比赛不存在';
        return '';
    },
    null
);
$blog_link_contests->addInput('title', 'text', '标题', '',
    function ($x) {
        return '';
    },
    null
);
$options = array(
    'add' => '添加',
    'del' => '删除'
);
$blog_link_contests->addSelect('op-type', $options, '操作类型', '');
$blog_link_contests->handle = function() {
    $blog_id = $_POST['blog_id'];
    $contest_id = $_POST['contest_id'];
    $str = mysql_fetch_array(mysql_query("select * from contests where id='${contest_id}'"));
    $all_config = json_decode($str['extra_config'], true);
    $config = $all_config['links'];

    $n = count($config);

    if ($_POST['op-type'] == 'add') {
        $row = array();
        $row[0] = $_POST['title'];
        $row[1] = $blog_id;
        $config[$n] = $row;
    }
    if ($_POST['op-type'] == 'del') {
        for ($i = 0; $i < $n; $i++)
            if ($config[$i][1] == $blog_id) {
                $config[$i] = $config[$n - 1];
                unset($config[$n - 1]);
                break;
            }
    }

    $all_config['links'] = $config;
    $str = json_encode($all_config);
    $str = mysql_real_escape_string($str);
    mysql_query("update contests set extra_config='${str}' where id='${contest_id}'");
};
$blog_link_contests->runAtServer();

$blog_link_index = new UOJForm('blog_link_index');
$blog_link_index->addInput('blog_id2', 'text', '博客ID', '',
    function ($x) {
        if (!validateUInt($x)) return 'ID不合法';
        if (!queryBlog($x)) return '博客不存在';
        return '';
    },
    null
);
$blog_link_index->addInput('blog_level', 'text', '置顶级别（删除不用填）', '0',
    function ($x) {
        if (!validateUInt($x)) return '数字不合法';
        if ($x > 3) return '该级别不存在';
        return '';
    },
    null
);
$options = array(
    'add' => '添加',
    'del' => '删除'
);
$blog_link_index->addSelect('op-type2', $options, '操作类型', '');
$blog_link_index->handle = function() {
    $blog_id = $_POST['blog_id2'];
    $blog_level = $_POST['blog_level'];
    if ($_POST['op-type2'] == 'add') {
        if (DB::selectFirst("select * from important_blogs where blog_id = {$blog_id}")) {
            DB::update("update important_blogs set level = {$blog_level} where blog_id = {$blog_id}");
        } else {
            DB::insert("insert into important_blogs (blog_id, level) values ({$blog_id}, {$blog_level})");
        }
    }
    if ($_POST['op-type2'] == 'del') {
        DB::delete("delete from important_blogs where blog_id = {$blog_id}");
    }
};
$blog_link_index->runAtServer();

$blog_deleter = new UOJForm('blog_deleter');
$blog_deleter->addInput('blog_del_id', 'text', '博客ID', '',
    function ($x) {
        if (!validateUInt($x)) {
            return 'ID不合法';
        }
        if (!queryBlog($x)) {
            return '博客不存在';
        }
        return '';
    },
    null
);
$blog_deleter->handle = function() {
    deleteBlog($_POST['blog_del_id']);
};
$blog_deleter->runAtServer();

$contest_submissions_deleter = new UOJForm('contest_submissions');
$contest_submissions_deleter->addInput('contest_id', 'text', '比赛ID', '',
    function ($x) {
        if (!validateUInt($x)) {
            return 'ID不合法';
        }
        if (!queryContest($x)) {
            return '博客不存在';
        }
        return '';
    },
    null
);
$contest_submissions_deleter->handle = function() {
    $contest = queryContest($_POST['contest_id']);
    genMoreContestInfo($contest);

    $contest_problems = DB::selectAll("select problem_id from contests_problems where contest_id = {$contest['id']}");
    foreach ($contest_problems as $problem) {
        $submissions = DB::selectAll("select * from submissions where problem_id = {$problem['problem_id']} and submit_time < '{$contest['start_time_str']}'");
        foreach ($submissions as $submission) {
            $content = json_decode($submission['content'], true);
            unlink(UOJContext::storagePath().$content['file_name']);
            DB::delete("delete from submissions where id = {$submission['id']}");
            updateBestACSubmissions($submission['submitter'], $submission['problem_id']);
        }
    }
};
$contest_submissions_deleter->runAtServer();

$custom_test_deleter = new UOJForm('custom_test_deleter');
$custom_test_deleter->addInput('last', 'text', '删除末尾记录', '5',
    function ($x, &$vdata) {
        if (!validateUInt($x)) {
            return '不合法';
        }
        $vdata['last'] = $x;
        return '';
    },
    null
);
$custom_test_deleter->handle = function(&$vdata) {
    $all = DB::selectAll("select * from custom_test_submissions order by id asc limit {$vdata['last']}");
    foreach ($all as $submission) {
        $content = json_decode($submission['content'], true);
        unlink(UOJContext::storagePath().$content['file_name']);
    }
    DB::delete("delete from custom_test_submissions order by id asc limit {$vdata['last']}");
};
$custom_test_deleter->runAtServer();

$banlist_cols = array('username', 'usergroup');
$banlist_config = array();
$banlist_header_row = <<<EOD
	<tr>
		<th>用户名</th>
	</tr>
EOD;
$banlist_print_row = function($row) {
    $hislink = getUserLink($row['username']);
    echo <<<EOD
			<tr>
				<td>${hislink}</td>
			</tr>
EOD;
};



$group_form =new UOJForm("group_form");
//查询结果处理
$query_group="";//cookie中上次查询的组
$query_user="";//cookie中上次查询的组的memeber的信息
$query_group_state="all";
$group_form_error_msg="";
if(isset($_GET)) {
    if (isset($_GET['group_query'])) {
        $query_group = $_GET['group_query'];
        $query_group_state = $_GET['group_query_state'];
        if($query_group_state=="all")
            $result= DB::selectAll("select * from group_info where group_name='{$query_group}'");
        else
            $result= DB::selectAll("select * from group_info where group_name='{$query_group}' and state='{$query_group_state}'");
        foreach ($result as $item) {
            $query_text.=$item['username']."\n";
        }
        if(count($result)==0){
            if($query_group_state=="all")
                $group_form_error_msg="{$query_group}组没有任何用户";
            if($query_group_state=="waiting")
                $group_form_error_msg="{$query_group}组没有任何待授权用户";
            if($query_group_state=="all")
                $group_form_error_msg="{$query_group}组没有任何群组中用户";
        }
    }
}
$group_form->addSelect("group-operation",array(
    'query'=> '查询',
    'add' => '添加',
    'del' => '删除',
    'modify'=>'修改权限',
    'create'=>'创建群组'
),'操作类型','query');
$all_groups=array();
$all_groups[""]="";
foreach (DB::selectAll("select * from group_description") as $each){
    $all_groups[$each['group_name']]=$each['group_name'];
}
$group_form->addSelect("group_select",$all_groups,"选择群组",$query_group);
$group_form->addSelect("group_query_state",array(
    'all'=>"全部",
    'in'=>"在群组中",
    'waiting'=>"等待验证"
),"状态筛选",$query_group_state);
$group_form->addSelect("group_is_admin",array(
    'yes'=>"群组管理员",
    'no'=>"一般组员",
),"设置权限为","no");
$group_form->addSelect("group_type",array(
    'public'=>"开放自由加入",
    'protected'=>"需要验证",
    'private'=>"必须手动加入"
),"群组类型","public");
$group_form->addInput(	"group_form_group_name","text", '群组名称', "", function($str, &$vdata){
    if(!validateGroupname($str)){
        return "组名{$str}非法,请检查！";
    }
    if(count(DB::selectAll("select * from group_description where group_name='{$str}'"))>0){
        return "{$str}群组已经存在，请新创建一个组名！";
    }
    return '';
},null);
$group_form->addTextArea("group_form_users", '用户', $query_text, function($str, &$vdata){
    if($_POST['group-operation']=="query")
        return '';
    else if($_POST['group-operation']=="del" || $_POST['group-operation']=="add" ){
        $local_group_name=$_POST['group_select'];
        if($local_group_name==""){
            return "请选择一个组名！";
        }
    }
    $users = array();
    foreach (explode("\n", $str) as $line_id => $raw_line) {
        $username = trim($raw_line);//移除空格等
        if ($username == '') {
            continue;
        }
        //检查对象是否存在
        if(!queryUser($username)){
            return "User {$username} 不存在，请检查输入！（出错：第{$line_id}行）";
        }
        $users[] = $username;
    }
    $vdata['users'] = $users;
    return '';
},null);
$group_form->handle = function(&$vdata) use($group_form){
    //通过验证，执行操作
    if($_POST['group-operation']=="add"){
        $local_group_name=$_POST['group_select'];
        foreach ($vdata["users"] as $index =>$username){
            DB::delete("delete from group_info where group_name = '{$local_group_name}' and username = '{$username}'");
            DB::insert("insert into group_info (group_name, username,is_admin, state)   values ('{$local_group_name}', '{$username}','{$_POST['group_is_admin']}','in')");
        }
    }
    else if($_POST['group-operation']=="del"){
        $local_group_name=$_POST['group_select'];
        foreach ($vdata["users"] as $index =>$username){
            DB::delete("delete from group_info where group_name = '{$local_group_name}' and username = '{$username}'");
        }
    }
    else if($_POST['group-operation']=="query"){
        $group_form->succ_href="group?group_query={$_POST["group_select"]}&group_query_state={$_POST["group_query_state"]}";
    }
    else if($_POST['group-operation']=="modify"){
        $local_group_name=$_POST['group_select'];
        foreach ($vdata["users"] as $index =>$username){
            mysql_query("delete from group_info where group_name = '{$local_group_name}' and username = '{$username}'");
            if(0==DB::selectCount("select * from group_info where group_name ='{$local_group_name}' and username = '{$username}'")){
                DB::insert("insert into group_info (group_name, username,is_admin, state) values ('{$local_group_name}', '{$username}','{$_POST['group_is_admin']}','in')");
            }
        }

    }
    else if($_POST['group-operation']=="create"){
        $cur_username=$_COOKIE["uoj_username"];
        DB::select();
        if(DB::select("count * from group_description where group_name='{$_POST['group_form_group_name']}'")==0){
            DB::insert("INSERT INTO group_description (group_name, group_type) VALUES ('{$_POST['group_form_group_name']}','{$_POST['group_type']}') ");
            DB::insert("INSERT INTO group_info (group_name, username,is_admin, state)  VALUES ('{$_POST['group_form_group_name']}', '{$cur_username}','yes','in')");
        }
    }
};
$group_form->succ_href="group";
$group_form->runAtServer();



//默认
$cur_tab = isset($_GET['tab']) ? $_GET['tab'] : 'users';

$tabs_info = array(
    'users' => array(
        'name' => '用户操作',
        'url' => "/super-manage/users"
    ),
    'blogs' => array(
        'name' => '博客管理',
        'url' => "/super-manage/blogs"
    ),
    'submissions' => array(
        'name' => '提交记录',
        'url' => "/super-manage/submissions"
    ),
    'custom-test' => array(
        'name' => '自定义测试',
        'url' => '/super-manage/custom-test'
    ),
    'click-zan' => array(
        'name' => '点赞管理',
        'url' => '/super-manage/click-zan'
    ),
    'search' => array(
        'name' => '搜索管理',
        'url' => '/super-manage/search'
    ),
    'group' => array(
        'name' => '组管理',
        'url' => '/super-manage/group'
    )
);

if (!isset($tabs_info[$cur_tab])) {
    become404Page();
}
?>
<?php
requireLib('shjs');
requireLib('morris');
?>
<?php echoUOJPageHeader('系统管理') ?>
    <div class="row">
        <div class="col-sm-3">
            <?= HTML::tablist($tabs_info, $cur_tab, 'nav-pills nav-stacked') ?>
        </div>

        <div class="col-sm-9">
            <?php if ($cur_tab === 'users'): ?>
                <?php $user_form->printHTML(); ?>
                <h3>封禁名单</h3>
            <?php echoLongTable($banlist_cols, 'user_info', "usergroup='B'", '', $banlist_header_row, $banlist_print_row, $banlist_config) ?>
            <?php elseif ($cur_tab === 'blogs'): ?>
                <div>
                    <h4>添加到比赛链接</h4>
                    <?php $blog_link_contests->printHTML(); ?>
                </div>

                <div>
                    <h4>添加到公告</h4>
                    <?php $blog_link_index->printHTML(); ?>
                </div>

                <div>
                    <h4>删除博客</h4>
                    <?php $blog_deleter->printHTML(); ?>
                </div>
            <?php elseif ($cur_tab === 'submissions'): ?>
                <div>
                    <h4>删除赛前提交记录</h4>
                    <?php $contest_submissions_deleter->printHTML(); ?>
                </div>
                <div>
                    <h4>测评失败的提交记录</h4>
                    <?php echoSubmissionsList("result_error = 'Judgment Failed'", 'order by id desc', $myUser); ?>
                </div>
            <?php elseif ($cur_tab === 'custom-test'): ?>
                <?php $custom_test_deleter->printHTML() ?>
                <?php
                $submissions_pag = new Paginator(array(
                    'col_names' => array('*'),
                    'table_name' => 'custom_test_submissions',
                    'cond' => '1',
                    'tail' => 'order by id asc',
                    'page_len' => 5
                ));
                foreach ($submissions_pag->get() as $submission)
                {
                    $problem = queryProblemBrief($submission['problem_id']);
                    $submission_result = json_decode($submission['result'], true);
                    echo '<dl class="dl-horizontal">';
                    echo '<dt>id</dt>';
                    echo '<dd>', "#{$submission['id']}", '</dd>';
                    echo '<dt>problem_id</dt>';
                    echo '<dd>', "#{$submission['problem_id']}", '</dd>';
                    echo '<dt>submit time</dt>';
                    echo '<dd>', $submission['submit_time'], '</dd>';
                    echo '<dt>submitter</dt>';
                    echo '<dd>', $submission['submitter'], '</dd>';
                    echo '<dt>judge_time</dt>';
                    echo '<dd>', $submission['judge_time'], '</dd>';
                    echo '</dl>';
                    echoSubmissionContent($submission, getProblemCustomTestRequirement($problem));
                    echoCustomTestSubmissionDetails($submission_result['details'], "submission-{$submission['id']}-details");
                }
                ?>
                <?= $submissions_pag->pagination() ?>
            <?php elseif ($cur_tab === 'click-zan'): ?>
                没写好QAQ
            <?php elseif ($cur_tab === 'search'): ?>
                <h2 class="text-center">一周搜索情况</h2>
                <div id="search-distribution-chart-week" style="height: 250px;"></div>
                <script type="text/javascript">
                    new Morris.Line({
                        element: 'search-distribution-chart-week',
                        data: <?= json_encode(DB::selectAll("select DATE_FORMAT(created_at, '%Y-%m-%d %h:00'), count(*) from search_requests  where created_at > now() - interval 1 week group by DATE_FORMAT(created_at, '%Y-%m-%d %h:00')")) ?>,
                        xkey: "DATE_FORMAT(created_at, '%Y-%m-%d %h:00')",
                        ykeys: ["count(*)"],
                        labels: ['number'],
                        resize: true
                    });
                </script>

                <h2 class="text-center">一月搜索情况</h2>
                <div id="search-distribution-chart-month" style="height: 250px;"></div>
                <script type="text/javascript">
                    new Morris.Line({
                        element: 'search-distribution-chart-month',
                        data: <?= json_encode(DB::selectAll("select DATE_FORMAT(created_at, '%Y-%m-%d'), count(*) from search_requests  where created_at > now() - interval 1 week group by DATE_FORMAT(created_at, '%Y-%m-%d')")) ?>,
                        xkey: "DATE_FORMAT(created_at, '%Y-%m-%d')",
                        ykeys: ["count(*)"],
                        labels: ['number'],
                        resize: true
                    });
                </script>

            <?php echoLongTable(array('*'), 'search_requests', "1", 'order by id desc',
                '<tr><th>id</th><th>created_at</th><th>remote_addr</th><th>type</th><th>q</th><tr>',
                function($row) {
                    echo '<tr>';
                    echo '<td>', $row['id'], '</td>';
                    echo '<td>', $row['created_at'], '</td>';
                    echo '<td>', $row['remote_addr'], '</td>';
                    echo '<td>', $row['type'], '</td>';
                    echo '<td>', HTML::escape($row['q']), '</td>';
                    echo '</tr>';
                }, array(
                    'page_len' => 1000
                ))
            ?>

            <?php elseif ($cur_tab === 'group'): ?>
                <script language="javascript">;
                    window.onload=function(){
                        var group_operation=document.getElementsByName("group-operation")[0];
                        var group_select=document.getElementsByName("group_select")[0];
                        var group_query_state=document.getElementsByName("group_query_state")[0];
                        var group_is_admin=document.getElementsByName("group_is_admin")[0];
                        var group_type=document.getElementsByName("group_type")[0];
                        var group_form_group_name=document.getElementsByName("group_form_group_name")[0];
                        var group_form_users=document.getElementsByName("group_form_users")[0];
                        var clear_display=function(){
                            var div_group_select=document.getElementById("div-group_select");
                            var div_group_query_state=document.getElementById("div-group_query_state");
                            var div_group_type=document.getElementById("div-group_type");
                            var div_group_is_admin=document.getElementById("div-group_is_admin");
                            var div_group_form_group_name=document.getElementById("div-group_form_group_name");
                            var div_group_form_users=document.getElementById("div-group_form_users");
                            div_group_select.style.display="none";
                            div_group_query_state.style.display="none";
                            div_group_type.style.display="none";
                            div_group_is_admin.style.display="none";
                            div_group_form_group_name.style.display="none";
                            div_group_form_users.style.display="none";
                        }
                        clear_display();
                        group_operation.onchange=function(){
                            var div_group_select=document.getElementById("div-group_select");
                            var div_group_query_state=document.getElementById("div-group_query_state");
                            var div_group_type=document.getElementById("div-group_type");
                            var div_group_is_admin=document.getElementById("div-group_is_admin");
                            var div_group_form_group_name=document.getElementById("div-group_form_group_name");
                            var div_group_form_users=document.getElementById("div-group_form_users");

                            var oper=this.options[this.selectedIndex].value;
                            if(oper==="query"){
                                clear_display();
                                div_group_select.style.display="inherit";
                                div_group_query_state.style.display="inherit";
                                div_group_form_users.style.display="inherit";
                            }else if(oper==="add"){
                                clear_display();
                                div_group_select.style.display="inherit";
                                div_group_is_admin.style.display="inherit";
                                div_group_form_users.style.display="inherit";
                            }else if(oper==="del"){
                                clear_display();
                                div_group_select.style.display="inherit";
                                div_group_form_users.style.display="inherit";
                            }else if(oper==="modify"){
                                clear_display();
                                div_group_select.style.display="inherit";
                                div_group_is_admin.style.display="inherit";
                                div_group_form_users.style.display="inherit";
                            }else if(oper==="create"){
                                clear_display();
                                div_group_form_group_name.style.display="inherit";
                                div_group_type.style.display="inherit";
                            }
                            document.getElementById("div-group_form_msg").style.display="none";
                        }
                        group_operation.onchange();
                        document.getElementById("div-group_form_msg").style.display="inherit";
                    }
                </script>
            <br>
                <div id="div-group_form_msg" align="center"><font color="red">
                        <?php echo $group_form_error_msg ?>
                    </font></div>
            <br>
            <br>
            <?php
            $group_form->printHTML();
            ?>
            <br>
            <?php endif ?>
        </div>
    </div>
<?php echoUOJPageFooter() ?>