<?php
/**
 * Show and modify page sets
 *
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
 *
 * @package Zoph
 * @author Jeroen Roos
 *
 */
require_once "include.inc.php";

use template\template;

$pageset_id = getvar("pageset_id");
$page_id = getvar("page_id");
$pageset = new pageset($pageset_id);
if ($pageset_id) {
    $pageset->lookup();
}
if (!is_null($page_id)) {
    $page = new page($page_id);
}

if (!$user->isAdmin()) {
    $_action="display";
}

if ($_action == "insert") {
    $pageset->set("user", $user->get("user_id"));
} else if ($_action == "moveup") {
    $pageset->moveUp($page);
} else if ($_action == "movedown") {
    $pageset->moveDown($page);
} else if ($_action == "delpage") {
    $pageset->removePage($page);
    $action="display";
} else if ($_action == "addpage") {
    $pageset->addPage($page);
    $action="display";
}
$obj = &$pageset;

require_once "actions.inc.php";

if ($_action != "new") {
    $title = $pageset->get("title");
} else {
    $title = translate("Create new pageset");
}
require_once "header.inc.php";
?>
<?php
if ($action == "confirm") {
    ?>
      <h1><?php echo translate("delete pageset") ?></h1>
        <div class="main">
           <ul class="actionlink">
             <li><a href="pageset.php?_action=confirm&amp;pageset_id=<?php
                echo $pageset->getId() ?>"><?php echo translate("delete") ?>
             </a></li>
             <li><a href="pageset.php?_action=edit&amp;pageset_id=<?php
                echo $pageset->getId() ?>"><?php echo translate("cancel") ?>
             </a></li>
           </ul>
           <?php echo translate("Confirm deletion of this pageset"); ?>
         </div>
    <?php
} else if ($action == "display") {
    ?>
      <h1>
        <ul class="actionlink">
          <li><a href="pageset.php?_action=edit&amp;pageset_id=<?php
            echo $pageset->getId() ?>">
            <?php echo translate("edit") ?>
          </a></li>
          <li><a href="pageset.php?_action=delete&amp;pageset_id=<?php
            echo $pageset->getId() ?>">
            <?php echo translate("delete") ?>
          </a></li>
        </ul>
        <?php echo $title; ?>
      </h1>
      <div class="main">
        <br>
        <dl class="display pageset">
    <?php
    $pageset->lookup();
    echo create_field_html($pageset->getDisplayArray());
    ?>
        </dl>
        <br>
        <h2>
          <?php echo translate("Pages in this pageset"); ?>
        </h2>
    <?php echo page::getTable($pageset->getPages(), $pageset); ?>
        <form action="pageset.php" class="addpage">
          <input type="hidden" name="_action" value="addpage">
          <input type="hidden" name="pageset_id" value="<?php echo $pageset->get("pageset_id") ?>">
          <label for="page_id">
            <?php echo translate("Add a page:") ?>
          </label>
          <?php echo template::createPulldown("page_id", 0, template::createSelectArray(page::getRecords("title"), array("title"), true), true); ?>
          <input type="submit" name="_button" value="<?php echo translate("add",0)?>">
        </form>
        <br>
      </div>
    <?php
} else {
    ?>
    <h1>
        <?php echo $title ?>
    </h1>
    <div class="main">
        <br>
        <form action="pageset.php">
            <input type="hidden" name="_action" value="<?php echo $action ?>">
            <input type="hidden" name="pageset_id" value="<?php
                echo $pageset->get("pageset_id") ?>">
            <label for="title"><?php echo translate("title") ?></label>
            <?php echo create_text_input("title", $pageset->get("title")) ?><br>
            <label for="show_orig"><?php echo translate("show original page") ?></label>
            <?php echo $pageset->getOriginalDropdown(); ?><br>
            <label for="orig_pos"><?php echo translate("position of original") ?></label>
            <?php echo template::createPulldown("orig_pos",
                $pageset->get("orig_pos"),
                array("top" => translate("Top",0), "bottom" => translate("Bottom",0))) ?><br>
            <input type="submit" value="<?php echo translate($action, 0) ?>">
        </form>
    </div>

    <?php
}
require_once "footer.inc.php";
?>
