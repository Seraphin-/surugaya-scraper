<?php
$sitedb = new PDO("sqlite:/surugaya-site.db3");
$itemdb = new PDO("sqlite:/surugaya.db3");
$lists = $sitedb->query('SELECT `rowid`, `user`, `name` FROM lists WHERE enabled = 1', PDO::FETCH_NUM);
$type_list = $sitedb->query('SELECT `name` FROM change_types ORDER BY id ASC')->fetchAll(PDO::FETCH_COLUMN);
$recent_changes = $itemdb->query('SELECT changes.`type`, changes.`from`, changes.`to`, changes.`productid`, '
    . 'items.`name`, items.`circle`, items.`price`, items.`image`, items.`release`, items.`condition`, items.`status`, items.`timesale` '
    . 'FROM changes LEFT JOIN items ON changes.productid = items.productid WHERE found > datetime("now", "-20 minutes")')->fetchAll(PDO::FETCH_NUM);
foreach($lists as $list) {
    $query = $sitedb->query('SELECT email FROM users WHERE `rowid` = ?');
    $query->execute([$list[1]]);
    try {
        $user = $query->fetch(PDO::FETCH_NUM);
        if(empty($user[0])) continue;
        $query = $sitedb->prepare('SELECT change_type FROM list_triggers WHERE `list` = ?');
        $query->execute([$list[0]]);
        $triggers = $query->fetchAll(PDO::FETCH_COLUMN);
        $query = $sitedb->prepare('SELECT * FROM list_filters WHERE `list` = ?');
        $query->execute([$list[0]]);
        $filters = $query->fetchAll(PDO::FETCH_NUM);
        $query = $sitedb->prepare('SELECT productid FROM list_items WHERE `list` = ?');
        $query->execute([$list[0]]);
        $products = $query->fetchAll(PDO::FETCH_COLUMN);

        $found_changes = [];
        if(count($filters) > 0) {
            foreach($recent_changes as $change) {
                if(!in_array($change[0], $triggers)) continue;
                $found = $list[2] == '0' ? false : true;
                foreach($filters as $filter) {
                    if($filter[1] == '-1') $from = $filter[4];
                    elseif($filter[1] == '0') $from = $change[1];
                    else $from = $change[intval($filter[1]) + 3];
                    if($filter[2] == '-1') $to = $filter[4];
                    elseif($filter[2] == '0') $to = $change[1];
                    else $to = $change[intval($filter[2]) + 3];

                    if($filter[3] == '0') {
                        if($from === $to) continue;
                    } elseif($filter[3] == '1') {
                        if(stripos($to, $from) !== false) continue;
                    } elseif($filter[3] == '2') {
                        if(intval($from) > intval($to)) continue;
                    } elseif($filter[3] == '3')
                        if(intval($from) < intval($to)) continue;
                    if($list[2] == '0') {
                        $found = true;
                    } else {
                        $found = false;
                    }
                    break;
                }
                if($found) $found_changes[] = $change;
            }
        } else
            foreach($recent_changes as $change)
                if(in_array($change[0], $triggers)) $found_changes[] = $change;
        if(count($products) > 0)
            foreach($found_changes as $change)
                if(!in_array($change[3], $products)) array_splice($found_changes, array_search($change[3], $products));
        if(count($found_changes) > 0) {
            $changes_string = '[List ' . $list[2] . "]\r\n";
            foreach($found_changes as $change) {
                $changes_string .= '[' . $type_list[$change[0]] . '] ' . $change[3] . ' / ' . $change[4] .
                    "\r\n\t" . $change[1] . ' => ' . $change[2] .
                    "\r\n\thttps://www.suruga-ya.jp/product/detail/" . $change[3] . " https://www.suruga-ya.com/en/product/" . $change[3] . "\r\n";
            }
            mail($user[0], '[Surugaya] New notifications for list ' . $list[2] . '!',
                "Here are changes marked by the list:\r\n".$changes_string.
                "\r\nAll emails are text-only. Do not reply to this email. It will bounce.",
                'From: email');
        }
        print(count($found_changes));
        print(" changes for list " . $list[0] . "\n");

    } catch (Exception $e) {
        mail('admin@email', 'A list ' . $list[0] . ' is broken',
            str_replace("\r", "\r\n", $e->getMessage()),
            'From: email');
    }
}