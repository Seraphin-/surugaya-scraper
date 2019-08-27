<?php
require_once 'nav.php';
$query = $GLOBALS['db']->prepare('SELECT list_items.`productid` FROM list_items LEFT JOIN lists ON list_items.`list` = lists.`rowid` WHERE lists.`default` = 1 AND lists.`user` = ?');
$query->execute([$GLOBALS['user'][0]]);
$products = $query->fetch(PDO::FETCH_COLUMN);
if(!$products) $products = [];
?>
<form>
    <div class="form-inline m-3">
        <input type="text" class="form-control" name="search">
        <button class="ml-2 btn btn-info" id="search">Search</button>
        <span class="ml-2 badge badge-light"><i>(all fields)</i></span>
        <div class="alert alert-info ml-5 mr-auto">
            Click on an item to see its change history. Click on the upper right to add or remove it to your default list.
        </div>
        <div class="float-right text-right text-light" id="time"></div>
    </div>
</form>
<div class="container-fluid">
    <div class="row" id="items">
        <div class="alert alert-info col-10 text-center m-auto">
            Please make a search query.
        </div>
    </div>
</div>
<!--suppress JSIgnoredPromiseFromCall -->
<script type="text/javascript">
    $(() => {
        // noinspection JSAnnotator
        const INITIAL_ADDED = <?= json_encode($products); ?>;
        function processItems(d) {
            $("#time").text("Query time: " + d[0][d[0].length - 1] + "s");
            if(d.length == 1 && d[0].length == 1) {
                $('#items').empty();
                $('#items').append('<div class="alert alert-danger ml-3">No results...</div>');
                return;
            }
            let items = $("#items");
            items.empty();
            d.forEach((item) => {
                //TODO card actions - ul/ur add to list, bl/br open on site
                let card = $('<div class="card ml-1 mr-1 mt-3">');
                let body = $('<div class="card-body" style="height: 130px">'); //10 + 24 + 24 * rows of text
                body.append($('<span class="card-text text-truncate d-block">').text("Name: " + item[1]));
                body.append($('<span class="card-text text-truncate d-block">').text("Circle: " + item[2]));
                let phtml = 'Price: ' + item[3];
                if(item[3] == "-1") {
                    phtml = 'Price: <span class="text-danger">Out of Stock</span>';
                }
                let condition;
                switch (item[6]) {
                    case '1':
                        condition = 'USED';
                        break;
                    case '2':
                        condition = '<span class="text-success">NEW</span>';
                        break;
                    default:
                        condition = 'N/A';
                }
                condition = 'Condition: ' + condition;
                body.append($('<span class="card-text text-truncate d-block">').html(phtml));
                body.append($('<span class="card-text text-truncate d-block">').html(condition));
                let imgdiv = $('<div class="overflow-hidden position-relative" style="padding-bottom: 100%;">');
                let listtoggle = $('<div class="position-relative float-right triangle-topright">');
                if(INITIAL_ADDED.includes(item[0])) listtoggle.addClass('remove-list').css('border-top', '100px solid red');
                else listtoggle.addClass('add-list');
                imgdiv.append(listtoggle);
                imgdiv.append($('<img class="card-img-top rounded position-absolute">').attr('src', item[4].replace(/size=m/g, 'size=l')));
                card.append(imgdiv);
                card.append(body);
                items.append($('<a class="col-lg-3 col-md-4 col-sm-6 col-xs-12 p-0 text-dark" style="text-decoration: none;">').data('id', item[0]).attr('href', 'home?productid=' + item[0]).append(card));
            });
        }
        $('#search').on('click', (ev) => {
            ev.preventDefault();
            $.get('ajax/items', {search: $('input[name=search]').val()}, processItems, 'json');
        });
        $('#items').on('click', '.add-list', (ev) => {
            ev.preventDefault();
            ev.stopPropagation();
            $.post('ajax/add_default_list', {id: $(ev.target).parent().parent().parent().data('id')}, () => {
                $(ev.target).css('border-top', '100px solid red').removeClass('add-list').addClass('remove-list');
            });
            return false;
        });
        $('#items').on('click', '.remove-list', (ev) => {
            ev.preventDefault();
            ev.stopPropagation();
            $.post('ajax/remove_default_list', {id: $(ev.target).parent().parent().parent().data('id')}, () => {
                $(ev.target).css('border-top', '100px solid green').addClass('add-list').removeClass('remove-list');
            });
            return false;
        });
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('timesale') !== null){
            $.get('ajax/items', {timesale: 1}, processItems, 'json');
        }
    });
</script>