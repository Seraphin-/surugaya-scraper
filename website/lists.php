<?php
const IMPORT_TEMPLATE = 'let a=!function(){let e=$(".pager-last").children().eq(0).attr("href").split("page="),t=parseInt(e[1]);for(let a=1;a<=t;a++)$.get(e[0]+"page="+a,{},e=>{document.body.insertAdjacentHTML("beforeend",e)},"html");setTimeout(()=>{let e=[];$(".gnavBox").each((t,a)=>{e.push($(a).find(".img").children().eq(0).attr("href").split("/")[5])}),$.post("https://this-domain/scraper-site/ajax/import_list",{key:"$key$",items:e},e=>{alert(e),window.location.reload()})},1e3)}();';

require_once 'nav.php';
require_once 'in/lib/random.php';
$query = $GLOBALS['db']->prepare('SELECT rowid, * FROM lists WHERE `user` = ?');
$query->execute([$GLOBALS['user'][0]]);
$lists = $query->fetchAll(PDO::FETCH_NUM);
foreach($lists as $list) {
    if(is_null($list[6])) {
        $list[6] = bin2hex(random_bytes(32));
        $query = $GLOBALS['db']->prepare('UPDATE lists SET `key` = ? WHERE `rowid` = ?');
        $query->execute([$list[6], $list[0]]);
    }
}
$type_list = $GLOBALS['db']->query('SELECT * FROM change_types ORDER BY id ASC', PDO::FETCH_NUM)->fetchAll();
?>
<div class="container row m-auto">
    <div class="alert alert-dark col-12">
        Please don't abuse this system with an excessive number of lists.<br>
        Click on a list to edit its properties.<br>
        The official website is much faster for stock notifications, it is better to use that when possible since there is no limit.<br>
        The default list is the one an item will be added to by default when using the quick shortcut on the items page.
        <hr>
        To import a list from suruga-ya.jp's website (favorite or watchlist)...
        <ul>
            <li>right click on the "Import Bookmark" link and bookmark it</li>
                <li>if using uBlock Origin, whitelist this domain for XHR (or disable matrix)</li>
            <li>run the bookmark while on the <b>first</b> page of the list and wait 5 seconds</li>
        </ul>
        <hr>
        Lists will send an email if...<br>
        <ul>
            <li>a change matches a selected type</li>
            <li>if any filters are set, either all (AND) or at least one (OR) of them are matched </li>
            <li>if any specific items are set, the item must be in that list</li>
        </ul>
    </div>
    <div class="row mr-auto ml-auto">
        <form>
            <div class="form-inline">
                <label class="badge badge-pill badge-info ml-1 mr-1" for="name">Name</label>
                <input type="text" name="name">
                <button type="submit" class="btn btn-primary update-filters ml-1" id="new_list">New List</button>
            </div>
        </form>
    </div>
</div>
<br>
<div class="container-fluid">
    <table class="table table-dark">
        <thead>
            <tr id="th">
                <th scope="col">Name</th>
                <th scope="col">Triggers On</th>
                <th scope="col">Filters</th>
                <th scope="col">Filter Mode</th>
                <th scope="col">Items</th>
                <th scope="col">Import Bookmark</th>
                <th scope="col">Default</th>
                <th scope="col">Enabled</th>
                <th scope="col">Delete</th>
            </tr>
        </thead>
        <tbody id="lists">
        <?php
        foreach ($lists as $list) {
            $query = $GLOBALS['db']->prepare('SELECT * FROM list_triggers WHERE `list` = ?');
            $query->execute([$list[0]]);
            $triggers = $query->fetchAll(PDO::FETCH_NUM);
            $triggerstr = '';
            foreach($triggers as $trigger) $triggerstr .= $type_list[$trigger[1]][1] . ' ';
            if($triggerstr == '') $triggerstr = '<span class="badge badge-warning">Nothing</span>';
            $query = $GLOBALS['db']->prepare('SELECT COUNT(*) FROM list_filters WHERE `list` = ?');
            $query->execute([$list[0]]);
            $filters = $query->fetchColumn();
            if($filters == 0) $filters = '<span class="badge badge-primary">None</span>';
            $query = $GLOBALS['db']->prepare('SELECT COUNT(*) FROM list_items WHERE `list` = ?');
            $query->execute([$list[0]]);
            $items = $query->fetchColumn();
            if($items == 0) $items = '<span class="badge badge-primary">All</span>';
            if($list[3] == '1') $defaultBtn = '<button type="button" class="btn btn-success set-default" value="yes">Yes</button>';
            else $defaultBtn = '<button type="button" class="btn btn-primary set-default" value="no">No</button>';
            if($list[4] == '1') $enabledBtn = '<button type="button" class="btn btn-success set-enabled" value="yes">Yes</button>';
            else $enabledBtn = '<button type="button" class="btn btn-danger set-enabled" value="no">No</button>';
            if($list[5] == '1') $filterBtn = '<button type="button" class="btn btn-primary set-filter" value="yes">AND</button>';
            else $filterBtn = '<button type="button" class="btn btn-primary set-filter" value="no">OR</button>';
            ?>
            <tr data-id="<?= $list[0] ?>">
                <td scope="row"><?= $list[2] ?></td>
                <td><?= $triggerstr ?></td>
                <td><?= $filters ?></td>
                <td><?= $filterBtn ?></td>
                <td><?= $items ?></td>
                <td><a href='javascript:<?= str_replace('$key$', $list[6], IMPORT_TEMPLATE) ?>'>Link</a></td>
                <td><?= $defaultBtn ?></td>
                <td><?= $enabledBtn ?></td>
                <td><button type="button" class="btn btn-danger delete-list">Delete</button></td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>
</div>
<script type="text/javascript">
    $(() => {
        $('tr:not(#th)').on('click', function(ev) {
            window.location.replace('/scraper-site/list/' + $(this).data('id'));
        });
        $('tr').on('click', '.set-default', function(ev) {
            ev.preventDefault();
            ev.stopPropagation();
            let def = $(ev.target).val() == 'yes';
            $.post('/scraper-site/ajax/update_list', {list: $(ev.target).parent().parent().data('id'), default: !def}, function() {
                let text = def ? 'No' : 'Yes';
                $('.set-default').addClass('btn-primary').removeClass('btn-success').val('no').text('No');
                $(ev.target).val(text.toLowerCase()).text(text).addClass(def ? 'btn-primary' : 'btn-success').removeClass(def ? 'btn-success' : 'btn-primary');
            });
        });
        $('tr').on('click', '.set-enabled', function(ev) {
            ev.preventDefault();
            ev.stopPropagation();
            let def = $(ev.target).val() == 'yes';
            $.post('/scraper-site/ajax/update_list', {list: $(ev.target).parent().parent().data('id'), enabled: !def}, function() {
                let text = def ? 'No' : 'Yes';
                $(ev.target).val(text.toLowerCase()).text(text).addClass(def ? 'btn-danger' : 'btn-success').removeClass(def ? 'btn-success' : 'btn-danger');
            });
        });
        $('tr').on('click', '.set-filter', function(ev) {
            ev.preventDefault();
            ev.stopPropagation();
            let def = $(ev.target).val() == 'yes';
            $.post('/scraper-site/ajax/update_list', {list: $(ev.target).parent().parent().data('id'), mode: !def}, function() {
                let text = def ? 'OR' : 'AND';
                let val = def ? 'no' : 'yes';
                $(ev.target).val(val).text(text);
            });
        });
        $('tr').on('click', '.delete-list', function(ev) {
            ev.preventDefault();
            ev.stopPropagation();
            if(confirm("Delete list?")) {
                $.post('/scraper-site/ajax/delete_list', {list: $(ev.target).parent().parent().data('id')}, function() {
                    location.reload();
                });
            }
        });

        $('#new_list').on('click', function(ev) {
            ev.preventDefault();
            ev.stopPropagation();
            $.post('/scraper-site/ajax/new_list', {name: $('input[name=name]').val()}, function() {
                location.reload();
            });
        });
    });
</script>
