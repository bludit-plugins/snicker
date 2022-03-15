<?php
/*
 |  Snicker     The first native FlatFile Comment Plugin 4 Bludit
 |  @file       ./admin/index-users.php
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.1.2 [0.1.0] - Alpha
 |
 |  @website    https://github.com/pytesNET/snicker
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2019 SamBrishes, pytesNET <info@pytes.net>
 */
    if(!defined("BLUDIT")){ die("Go directly to Jail. Do not pass Go. Do not collect 200 Cookies!"); }

    global $SnickerUsers;

    // Get Data
    $page = max((isset($_GET["page"])? (int) $_GET["page"]: 1), 1);
    $limit = sn_config("frontend_per_page");
    $total = count($SnickerUsers->db);

    // Get Users
    $search = null;
    if(isset($_GET["view"]) && $_GET["view"] === "users"){
        $page = 1;
        $limit = -1;
        $search = isset($_GET["search"])? $_GET["search"]: null;
    }
    $users = $SnickerUsers->getList($search, $page, $limit);

    // Link
    $link = DOMAIN_ADMIN . "snicker?page=%d&tab=users#users";

?>
<div id="snicker-users" class="tab-pane">
    <div class="card shadow-sm" style="margin: 1.5rem 0;">
        <div class="card-body">
            <div class="row">
                <form class="col-sm-6" method="get" action="<?php echo DOMAIN_ADMIN; ?>snicker#users">
                    <div class="form-row align-items-center">
                        <div class="col-sm-8">
                            <input type="text" name="search" value="<?php echo $search; ?>" class="form-control" placeholder="<?php sn_e("Username or eMail Address"); ?>" />
                        </div>
                        <div class="col-sm-4">
                            <button class="btn btn-primary" name="view" value="users"><?php sn_e("Search Users"); ?></button>
                        </div>
                    </div>
                </form>

                <div class="col-sm-6 text-right">
                    <?php if($total > $limit){ ?>
                        <div class="btn-group btn-group-pagination">
                            <?php if($page <= 1){ ?>
                                <span class="btn btn-secondary disabled">&laquo;</span>
                                <span class="btn btn-secondary disabled">&lsaquo;</span>
                            <?php } else { ?>
                                <a href="<?php printf($link, 1); ?>" class="btn btn-secondary">&laquo;</a>
                                <a href="<?php printf($link, $page-1); ?>" class="btn btn-secondary">&lsaquo;</a>
                            <?php } ?>
                            <?php if(($page * $limit) < $total){ ?>
                                <a href="<?php printf($link, $page+1); ?>" class="btn btn-secondary">&rsaquo;</a>
                                <a href="<?php printf($link, ceil($total / $limit)); ?>" class="btn btn-secondary">&raquo;</a>
                            <?php } else { ?>
                                <span class="btn btn-secondary disabled">&rsaquo;</span>
                                <span class="btn btn-secondary disabled">&raquo;</span>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <?php if(!$users || count($users) === 0){ ?>
        <div class="row justify-content-md-center">
            <div class="col-sm-6">
                <div class="card w-100 shadow-sm bg-light">
                    <div class="card-body text-center p-4"><i><?php sn_e("No Users available"); ?></i></div>
                </div>
            </div>
        </div>
    <?php } else { ?>
        <?php $link = DOMAIN_ADMIN . "snicker?action=snicker&snicker=users&uuid=%s&handle=%s&tokenCSRF=" . $security->getTokenCSRF(); ?>
        <table class="table table-bordered table-hover-light shadow-sm mt-3">
            <?php foreach(array("thead", "tfoot") AS $tag){ ?>
                <<?php echo $tag; ?>>
                    <tr class="thead-light">
                        <th width="38%" class="border-0 p-3 text-uppercase text-muted"><?php sn_e("Username"); ?></th>
                        <th width="15%" class="border-0 p-3 text-uppercase text-muted"><?php sn_e("eMail"); ?></th>
                        <th width="22%" class="border-0 p-3 text-uppercase text-muted"><?php sn_e("Comments"); ?></th>
                        <th width="25%" class="border-0 p-3 text-uppercase text-muted text-center"><?php sn_e("Actions"); ?></th>
                    </tr>
                </<?php echo $tag; ?>>
            <?php } ?>

            <tbody class="shadow-sm-both">
                <?php foreach($users AS $uuid => $user){ ?>
                    <tr>
                        <td class="p-3">
                            <?php echo $user["username"]; ?>
                        </td>
                        <td class="p-3">
                            <?php echo $user["email"]; ?>
                        </td>
                        <td class="text-center align-middle pt-2 pb-2 pl-1 pr-1">
                            <a href="<?php echo DOMAIN_ADMIN; ?>snicker?view=user&user=<?php echo $uuid; ?>">
                                <?php echo count(isset($user["comments"])? $user["comments"]: array()); ?>
                                <?php sn_e("Comments"); ?>
                            </a>
                        </td>
                        <td class="text-center align-middle pt-2 pb-2 pl-1 pr-1">
                            <div class="btn-group">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-toggle="dropdown">
                                    <?php sn_e("Handle"); ?>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item text-danger" href="<?php printf($link, $uuid, "delete"); ?>&anonymize=true"><?php sn_e("Delete (Anonymize)"); ?></a>
                                    <a class="dropdown-item text-danger" href="<?php printf($link, $uuid, "delete"); ?>&anonymize=false"><?php sn_e("Delete (Completely)"); ?></a>
                                    <div class="dropdown-divider"></div>

                                    <?php if($user["blocked"]){ ?>
                                        <a class="dropdown-item" href="<?php printf($link, $uuid, "unblock"); ?>"><?php sn_e("Unblock User"); ?></a>
                                    <?php } else { ?>
                                        <a class="dropdown-item" href="<?php printf($link, $uuid, "block"); ?>"><?php sn_e("Block User"); ?></a>
                                    <?php } ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } ?>
</div>
