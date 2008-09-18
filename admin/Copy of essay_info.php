    <?php
	if(!isset($_GET['id']) || $_GET['id'] == '') {
		header("Location: applicants_list.php");
		exit;
	} else {
		$editid = $_GET['id'];
	}

    require_once("../includes/config.php");
    require_once(CLASS_PATH. "class.mysql.php");
    require_once(CLASS_PATH. "class.common.php");
    $common = new Common();
    $sql = new  Sql();
	$common->checkSession();
	$user_details = $sql->get_one_record_two_condition("users", "id", $user_id, "type", "admin");
    $user_type = $user_details['type'];   
	include_once(INCLUDE_PATH. "top.php"); 
	$user_id = $editid;
	$user_details = $sql->get_one_record("users", "id", $user_id);


    $question_details = $sql->get_one_record("application_template", "application_year", date('Y'));
    $question = $question_details['essay_question'];   
    
    //Redirect to login page if no user is logged in 
    $common->redirect_to_login_page($user_id);
    $common->redirect_to_allowed_page($user_details['type']); 
    $edit = $_REQUEST['edit'];  
    if(isset($_REQUEST['update_essay']))
    {
         extract($_REQUEST);
         
         if($ids != '') {
            $ids_arr = explode(',',$ids);
         }
         $flag = 0;
         foreach($ids_arr as $val) {
            $qid = 'answer'.$val;
            if($$qid != '')    {
                $flag =1;   
            }
         }
          if($flag == 0) array_push($error, "Please enter answer");
          if(count($error) == 0)
          {
             //echo "no error";
             //print_r($user_details);
             $upload_dir = '/upload';
             if($edit == 1)
             {
                 foreach($ids_arr as $val) {
                     if($val !='') {
                         $qid = 'answer'.$val;
                         $query = "UPDATE `user_essay` SET answer='".addslashes($$qid)."' WHERE user_id='".$user_id."' and question_id='".$val."'";
                         //echo $query;
                         mysql_query($query); 
                     }
                 }
             }
             else
             {
                 foreach($ids_arr as $val) { 
                    if($val !='') {
                         $qid = 'answer'.$val;                
                         
                         $query = "INSERT INTO `user_essay`(user_id,question_id, answer, status, created_date)
                                    VALUES('".addslashes($user_id)."','".$val."', '".addslashes($$qid)."', 'Complete', NOW())";
                         mysql_query($query) or die(mysql_error());  
                    }
                 }             
             }
             $cnt = count($_FILES['essay_file']['name']);
             
             for($i=0;$i<$cnt;$i++)
             {
                 if(!empty($_FILES['essay_file']['name'][$i]))
                 {
                     $filename = $_FILES['essay_file']['name'][$i];
                     $pos = strrpos($filename, ".");
                     $filename_only = substr($filename, 0,$pos); //as.df
                     $filetype_with_dot = substr($filename, $pos, strlen($filename));
                     
                     $full_file_name = time().rand(1,9999).$filetype_with_dot;
                     $user_dir_path = "./upload/$user_id";
                     if(!file_exists($user_dir_path)) 
                     {
                         $user_dir = mkdir($user_dir_path, 0777);
                         chmod($user_dir_path, 0777);
                     }
                     else
                     {
                        $user_dir = $user_dir_path;
                         chmod($user_dir_path, 0777);
                     }
                     $file_path = $user_dir . "/" .  $full_file_name;
                     if(move_uploaded_file($_FILES['essay_file']['tmp_name'][$i], $file_path))
                     {
                       $query = "INSERT INTO `user_essay_files`(user_id, essay_file, file_name)
                            VALUES('$user_id', '$full_file_name', '$filename')";
                       mysql_query($query) or die(mysql_error());               
                     } 
               }
               
             }
             $msg = "Essay answers are updated successfully!";
             $msg = urlencode($msg);
             header("Location: application_review.php?msg=$msg&id=".$editid);   
          }
          
    }
    else
    {
        $edit =0;
        $essay_rs = mysql_query("select * from user_essay where user_id = '$user_id'");
        $arrs = array();
        while($essay_row = mysql_fetch_array($essay_rs)) {
            $arrs[$essay_row['question_id']]  = $essay_row['answer'];   
            $edit =1;
        }
    }
    ?>
          
        <!-- Content -->
        <td class="content">
            <table>
            <?php
            echo $common->show_error_messages($error); 
            ?>
            </table>
            <form name='updateEssay' method='post' action='essay_info.php?id=<?php echo $editid; ?>' onsubmit='return validateUpdateEssay();' enctype="multipart/form-data">
            <table class="dialogbox" border="0" cellpadding="0" cellspacing="0" id='tableid'>
            <tbody>
              
            <tr>
                <th colspan="2">Update Essays <span class='smallred'> (* Required field )</span></th>
            </tr>
            <?php  
            $query = "select eq.id,eq.essay_question from essay_questions eq inner join application_template at on eq.template_id = at.id and at.application_year = '".date('Y')."'";
            $result = mysql_query($query);
            $ids='';
            while($row = mysql_fetch_array($result)) {
            ?>
            <tr>
                <td colspan="2"><?php 
                //print_r($row);
                $ids = $ids.','.$row['id'];
                echo $row['essay_question']; 
                ?>
                
                </td>
            </tr>
            <tr>
                <td class="dialogbox-titlecolumn"><label for="formUsername"><span class='smallred'>*</span> Answer <br><span class='verysmallred'>(Maximum 500 words)</span></label></td>
                <td><textarea name='answer<?php echo $row['id']; ?>' cols='35' rows="10"><?php echo $arrs[$row['id']]; ?></textarea> </td>
            </tr>
            <?php } ?>
             <input type="hidden" value="<?php echo $ids; ?>" name="ids">
             <input type='hidden' name='edit' value="<?php echo $edit; ?>">  
            <tr>
                <td class="dialogbox-titlecolumn"><label for="formUsername">&nbsp;&nbsp;Upload file</label></td>
                <td><input name="essay_file[]" size="34" type="file" onkeydown="this.blur()"> </td>
            </tr>
            
            
            </tbody>
            </table>
            
            
            <table class="dialogbox" border="0" cellpadding="0" cellspacing="0">
            <tbody>
            <tr>
                <td class="dialogbox-titlecolumn">&nbsp;</td>
                <td colspan="3"><a href='#' onclick="addMoreFile('tableid');">Add One More File</a></td>
            </tr>
             
            <tr>
                
                <th colspan="4" style="text-align:center;"><input type="submit" name="update_essay" value="Update &gt;" class="primarybutton"> &nbsp;&nbsp;
                <input type="button" name="personal" value=" Back "  class="primarybutton" onclick="self.location='application_review.php?id=<?php echo $editid; ?>';"> 
                </th>
            </tr>
            
            </tbody></table>
            </form>

        </td>
        <!-- End Content -->
   <?php
    include_once(INCLUDE_PATH. "bottom.php");
    ?>