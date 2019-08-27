<nav class="navbar navbar-dark bg-dark navbar-expand">
    <span class="navbar-brand h1">Suruga-ya Tracking</span>
    <ul class="navbar-nav mr-auto">
        <li class="nav-item<?= $GLOBALS['path'] == 'home' ? ' active' : '' ?>">
            <a class="nav-link" href="/scraper-site/home">Home</a>
        </li>
        <li class="nav-item<?= $GLOBALS['path'] == 'items' ? ' active' : '' ?>">
            <a class="nav-link" href="/scraper-site/items">Items</a>
        </li>
        <li class="nav-item<?= $GLOBALS['path'] == 'lists' ? ' active' : '' ?>">
            <a class="nav-link" href="/scraper-site/lists">Lists</a>
        </li>
        <li class="nav-item<?= $GLOBALS['path'] == 'settings' ? ' active' : '' ?>">
            <a class="nav-link" href="/scraper-site/settings">Settings</a>
        </li>
    </ul>
    <div class="nav-item">
        <a class="btn btn-success mr-1" href="/scraper-site/items?timesale">Timesale</a>
        <a class="btn btn-danger" href="/scraper-site/logout">Log Out</a>
    </div>
</nav>