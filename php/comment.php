<?php
/*
 * This file is part of Zoph.
 *
 * Zoph is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * Zoph is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with Zoph; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
    require_once("include.inc.php");

    if (!ALLOW_COMMENTS) {
        header("Location: " . add_sid("zoph.php"));
    }

    $comment_id = getvar("comment_id");
    $comment = new comment($comment_id);
    if ($comment_id) {
        $comment->lookup();
	$comment_user=new user($comment->get("user_id"));
	$comment_user->lookup();
    }

    if(!$user->is_admin() && (!$comment->is_owner($user)) && ($_action!="new") && $_action!="insert") {
       $_action="display";
    }

    if(!$user->is_admin() && !$user->get("leave_comments") && ($_action=="new" || $_action=="insert")) {
        header("Location: " . add_sid("zoph.php"));
    }

    $photo=$comment->get_photo();

    if ($photo) {
       $photo_id=$photo->get("photo_id");
       if(!$user->get_permissions_for_photo($photo_id) && !$user->is_admin()) {
           header("Location: " . add_sid("zoph.php"));
       }
    } else {
       $photo_id = getvar("photo_id");
       $photo = new photo($photo_id);
    }

    $request_vars["photo_id"]=NULL;


    $redirect = "comment.php";
    if ($_action == "insert") {
        $comment->set("user_id", $user->get("user_id"));
    }
    
    $obj = &$comment;

    require_once("actions.inc.php");

    if($_action == "insert") {
	$comment->add_comment_to_photo($photo_id);
    }
    
    if ($_action != "new") {
        $title = $comment->get("subject");
    } else {
        $title = translate("Add comment");
    }
    require_once("header.inc.php");
?>
<?php
if ($action == "confirm") {
?>
          <h1><?php echo translate("delete comment") ?></h1>
            <div class="main">
               <span class="actionlink">
                 <a href="comment.php?_action=confirm&amp;comment_id=<?php echo $comment->get("comment_id") ?>"><?php echo translate("delete") ?></a> |
                 <a href="comment.php?_action=edit&amp;comment_id=<?php echo $comment->get("comment_id") ?>"><?php echo translate("cancel") ?></a>
               </span>
               <?php echo sprintf(translate("Confirm deletion of comment '<b>%s</b>' by '<b>%s</b>'"), $comment->get("subject"), $comment_user->get("user_name")) ?>
             </div>
<?php
    }
    else if ($action == "display") {
?>
          <h1>
<?php
        if ($user->is_admin() || $comment->is_owner($user)) {
?>
            <span class="actionlink">
              <a href="photo.php?photo_id=<?php echo $photo_id ?>"><?php echo translate("return") ?></a> |
              <a href="comment.php?_action=edit&amp;comment_id=<?php echo $comment->get("comment_id") ?>"><?php echo translate("edit") ?></a> |
              <a href="comment.php?_action=delete&amp;comment_id=<?php echo $comment->get("comment_id") ?>"><?php echo translate("delete") ?></a>
            </span>
<?php
     }
     echo $title;
?>
          </h1>
      <div class="main">
      <br>
<?php
    echo $photo->get_midsize_img();
?>
<br>
      <table>
<?php echo create_field_html($comment->get_display_array($user)) ?>
</table>
  </div>
<?php
    }
    else {
?>
    <h1>
        <?php echo $title ?>
    </h1>
    <div class="main">
    <br>
<?php
    echo $photo->get_midsize_img();
?>
    <br>

        <form action="comment.php">
            <input type="hidden" name="_action" value="<?php echo $action ?>">
            <input type="hidden" name="comment_id" value="<?php echo $comment->get("comment_id") ?>">
            <input type="hidden" name="photo_id" value="<?php echo $photo_id ?>">
            <label for="subject"><?php echo translate("subject") ?></label>
            <?php echo create_text_input("subject", $comment->get("subject")) ?><br>
            <label for="comment"><?php echo translate("comment") ?></label> 
            <textarea name="comment" rows="8" cols="80"><?php echo $comment->get("comment") ?></textarea><br>
            <input type="submit" value="<?php echo translate($action, 0) ?>">
        </form>
    </div>

<?php
}
    require_once("footer.inc.php");
?>