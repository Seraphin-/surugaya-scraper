<?php
require_once 'nav.php';
$type_list = $GLOBALS['db']->query('SELECT * FROM change_types ORDER BY id ASC', PDO::FETCH_NUM);
$types = [];
foreach($type_list as $type) {
    $types[$type[0]] = $type[1];
}
?>
<div class="container row m-auto">
    <div class="alert alert-dark col-12">
        Only the last 1000 matching the query filter are shown. Use "-1" for Out of Stock in From and To.<br>
        Click on an item's name to go to suruga-ya.jp (or * for .com)'s listing, and the ID to see its history.<br>
        The stock type is a combined form of Price and Condition, and thus cannot be filtered like them.<br>
        Timesale automatically combines price information if it is displayed. (Collapsed items: <span id="collapsed">0</span>)
    </div>
    <div class="row m-auto">
        <button class="btn btn-secondary mr-1 filter-type" value="-1">All
            <span class="badge badge-light">?</span></button>
        <?php
        foreach($types as $type => $name):
        ?>
        <button class="btn btn-secondary mr-1 filter-type" value="<?= $type ?>"><?= $name ?>
            <span class="badge badge-light">?</span></button>
        <?php
        endforeach;
        ?>
    </div>
</div>
<div class="container-fluid row m-auto">
    <div class="row mr-auto ml-auto mt-2">
        <form>
            <div class="form-inline">
                <label class="badge badge-pill badge-info mr-1" for="productid">ID</label>
                <input type="text" name="productid">
                <label class="badge badge-pill badge-info mr-1" for="from">From</label>
                <input type="text" name="from">
                <label class="badge badge-pill badge-info ml-1 mr-1" for="to">To</label>
                <input type="text" name="to">
                <label class="badge badge-pill badge-info ml-1 mr-1" for="name">Name</label>
                <input type="text" name="name">
                <button type="submit" class="btn btn-primary update-filters ml-1">Submit</button>
                <button type="reset" class="btn btn-warning reset-filters ml-1">Reset All</button>
            </div>
        </form>
    </div>
</div>
<br>
<div class="container-fluid">
    <table class="table table-dark">
        <thead>
        <tr>
            <th scope="col">Type</th>
            <th scope="col">From</th>
            <th scope="col">To</th>
            <th scope="col">Name</th>
            <th scope="col">ID</th>
            <th scope="col">Found (Local Time)</th>
        </tr>
        </thead>
        <tbody id="changes">
        </tbody>
    </table>
</div>
<script type="text/javascript">
    $(() => {
        // noinspection JSAnnotator
        const types = <?= json_encode(array_merge($types, ['Stock'])) ?>;
        const conditions = ['Out of Stock', 'Used', 'New'];
        let changes = [];
        const urlParams = new URLSearchParams(window.location.search);
        let filters = {};
        urlParams.forEach((v, k) => {
           filters[k] = v;
           $('input[name=' + k + ']').val(v);
        });

        function parseChanges(data) {
            changes = data;
            let counts = Array(<?= count($types) ?>).fill(0);
            let collapsed = 0;
            let tbody = $("#changes");
            tbody.empty();
            for(let i = 0; i < changes.length; i++) {
                let item = changes[i];
                counts[item.type] += 1;
                if(item.type == 3 && i < changes.length - 1 && changes[i+1].type == 6) {
                    i++;
                    collapsed++;
                    counts[changes[i].type] += 1;
                    item.type = types.length - 1;
                    if(item.to !== '-1') item.to = item.to + ' / ' + conditions[changes[i].to];
                } else if(item.type == 3 && i < changes.length - 1 && changes[i+1].type == 8) {
                    i++;
                    collapsed++;
                    counts[changes[i].type] += 1;
                    item.type = 8;
                    if(changes[i].from === '0') item.from = 'No';
                    else item.to = 'No';
                } else if(item.type == 6) {
                    item.from = conditions[item.from];
                    item.to = conditions[item.to];
                }
                if(item.from == '-1') item.from = 'Out of Stock';
                if(item.to == '-1') item.to = 'Out of Stock';
                let row = $('<tr>').tooltip({title: item.price + ' / ' + item.release});
                row.append($('<td scope="row">').text(types[item.type]));
                row.append($('<td>').text(item.from));
                row.append($('<td>').text(item.to));
                row.append($('<td>').append($('<a>').attr('href', 'https://www.suruga-ya.jp/product/detail/' + item.productid).text(item.name))
                    .append($('<a>').addClass('text-warning').attr('href', 'https://www.suruga-ya.com/en/product/' + item.productid).text('*')));
                row.append($('<td>').append($('<a>').addClass('productid').attr('href', '#').text(item.productid)));
                let date = new Date(item.found.replace(' ', 'T') + 'Z');
                row.append($('<td>').text(date.toLocaleString()));
                tbody.append(row);
            };
            counts.forEach((val, index) => {
                $('.filter-type[value=' + index + ']').children().text(val);
            });
            $('.filter-type[value=-1]').children().text(counts.reduce((a, b) => a + b));
            $('#collapsed').text(collapsed);
        }
        function getRecent() {
            let tbody = $("#changes");
            tbody.empty();
            let row = $('<tr>');
            row.append($('<td class="spinner-border text-primary" role="status">'));
            tbody.append(row);
            $.get('/scraper-site/ajax/changes', filters, parseChanges, 'json');
        }

        $('.filter-type').on('click', function(ev) {
            ev.preventDefault();
            if(ev.target.value == '-1') delete filters.type;
            else filters.type = ev.target.value;
            getRecent();
        });

        $('#changes').on('click', '.productid', function(ev) {
            ev.preventDefault();
            $('input[name=productid]').val(ev.target.text);
            filters.productid = ev.target.text;
            getRecent();
        });

        $('.update-filters').on('click', function (ev) {
            ev.preventDefault();
            let parent = $(ev.target.parentElement);
            if(parent.children().eq(1).val()) filters.productid = parent.children().eq(1).val();
            else delete filters.productid;
            if(parent.children().eq(3).val()) filters.from = parent.children().eq(3).val();
            else delete filters.from;
            if(parent.children().eq(5).val()) filters.to = parent.children().eq(5).val();
            else delete filters.to;
            if(parent.children().eq(7).val()) filters.name = parent.children().eq(7).val();
            else delete filters.name;
            getRecent();
        });

        $('.reset-filters').on('click', function() {
            filters = {};
            getRecent();
        });

        history.pushState({}, "home", "home"); //reset url
        getRecent();
    });
</script>
