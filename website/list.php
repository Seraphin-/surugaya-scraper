<?php
const OPS = ['=', 'IN', '>', '<'];
include 'nav.php';
$query = $GLOBALS['db']->prepare('SELECT rowid, * FROM lists WHERE `user` = ? AND `rowid` = ?');
$query->execute([$GLOBALS['user'][0], $GLOBALS['list']]);
$list_array = $query->fetch(PDO::FETCH_NUM);
if(!$list_array) {
    ?>
    <div class="alert alert-danger">That ain't your list, buddy! Or it doesn't exist!!!</div>
    <?php
    return;
}
$type_list = $GLOBALS['db']->query('SELECT * FROM change_types ORDER BY id ASC', PDO::FETCH_NUM)->fetchAll();
$query = $GLOBALS['db']->prepare('SELECT change_type FROM list_triggers WHERE `list` = ?');
$query->execute([intval($GLOBALS['list'])]);
$triggers = $query->fetchAll(PDO::FETCH_COLUMN);
$query = $GLOBALS['db']->prepare('SELECT * FROM list_filters WHERE `list` = ?');
$query->execute([intval($GLOBALS['list'])]);
$filters = $query->fetchAll(PDO::FETCH_NUM);
$query = $GLOBALS['db']->prepare('SELECT productid FROM list_items WHERE `list` = ?');
$query->execute([intval($GLOBALS['list'])]);
$products = $query->fetchAll(PDO::FETCH_COLUMN);
?>
<div class="container-fluid">
    <h2 class="text-light m-auto d-inline">List: <?= $list_array[2] ?></h2>
    <button type="button" class="btn btn-success d-inline ml-3" id="save_filter">Save</button>
    <div class="alert alert-warning d-inline float-right">Make sure to save your changes with the button on the left!</div>
</div>
<div class="container-fluid row mt-2">
    <div class="col-4">
        <div>
            <h3 class="text-light">Triggers</h3>
        </div>
        <div id="trigger_buttons" class="row">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
    </div>
    <div class="col-4">
        <h3 class="text-light">Filters</h3>
        <h4 class="text-light">New Filter</h4>
        <form>
            <div class="form-inline">
                <div class="text-light badge badge-info col-4">-</div>
                <div class="text-light badge badge-secondary col-2">Operator</div>
                <div class="text-light badge badge-info col-4">-</div>
            </div>
            <div class="form-inline">
                <select class="form-control col-4" name="filter_from" id="filter_from">
                    <?php
                    foreach ($type_list as $type) {
                        if($type[0] == '0'){
                            print('<option value="-1">Custom Text</option>');
                            print('<option value="0">Old Value</option>');
                        }
                        else print("<option value='{$type[0]}'>{$type[1]}</option>");
                    }
                    ?>
                </select>
                <select class="form-control col-2" name="filter_op" id="filter_op">
                    <?php
                    foreach (OPS as $k => $v) {
                        ?>
                        <option value="<?= $k ?>"><?= $v ?></option>
                        <?php
                    }
                    ?>
                </select>
                <select class="form-control col-4" name="filter_to" id="filter_to">
                    <?php
                    foreach ($type_list as $type) {
                        if($type[0] == '0') {
                            print('<option value="-1">Custom Text</option>');
                            print('<option value="0">New Value</option>');
                        }
                        else print("<option value='{$type[0]}'>{$type[1]}</option>");
                    }
                    ?>
                </select>
            </div>
            <div class="form-inline mt-1">
                <div class="text-light badge badge-info">Custom Text: </div>
                <input type="text" class="form-control ml-1" name="filter_text" id="filter_text">
                <button type="submit" class="btn btn-success ml-1" id="new_filter">Create</button>
            </div>
        </form>
        <h4 class="text-light">Filters</h4>
        <table class="table table-dark">
            <thead>
            <tr>
                <th scope="col">Old</th>
                <th scope="col">Operator</th>
                <th scope="col">New</th>
                <th scope="col">Text</th>
                <th scope="col">Delete</th>
            </tr>
            </thead>
            <tbody id="filters">
            <td class="spinner-border text-primary" role="status">
            </tbody>
        </table>
    </div>
    <div class="col-4">
        <h3 class="text-light">Products</h3>
        <h4 class="text-light">Add by ID</h4>
        <form>
            <div class="form-inline">
                <div class="text-light badge badge-info">Product ID: </div>
                <input type="text" class="form-control ml-1" name="product_id" id="product_id">
                <button type="submit" class="btn btn-success ml-1" id="new_product">Add</button>
            </div>
        </form>
        <h4 class="text-light">Products</h4> <span class="text-light"><i>(items must be in this list if any are set)</i></span>
        <table class="table table-dark">
            <thead>
            <tr>
                <th scope="col">ID (click to open in new tab)</th>
                <th scope="col">Delete</th>
            </tr>
            </thead>
            <tbody id="products">
            <td class="spinner-border text-primary" role="status">
            </tbody>
        </table>
    </div>
</div>
<script type="text/javascript">
    $(() => {
        const OPS = ['=', 'IN', '>', '<'];
        // noinspection JSAnnotator
        const types = <?= json_encode($type_list) ?>;
        let active_types = <?= json_encode($triggers) ?>;
        let filters = <?= json_encode($filters) ?>;
        let products = <?= json_encode($products) ?>;

        $('#trigger_buttons').empty();
        types.forEach((el) => {
            let div = $('<div>').addClass('col-sm-12').addClass('col-md-6').addClass('col-xl-4');
            div.append($('<button>').addClass('btn').addClass('btn-primary').addClass('mr-1').addClass('mt-1').val(el[0]).text(el[1]));
            $('#trigger_buttons').append(div);
        });
        function updateTypeDisplay() {
            types.forEach((el) => {
                if(active_types.includes(el[0])) $('#trigger_buttons button[value=' + el[0] + ']').addClass('active');
                else $('#trigger_buttons button[value=' + el[0] + ']').removeClass('active');
            });
        }
        updateTypeDisplay();
        $('#trigger_buttons').on('click', 'button', function(ev) {
            if($(ev.target).hasClass('active')) active_types.pop($(ev.target).val());
            else active_types.push($(ev.target).val());
            updateTypeDisplay();
        });

        function updateFilterDisplay() {
            let tbody = $('#filters').empty();
            filters.forEach(function(el, i) {
                let row = $('<tr>');
                let el_1 = parseInt(el[1]);
                let el_2 = parseInt(el[2]);
                if(el_1 > 0) el_1 = types[el_1][1];
                else if(el_1 == 0) el_1 = "Old Value";
                else if(el_1 == -1) el_1 = "Text";
                if(el_2 > 0) el_2 = types[el_2][1];
                else if(el_2 == 0) el_2 = "New Value";
                else if(el_2 == -1) el_2 = "Text";
                row.append($('<td scope="row">').text(el_1));
                row.append($('<td>').text(OPS[el[3]]));
                row.append($('<td>').text(el_2));
                row.append($('<td>').text(el[4]));
                row.append($('<td>').append($('<button>').addClass('btn').addClass('btn-danger').addClass('remove-filter').text('Delete')));
                row.data('index', i);
                tbody.append(row);
            });
        };
        $('#filters').on('click', '.remove-filter', function(ev) {
            ev.preventDefault();
            if(confirm('Delete?')) {
                filters.pop($(ev.target).parent().data('index'));
                updateFilterDisplay();
            }
        });
        $('#new_filter').on('click', function(ev) {
            ev.preventDefault();
            let filter_from = $('#filter_from').find(':selected').val();
            let filter_to = $('#filter_to').find(':selected').val();
            let filter_op = $('#filter_op').find(':selected').val();
            let text = $('#filter_text').val();
            if(text == '') text = 'None';
            filters.push([<?= $GLOBALS['list'] ?>, filter_from, filter_to, filter_op, text]);
            updateFilterDisplay();
        });
        updateFilterDisplay();

        function updateProductDisplay() {
            let tbody = $('#products').empty();
            products.forEach(function(el) {
                let row = $('<tr>');
                row.append($('<td scope="row">').append($('<a>').attr('href', 'https://www.suruga-ya.jp/product/detail/' + el).attr('target', '_blank').text(el)));
                row.append($('<td>').append($('<button>').addClass('btn').addClass('btn-danger').addClass('remove-product').text('Delete')));
                tbody.append(row);
            });
        }
        $('#products').on('click', '.remove-product', function(ev) {
            ev.preventDefault();
            if(confirm('Delete?')) {
                products.pop($(ev.target).text());
                updateProductDisplay();
            }
        });
        $('#new_product').on('click', function(ev) {
            ev.preventDefault();
            products.push($('#product_id').val());
            updateProductDisplay();
        });
        updateProductDisplay();

        $('#save_filter').on('click', function (ev) {
            $.post('/scraper-site/ajax/update_list', {types: active_types, filters: filters, products: products, list: <?= $GLOBALS['list'] ?>}, function(ev) {
                alert("Saved!");
            });
        });
    });
</script>