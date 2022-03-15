<?php
/*
 |  Snicker     The first native FlatFile Comment Plugin 4 Bludit
 |  @file       ./admin/edit.php
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.1.2 [0.1.0] - Alpha
 |
 |  @website    https://github.com/pytesNET/snicker
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2019 SamBrishes, pytesNET <info@pytes.net>
 */
    if(!defined("BLUDIT")){ die("Go directly to Jail. Do not pass Go. Do not collect 200 Cookies!"); }

    global $login, $pages, $security, $SnickerIndex;

    $data = $SnickerIndex->getComment($_GET["uid"]);
    $comment = new Comment($_GET["uid"], $data["page_uuid"]);
    $page = new Page($pages->getByUUID($data["page_uuid"]));

?><h2 class="mt-0 mb-3">
    <span class="oi oi-comment-square" style="font-size: 0.7em;"></span> Snicker <?php sn_e("Comments"); ?> / <?php sn_e("Edit"); ?>
</h2>
<form method="post" action="<?php echo HTML_PATH_ADMIN_ROOT; ?>snicker">
    <div class="card" style="margin: 1.5rem 0;">
        <div class="card-body">
            <div class="row">
                <div class="col-sm-6">
                    <input type="hidden" id="tokenUser" name="tokenUser" value="<?php echo $login->username(); ?>" />
                    <input type="hidden" id="tokenCSRF" name="tokenCSRF" value="<?php echo $security->getTokenCSRF(); ?>" />
                    <input type="hidden" id="sn-action" name="action" value="snicker" />
                    <input type="hidden" id="sn-snicker" name="snicker" value="edit" />
                    <input type="hidden" id="sn-unique" name="uid" value="<?php echo $comment->uid(); ?>" />
                    <button class="btn btn-primary" name="type" value="edit"><?php sn_e("Update Comment"); ?></button>
                </div>

                <div class="col-sm-6 text-right">
                    <button class="btn btn-danger" name="type" value="delete"><?php sn_e("Delete Comment"); ?></button>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col">
            <input type="text" name="comment[title]" value="<?php echo $comment->title(); ?>"
                class="form-control form-control-lg" placeholder="<?php sn_e("Comment Title"); ?>" />
        </div>
    </div>

    <div class="row">
        <div class="col-sm-8">
            <textarea name="comment[comment]" class="form-control" placeholder="<?php sn_e("Comment Text"); ?>"
                style="min-height: 275px;"><?php echo $comment->commentRaw(); ?></textarea>
        </div>
        <div class="col-sm-4">
            <div class="card">
                <div class="card-header"><?php sn_e("Meta Settings"); ?></div>
                <div class="card-body">

                    <?php if(strpos($comment->getValue("author"), "bludit") === 0){ ?>
                        <p>
                            <input type="text" value="<?php echo $comment->username(); ?>" class="form-control" disabled />
                        </p>
                        <p>
                            <input type="text" value="<?php sn_e("Registered User"); ?>" class="form-control" disabled />
                        </p>
                    <?php } else { ?>
                        <p>
                            <input type="text" name="comment[username]" value="<?php echo $comment->username(); ?>"
                            class="form-control" placeholder="<?php sn_e("Comment Username"); ?>" />
                        </p>
                        <p>
                            <input type="text" name="comment[email]" value="<?php echo $comment->email(); ?>"
                            class="form-control" placeholder="<?php sn_e("Comment eMail"); ?>" />
                        </p>
                    <?php } ?>
                    <p>
                        <select name="comment[status]" class="custom-select">
                            <option value="pending"<?php echo ($comment->isPending())? ' selected="selected"': ''; ?>><?php sn_e("Pending"); ?></option>
                            <option value="approved"<?php echo ($comment->isApproved())? ' selected="selected"': ''; ?>><?php sn_e("Approved"); ?></option>
                            <option value="rejected"<?php echo ($comment->isRejected())? ' selected="selected"': ''; ?>><?php sn_e("Rejected"); ?></option>
                            <option value="spam"<?php echo ($comment->isSpam())? ' selected="selected"': ''; ?>><?php sn_e("Spam"); ?></option>
                        </select>
                    </p>
                </div>
            </div>

            <p class="mt-4 text-center">
                <a href="<?php echo $page->permalink(); ?>" target="_blank" class="btn btn-primary"><?php sn_e("View Page"); ?></a>
            </p>
        </div>
    </div>
</form>
