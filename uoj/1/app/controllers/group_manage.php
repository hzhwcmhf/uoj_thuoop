<?php

/**
 * Created by PhpStorm.
 * User: yaozh16
 * Date: 18-5-9
 * Time: 下午5:35
 */

requireLib('flot');
requirePHPLib('form');
$base_URL='/groups';
if ($myUser == null ) become403Page();


$cur_tab = isset($_GET['tab']) ? $_GET['tab'] : 'my_group';
$username = $myUser['username'];
//tab条目
$tabs_info = array(
    'my_group' => array(
        'name' => '我的群组',
        'url' => '/groups/my_group'
    ),
    'join' => array(
        'name' => '加入新群组',
        'url' => '/groups/join'
    )
);
//增加管理群组条目
foreach (DB::selectAll("select * from group_info where username='{$username}' and is_admin='yes'") as $each_row) {
    $tabs_info["manage_".$each_row['group_name']] =array(
        'name' => '管理群组：'.$each_row['group_name'],
        'url' => '/groups/manage_'.$each_row['group_name']
    ) ;
}
//防止非法输入
if (!isset($tabs_info[$cur_tab])) {
    become404Page();
}


global $group_exit_forms, $group_join_forms,$group_manage_forms;

$group_manage_forms=array();
$group_exit_forms = array();//用于控制退出群组
foreach (DB::selectAll("select * from group_info where username='{$username}'") as $each_row) {
    $group_name = $each_row['group_name'];
    $username = $each_row['username'];
    $cur_group = DB::selectFirst("select * from group_description where group_name='{$group_name}'");
    $count = count(DB::selectAll("select * from group_info where group_name= '{$group_name}'"));

    $form_name = "exit_group_{$group_name}_{$username}";
    $exit_form = new UOJForm($form_name);
    $exit_form->addHidden("group_name", "$group_name", function ($str, &$vdata) {
        $vdata['group_name'] = $str;
    }, null);
    $exit_form->addHidden("username", "$username", function ($str, &$vdata) {
        $vdata['username'] = $str;
    }, null);
    $exit_form->handle = function (&$vdata) {
        $group_name = $vdata['group_name'];
        $username = $vdata['username'];
        DB::delete("delete from group_info where username='{$username}' and group_name='{$group_name}'");
    };
    $exit_form->submit_button_config['class_str'] = "btn btn-success btn-sm";
    if ($each_row['state'] == 'in')
        $exit_form->submit_button_config['text'] = "退出群组";
    else
        $exit_form->submit_button_config['text'] = "放弃申请";
    $exit_form->submit_button_config['smart_confirm'] = "yes";
    $exit_form->runAtServer();
    $group_exit_forms[$group_name] = $exit_form;
}
$group_join_forms = array();//用于控制加入群组
foreach (DB::selectAll("select * from group_description") as $each_group) {
    if ($each_group['group_type'] == "private")
        continue;//组不开放
    if (count(DB::selectAll("select * from group_info where group_name='{$each_group['group_name']}' and username='{$username}'")) > 0)
        continue;//已经在组中
    $group_name = $each_group['group_name'];
    $group_type = $each_group['group_type'];
    $form_name = "join_group_{$group_name}_{$username}";
    $join_form = new UOJForm($form_name);
    $join_form->addHidden("group_name", "$group_name", function ($str, &$vdata) {
        $vdata['group_name'] = $str;
    }, null);
    $join_form->addHidden("username", "$username", function ($str, &$vdata) {
        $vdata['username'] = $str;
    }, null);
    $join_form->addHidden("group_type", "$group_type", function ($str, &$vdata) {
        $vdata['group_type'] = $str;
    }, null);
    if ($each_group['group_type'] == 'public')
        $join_form->submit_button_config['text'] = "加入群组";
    else if ($each_group['group_type'] == 'protected')
        $join_form->submit_button_config['text'] = "申请加入";
    $join_form->handle = function ($vdata) {
        if ($vdata['group_type'] == 'public')
            DB::insert("insert into group_info (group_name, username,is_admin, state)   values ('{$vdata['group_name']}', '{$vdata['username']}','no','in')");
        else if ($vdata['group_type'] == 'protected')
            DB::insert("insert into group_info (group_name, username,is_admin, state)   values ('{$vdata['group_name']}', '{$vdata['username']}','no','waiting')");
    };
    $join_form->runAtServer();
    $group_join_forms[$group_name] = $join_form;

}
foreach (DB::selectAll("select * from group_info where username='{$username}' and is_admin='yes'") as $each_row) {
    $group_name = $each_row['group_name'];
    $username = $each_row['username'];
    $group_id = DB::selectFirst("select * from group_description where group_name='{$group_name}'")['group_id'];
    $filter_form_name = "filter_group_{$group_name}_{$username}";
    $filter_form = new UOJForm($filter_form_name);
    $filter_form->addHidden("group_name", "$group_name", function ($str, &$vdata) {
        $vdata['group_name'] = $str;
    }, null);
    $filter_form->addHidden("username", "$username", function ($str, &$vdata) {
        $vdata['username'] = $str;
    }, null);

    $filter_form->appendHTML("<div class='table-responsive' width='100%'>");
    $filter_form->appendHTML("<div style=\"width:35%;display: inherit;float: left;\">");
    $filter_form->addSelect("state",array('all'=>'全部','in'=>'群组中','waiting'=>'等待审核'),'筛选',(isset($_GET['group_name']) && $_GET['group_name']==$group_name&&isset($_GET['state']) )?$_GET['state']:"all");
    $filter_form->appendHTML("</div>");

    $filter_form->appendHTML("<div style=\"width:45%;display:inherit;float: left;\" > ");
    $filter_form->addInput('filter_user','text','用户(部分)名称',(isset($_GET['group_name']) && $_GET['group_name']==$group_name)?$_GET['filter_user']:"",
        function ($str,&$vdata){
            $vdata['filter_user']=$str;
            return '';
        },null);
    $filter_form->appendHTML("</div>");

    $filter_form->appendHTML("<div style=\"display:inherit;float: left;\" > ");


    $filter_form->handle=function (&$vdata) use ($filter_form,$base_URL){
        $filter_form->succ_href=$base_URL."?".'group_name='.$vdata['group_name'].'&'.'state='.$_POST['state'].'&'.'filter_user='.$vdata['filter_user'];
    };
    $filter_form->submit_button_config['class_str'] = "btn btn-success btn-sm";
    $filter_form->submit_button_config['text'] = "筛选";
    $filter_form->submit_button_config['align']='offset';
    $filter_form->runAtServer();



    //实际操作
    $operate_form_name = "operate_group_{$group_name}_{$username}";
    $operate_form=new  UOJForm($operate_form_name);
    $operate_form->succ_href=$base_URL."?group_name=".$group_name;
    $operate_form->appendHTML("<div class='table-responsive' width='100%'>");
    $operate_form->appendHTML("<div style=\"width:35%;display: inherit;float: left;\" name='$group_name' onchange=\"switch_group_operation(this,'$group_name')\">");
    $operate_form->addSelect("group_operation",array('add'=>'添加','del'=>'删除','modify'=>'授权'),'操作','add');
    $operate_form->appendHTML("</div>");
    $operate_form->appendHTML("<div style=\"width:45%;display:inherit;float: left;\" id='{$group_name}_suboperation_group_is_admin'> ");
    $operate_form->addSelect("group_is_admin",array(
        'yes'=>"群管理员",
        'no'=>"一般组员",
    ),"设置为","no");
    $operate_form->appendHTML("</div>");
    $operate_form->appendHTML("<div style=\"width:14%;display:inherit;float: left;\" id='{$group_name}_suboperation_group_form_users'> ");
    $operate_form->addTextArea("group_form_users", '用户', "", function($str, &$vdata){
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
    $operate_form->appendHTML("</div>");
    $operate_form->appendHTML("</div>");

    //列表题目
    $operate_form->appendHTML('<div class="table-responsive" style="display: inherit;" >');
    $operate_form->appendHTML('<table class="table table-bordered table-hover table-striped table-text-center" style="display: inherit" width="100%">');
    $operate_form->appendHTML('<thead style="min-width:100%;">');
    $operate_form->appendHTML('<tr style="width:100%;">');
    $operate_form->appendHTML('<th style="width:20em;"> rank </th>');
    $operate_form->appendHTML( '<th style="width:20em;">用户名</th>');
    $operate_form->appendHTML( '<th style="width:20em;">权限</th>');
    $operate_form->appendHTML( '<th style="width:20em;">状态</th>');
    $operate_form->appendHTML( '<th style="width:20em;">');
    $operate_form->appendHTML("<input type=\"checkbox\"  name=\"all_{$operate_form_name}\" onchange=\"select_group_all(this, '{$operate_form_name}')\">");
    $operate_form->appendHTML('</th>');
    $operate_form->appendHTML('</tr>');
    $operate_form->appendHTML('</thead>');
    $operate_form->appendHTML('<tbody>');


//筛选
    $local_tmp_users=array();
    if(isset($_GET['group_name']) && $_GET['group_name']==$group_name){
        if(!isset($_GET['state']) ||$_GET['state']==='all'){
            $local_tmp_users=DB::selectAll('select * from group_info where group_name="'.$group_name.'" and username like "'.$_GET['filter_user'].'%"');
        }else{
            $local_tmp_users=DB::selectAll('select * from group_info where group_name="'.$group_name.'" and state=\''.$_GET['state'].'\' and username like "'.$_GET['filter_user'].'%"');
        }
    }
    else{
        $local_tmp_users=DB::selectAll('select * from group_info where group_name="'.$group_name.'"');
    }
    if(count($local_tmp_users)>0) {
        foreach ($local_tmp_users as $index => $local_tmp_user) {
            $operate_form->appendHTML('<tr>');
            $operate_form->appendHTML('<td>' . ($index + 1) . '</td>');
            $operate_form->appendHTML('<td>' . getUserLink($local_tmp_user['username']) . '</td>');
            $operate_form->appendHTML('<td>' . ($local_tmp_user['state'] == 'in' ? ($local_tmp_user['is_admin'] == 'yes' ? "管理员" : "组员") : "") . '</td>');
            $operate_form->appendHTML('<td>' . ($local_tmp_user['state'] == 'in' ? "已入组" : "等待审核") . '</td>');
            $operate_form->appendHTML('<td class="' . $operate_form_name . '_check_td">');
            $operate_form->addCheckBox("check_item_" . $local_tmp_user['username'], '', '');
            $operate_form->appendHTML('</td>');
            $operate_form->appendHTML('</tr>');
        }
    }else{
        $operate_form->appendHTML('<tr><td colspan="5">没有记录！</td></tr>');
    }
    $operate_form->appendHTML('</tbody>');
    $operate_form->appendHTML('</table>');
    $operate_form->appendHTML('</div>');

    $operate_form->submit_button_config['text']='发送';
    $operate_form->handle=function ($vdata) use($group_name){
        $checked=array();
        foreach ($_POST as $key=>$each_item){
            if(substr($key,0,11)=='check_item_' && $each_item=='on'){
                $checked[]=substr($key,11);

            };
        }
        switch ($_POST['group_operation']) {
            case 'add':
                foreach ($vdata['users'] as  $eachusername){
                    DB::delete("delete from group_info where group_name = '{$group_name}' and username = '{$eachusername}'");
                    DB::insert("insert into group_info (group_name, username,is_admin, state)   values ('{$group_name}', '{$eachusername}','{$_POST['group_is_admin']}','in')");
                }
                break;
            case 'del':
                foreach ($checked as  $eachusername){
                    DB::delete("delete from group_info where group_name = '{$group_name}' and username = '{$eachusername}'");
                }
                break;
            case 'modify':
                foreach ($checked as  $eachusername){
                    DB::delete("delete from group_info where group_name = '{$group_name}' and username = '{$eachusername}'");
                    DB::insert("insert into group_info (group_name, username,is_admin, state)   values ('{$group_name}', '{$eachusername}','{$_POST['group_is_admin']}','in')");
                }
                break;
        }
    };
    $operate_form->runAtServer();



    $group_manage_forms[$group_name]=array('filter'=>$filter_form,'operate'=>$operate_form);
}


?>
<script type="text/javascript">
    var select_group_all=function(obj,group_name) {
        console.log(obj);
        console.log(obj.value);
        console.log(group_name + '_check_td');
        var c = document.getElementsByClassName(group_name + '_check_td');
        console.log(c.length);
        for (var i = 0; i < c.length; i++) {
            console.log(c[i].getElementsByTagName('input')[0]);
            console.log(c[i].getElementsByTagName('input')[0].value);
            c[i].getElementsByTagName('input')[0].checked = obj.checked;
            console.log(c[i].getElementsByTagName('input')[0].value);
        }
    }
</script>
<script type='text/javascript'>
    function operate_group(group_name){
        all_board=document.getElementsByClassName('operate_group_board');
        var open_board="operate_"+group_name+"_board";
        for(var i=0;i<all_board.length;i++){
            if(all_board[i].id===open_board){
                if(all_board[i].style.display==='block') {
                    all_board[i].style.display = 'none';
                    this.value="收起";
                }else {
                    all_board[i].style.display = 'block';
                    this.value="管理";
                }
            }else{
                all_board[i].style.display='none';
            }
        }
    }
</script>
<script type='text/javascript'>
    var switch_group_operation=function(obj,form_name) {
        var select=obj.getElementsByTagName('select')[0];
        var suboperation_group_is_admin=document.getElementById(form_name+'_suboperation_group_is_admin');
        var suboperation_group_form_users=document.getElementById(form_name+'_suboperation_group_form_users');
        var oper=select.options[select.selectedIndex].value;
        console.log(select);
        console.log(suboperation_group_is_admin);
        console.log(suboperation_group_form_users);
        console.log(oper);
        switch(oper){
            case "add":
                suboperation_group_is_admin.style.display="inherit";
                suboperation_group_form_users.style.display="inherit";
                break;
            case "del":
                suboperation_group_is_admin.style.display="none";
                suboperation_group_form_users.style.display="none";
                break;
            case "modify":
                suboperation_group_is_admin.style.display="inherit";
                suboperation_group_form_users.style.display="none";
                break;
        }
    }

</script>
<?php

echoUOJPageHeader('群组管理');?>

<div class="row">
    <div class="col-sm-3">
        <?= HTML::tablist($tabs_info, $cur_tab, 'nav-pills nav-stacked') ?>
    </div>
    <div class="col-sm-9">
        <?php


        if(substr($cur_tab,0,7)==='manage_'){
            global $group_manage_forms;
            $form_array=$group_manage_forms[substr($cur_tab,7)];
            echo "<div class='operate_group_board' id='operate_{$group_name}_board' style='display:inherit;'>";
            $form_array['filter']->printHTML();

            echo "</div>";
            echo "</div>";
            $form_array['operate']->printHTML();
            echo "</div>";
        }
        else if($cur_tab=='my_group'){
            echo '<p class="list-group-item-text">';
            echo '<h6 class="list-group-item-heading">已申请加入的群组</h6>';
            function show_exit_table($username)
            {
                global $group_exit_forms;
                $header_row = '';
                $header_row .= '<tr>';
                $header_row .= '<th style="width: 5em;">群组id</th>';
                $header_row .= '<th style="width: 20em;">群组名</th>';
                $header_row .= '<th style="width: 20em;">加入状态</th>';
                $header_row .= '<th style="width: 20em;">是否是管理员</th>';
                $header_row .= '<th style="width: 20em;">群组人数</th>';
                if(Auth::check()&&Auth::id()===$username) {
                    $header_row .= '<th style="width: 10em;">操作</th>';
                }
                $header_row .= '</tr>';
                $print_row = function ($each_row, $index) use ($group_exit_forms) {
                    $group_name = $each_row['group_name'];
                    $username = $each_row['username'];
                    $cur_group = DB::selectFirst("select * from group_description where group_name='{$group_name}'");
                    $count = count(DB::selectAll("select * from group_info where group_name= '{$group_name}' and state='in'"));
                    echo '<tr>';
                    echo '<td>' . $cur_group['group_id'] . '</td>';
                    echo '<td>' . $group_name . '</td>';
                    echo '<td>' . ($each_row['state'] == 'in' ? "已入群" : "等待验证") . '</td>';
                    if($each_row['is_admin'] == 'yes' ){
                        echo '<td><input type="button" value="管理" href="/groups/manage_'. $group_name .'"> </td>';
                    }else
                        echo '<td>' .  "否" . '</td>';
                    echo '<td>' . $count . '</td>';

                    if(Auth::check()&&Auth::id()==$username) {
                        echo '<td>';
                        $group_exit_forms[$group_name]->printHTML();
                        echo '</td>';
                    }
                    echo '</tr>';

                };
                $col_names = array('*');

                $config = array(
                    'echo_full' => 'yes',
                    'get_row_index' => "yes"
                );
                echoLongTable($col_names, 'group_info', 'username =\'' . $username . '\'', "", $header_row, $print_row, $config);
            }
            show_exit_table($myUser['username']);
        }
        else if($cur_tab=="join"){

            echo '<h6 class="list-group-item-heading">可申请加入的群组</h6>';
            function show_join_table($username)
            {
                global $group_join_forms;
                $header_row = '';
                $header_row .= '<tr>';
                $header_row .= '<th style="width: 5em;">群组id</th>';
                $header_row .= '<th style="width: 20em;">群组名</th>';
                $header_row .= '<th style="width: 10em;">群组类型</th>';
                $header_row .= '<th style="width: 40em;">群组管理员</th>';
                $header_row .= '<th style="width: 10em;">群组人数</th>';
                $header_row .= '<th style="width: 10em;">操作</th>';
                $header_row .= '</tr>';
                $count = 0;
                $print_row = function ($each_row, $index) use ($group_join_forms, &$count, $username) {
                    if ($each_row['group_type'] == "private")
                        return;//组不开放
                    $exist = count(DB::selectAll("select * from group_info where group_name='{$each_row['group_name']}' and username='{$username}'"));
                    if ($exist > 0)
                        return;//已经在组中
                    $group_name = $each_row['group_name'];
                    $group_admins_arr = DB::selectAll('select username from group_info where group_name=\'' . $group_name . '\' and is_admin="yes"');
                    $group_admins = "";
                    foreach ($group_admins_arr as $each) {
                        if ($group_admins != '')
                            $group_admins .= ',';
                        $group_admins .= getUserLink($each['username']);
                    }
                    $count = count(DB::selectAll("select * from group_info where group_name= '{$group_name}'"));
                    echo '<tr>';
                    echo '<td>' . $each_row['group_id'] . '</td>';
                    echo '<td>' . $group_name . '</td>';
                    echo '<td>' . ($each_row['group_type'] == "public" ? "自由加入" : "需要验证") . '</td>';
                    echo '<td>' . $group_admins . '</td>';
                    echo '<td>' . $count . '</td>';
                    echo '<td>';
                    $group_join_forms[$group_name]->printHTML();
                    echo '</td>';
                    echo '</tr>';
                };
                $col_names = array('*');

                $config = array(
                    'echo_full' => 'yes',
                    'get_row_index' => "yes"
                );
                echoLongTable($col_names, 'group_description', 'group_name !=""', "", $header_row, $print_row, $config);
            }

            show_join_table($myUser['username']);
        }
        ?>
    </div>
</div>

<?php
echoUOJPageFooter();
?>
