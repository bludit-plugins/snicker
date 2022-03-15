<?php
/*
 |  Snicker     The first native FlatFile Comment Plugin 4 Bludit
 |  @file       ./system/themes/default/snicker.php
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.1.2 [0.1.0] - Alpha
 |
 |  @website    https://github.com/pytesNET/snicker
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2019 SamBrishes, pytesNET <info@pytes.net>
 */
    if(!defined("BLUDIT")){ die("Go directly to Jail. Do not pass Go. Do not collect 200 Cookies!"); }

    class Default_SnickerTemplate extends CommentsTheme{
        const SNICKER_NAME = "Default Theme";
        const SNICKER_JS = "snicker.js";
        const SNICKER_CSS = "snicker.css";

        /*
         |  RENDER :: COMMENT FORM
         |  @since  0.1.0
         |  @update 0.1.1
         */
        public function form($username = "", $email = "", $title = "", $message = ""){
            global $comments, $login, $page, $security, $Snicker;

            // User Logged In
            if(!is_a($login, "Login")){
                $login = new Login;
            }
            $user = $login->isLogged();

            // Get Data
            if(empty($security->getTokenCSRF())){
                $security->generateTokenCSRF();
            }
            $captcha = ($user)? "disabled": sn_config("frontend_captcha");
            $terms = ($user)? "disabled": sn_config("frontend_terms");

            // Is Reply
            $reply = isset($_GET["snicker"]) && $_GET["snicker"] == "reply";
            if($reply && isset($_GET["uid"]) && $comments->exists($_GET["uid"])){
                $reply = new Comment($_GET["uid"], $page->uuid());
            }
            ?>
                <form class="comment-form" method="post" action="<?php echo $page->permalink(); ?>?snicker=comment#snicker">
                    <?php if(is_array($username)){ ?>
                        <div class="comment-header">
                            <input type="hidden" id="comment-user" name="comment[user]" value="<?php echo $username[0]; ?>" />
                            <input type="hidden" id="comment-token" name="comment[token]" value="<?php echo $username[1]; ?>" />
                            <div class="inner">
                                <?php sn_e("Logged in as %s (%s)", array("<b>" . $username[2] . "</b>", $username[0])); ?>
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="comment-header">
                            <div class="table">
                                <div class="table-cell align-left">
                                    <input type="text" id="comment-user" name="comment[username]" value="<?php echo $username; ?>" placeholder="<?php sn_e("Your Username"); ?>" />
                                </div>
                                <div class="table-cell align-right">
                                    <input type="email" id="comment-mail" name="comment[email]" value="<?php echo $email; ?>" placeholder="<?php sn_e("Your eMail address"); ?>" />
                                </div>
                            </div>
                        </div>
                    <?php } ?>

                    <div class="comment-article">
                        <?php if(Alert::get("snicker-alert") !== false){ ?>
                            <div class="comment-alert alert-error">
                                <?php Alert::p("snicker-alert"); ?>
                            </div>
                        <?php } else if(Alert::get("snicker-success") !== false){ ?>
                            <div class="comment-alert alert-success">
                                <?php Alert::p("snicker-success"); ?>
                            </div>
                        <?php } ?>

                        <?php if($title !== false){ ?>
                            <p>
                                <input type="text" id="comment-title" name="comment[title]" value="<?php echo $title; ?>" placeholder="<?php sn_e("Comment Title"); ?>" />
                            </p>
                        <?php } ?>
                        <p>
                            <textarea id="comment-text" name="comment[comment]" placeholder="<?php sn_e("Your Comment..."); ?>"><?php echo $message; ?></textarea>
                        </p>
                        <?php if($captcha !== "disabled"){ ?>
                            <div class="comment-captcha">
                                <input type="text" name="comment[captcha]" value="" placeholder="<?php sn_e("Answer"); ?>" />

                                <a href="<?php echo $page->permalink(); ?>#snicker-comment-form" data-captcha="reload">
                                    <?php echo $Snicker->generateCaptcha();  ?>
                                </a>
                            </div>
                        <?php } ?>

                        <?php if(is_a($reply, "Comment")){ ?>
                            <div class="comment-reply">
                                <a href="<?php echo $page->permalink(); ?>" class="reply-cancel"></a>
                                <div class="reply-title">
                                    <?php echo $reply->username(); ?> <?php sn_e("wrotes"); ?>:
                                </div>
                                <div class="reply-content">
                                    <?php echo $reply->comment(); ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>

                    <div class="comment-footer">
                        <div class="table">
                            <div class="table-cell align-left">
                                <?php if($terms === "default"){ ?>
                                    <div class="terms-of-use">
                                        <input type="checkbox" id="comment-terms" name="comment[terms]" value="1" />
                                        <label for="comment-terms">
                                            <?php echo sn_config("string_terms_of_use"); ?>
                                        </label>
                                    </div>
                                <?php } else if($terms !== "disabled"){ ?>
                                    <div class="terms-of-use">
                                        <input type="checkbox" id="comment-terms" name="comment[terms]" value="1" />
                                        <label for="comment-terms">
                                            <?php sn_e("I agree the %s!", array('<a href="" target="_blank">'.sn__("Terms of Use").'</a>')); ?>
                                        </label>
                                    </div>
                                <?php } ?>
                            </div>
                            <div class="table-cell align-right">
                                <input type="hidden" name="tokenCSRF" value="<?php echo $security->getTokenCSRF(); ?>" />
                                <input type="hidden" name="comment[page_uuid]" value="<?php echo $page->uuid(); ?>" />
                                <input type="hidden" name="action" value="snicker" />
                                <?php if(is_a($reply, "Comment")){ ?>
                                    <input type="hidden" name="comment[parent_uid]" value="<?php echo $reply->uid(); ?>" />
                                    <button name="snicker" value="reply" data-string="<?php sn_e("Comment"); ?>"><?php sn_e("Answer"); ?></button>
                                <?php } else { ?>
                                    <button name="snicker" value="comment" data-string="<?php sn_e("Answer"); ?>"><?php sn_e("Comment"); ?></button>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </form>
            <?php

            unset($_SESSION["s_snicker-alert"]);        // Remove Snicker Alerts
            unset($_SESSION["s_snicker-success"]);      // Remove Snicker Success
        }

        /*
         |  RENDER :: PAGINATION
         |  @since  0.1.0
         */
        public function pagination($location, $cpage, $limit, $count){
            global $url;

            // Data
            $link = DOMAIN . $url->uri() . "?cpage=%d#snicker-comments-list";
            $maxpages = (int) ceil($count / $limit);
            $prev = ($cpage === 1)? false: $cpage - 1;
            $next = ($cpage === $maxpages)? false: $cpage + 1;

            // Top Position
            if($location === "top"){
                ?>
                    <div class="pagination pagination-top">
                        <?php if($cpage === 1){ ?>
                            <span class="pagination-button button-previous disabled"><?php sn_e("Previous Comments"); ?></span>
                        <?php } else { ?>
                            <a href="<?php printf($link, $prev); ?>" class="pagination-button button-previous"><?php sn_e("Previous Comments"); ?></a>
                        <?php } ?>

                        <?php if($cpage < $maxpages){ ?>
                            <a href="<?php printf($link, $next); ?>" class="pagination-button button-next"><?php sn_e("Next Comments"); ?></a>
                        <?php } else { ?>
                            <span class="pagination-button button-next disabled"><?php sn_e("Next Comments"); ?></span>
                        <?php } ?>
                    </div>
                <?php
            }

            // Bottom Position
            if($location === "bottom"){
                ?>
                    <div class="pagination pagination-bottom">
                        <div class="pagination-inner">
                            <?php if($prev === false){ ?>
                                <span class="pagination-button button-first disabled">&laquo;</span>
                                <span class="pagination-button button-previous disabled">&lsaquo;</span>
                            <?php } else { ?>
                                <a href="<?php printf($link, 1); ?>" class="pagination-button button-first">&laquo;</a>
                                <a href="<?php printf($link, $prev); ?>" class="pagination-button button-previous">&lsaquo;</a>
                            <?php } ?>

                            <?php
                                if($maxpages < 6){
                                    $start = 1;
                                    $stop = $maxpages;
                                } else {
                                    $start = ($cpage > 3)? $cpage - 3: $cpage;
                                    $stop = ($cpage + 3 < $maxpages)? $cpage + 3: $maxpages;
                                }

                                if($start > 1){
                                    ?><span class="pagination-button button-sep disabled">...</span><?php
                                }
                                for($i = $start; $i <= $stop; $i++){
                                    $active = ($i == $cpage)? "active": "";
                                    ?>
                                        <a href="<?php printf($link, $i); ?>" class="pagination-button button-number <?php echo $active; ?>"><?php echo $i; ?></a>
                                    <?php
                                }
                                if($stop < $maxpages){
                                    ?><span class="pagination-button button-sep disabled">...</span><?php
                                }
                            ?>

                            <?php if($next !== false){ ?>
                                <a href="<?php printf($link, $next); ?>" class="pagination-button button-next">&rsaquo;</a>
                                <a href="<?php printf($link, $maxpages); ?>" class="pagination-button button-last">&raquo;</a>
                            <?php } else { ?>
                                <span class="pagination-button button-next disabled">&rsaquo;</span>
                                <span class="pagination-button button-last disabled">&raquo;</span>
                            <?php } ?>
                        </div>
                    </div>
                <?php
            }
        }

        /*
         |  RENDER :: COMMENT
         |  @since  0.1.0
         */
        public function comment($comment, $uid, $depth){
            global $users, $security, $Snicker, $SnickerUsers;

            // Get Page
            $page = new Page($comment->page_key());
            $user = $SnickerUsers->getByString($comment->getValue("author"));

            // Render
            $token = $security->getTokenCSRF();
            $maxdepth = (int) sn_config("comment_depth");
            $url = $page->permalink() . "?action=snicker&snicker=rate&&uid=%s&tokenCSRF=%s";
            $url = sprintf($url, $comment->uid(), $token);
            ?>
                <div id="comment-<?php echo $comment->uid(); ?>" class="comment" style="margin-left: <?php echo (15 * ($depth - 1)); ?>px;">
                    <div class="table">
                        <div class="table-cell comment-avatar">
                            <?php echo $comment->avatar(90); ?>
                            <?php
                                if(isset($user["role"]) && $user["username"] === $page->username()){
                                    echo '<span class="avatar-role">Author</span>';
                                } else if(isset($user["role"]) && $user["role"] === "admin"){
                                    echo '<span class="avatar-role">Admin</span>';
                                }
                            ?>
                        </div>

                        <div class="table-cell comment-content">
                            <?php if(sn_config("comment_title") !== "disabled" && !empty($comment->title())){ ?>
                                <div class="comment-title">
                                    <?php echo $comment->title(); ?>
                                    <?php if($comment->status() === "pending"){ ?>
                                        <span class="comment-moderation"><?php sn_e("This comment hasn't been moderated yet!"); ?></span>
                                    <?php } ?>
                                </div>
                            <?php } else if($comment->status() === "pending"){ ?>
                                <div class="comment-moderation"><?php sn_e("This comment hasn't been moderated yet!"); ?></div>
                            <?php } ?>
                            <div class="comment-meta">
                                <span class="meta-author">
                                    <?php sn_e("Written by %s", array('<span class="author-username">'.$user["username"].'</span>')); ?>
                                </span>
                                <span class="meta-date">
                                    <?php sn_e("on %s", array($comment->date())); ?>
                                </span>
                            </div>
                            <div class="comment-comment">
                                <?php echo $comment->comment(); ?>
                            </div>
                        </div>
                    </div>

                    <div class="comment-action">
                        <div class="table">
                            <div class="table-cell align-left">
                                <?php if(sn_config("comment_enable_like")){ ?>
                                    <a href="<?php echo $url; ?>&type=like" class="action-like <?php echo ($Snicker->hasLiked($comment->uid())? "active": ""); ?>">
                                        <?php sn_e("Like"); ?> <span data-snicker="like"><?php echo $comment->like(); ?></span>
                                    </a>
                                <?php } ?>
                                <?php if(sn_config("comment_enable_dislike")){ ?>
                                    <a href="<?php echo $url; ?>&type=dislike" class="action-dislike <?php echo ($Snicker->hasDisliked($comment->uid())? "active": ""); ?>">
                                        <?php sn_e("Dislike"); ?> <span data-snicker="dislike"><?php echo $comment->dislike(); ?></span>
                                    </a>
                                <?php } ?>
                            </div>
                            <div class="table-cell align-right">
                                <?php if($maxdepth === 0 || $maxdepth > $comment->depth()){ ?>
                                    <a href="<?php echo $page->permalink(); ?>?snicker=reply&uid=<?php echo $comment->key(); ?>#snicker-comments-form" class="action-reply">
                                        <?php sn_e("Reply"); ?>
                                    </a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
        }
    }
