<!DOCTYPE html>
<html lang="ru">
    <head>
        <?php require(TEMPLATES_DIR . "/sections/includes.php") ?>
        <link href="/css/catalog/list.css" rel="stylesheet" />
    </head>
    <body>
        <?php require(TEMPLATES_DIR . "/sections/header.php") ?>
        <section class="content">
            <div class="title">
                <div class="options">
                    <div class="options-sort">
                        <a href="#" class="options-sort-setting-link">По возрастанию цены</a>
                    </div>
                    <div class="options-sort-setting">
                        <a href="#" class="options-sort-setting-item selected" data-type="price:asc">По возрастанию цены</a>
                        <a href="#" class="options-sort-setting-item" data-type="price:desc">По убыванию цены</a>
                        <div class="options-sort-setting-sep"></div>
                        <a href="#" class="options-sort-setting-item" data-type="id:desc">По новизне</a>
                    </div>
                </div>
                <h1>Каталог зубных щеток</h1>
                <a href="#" class="add-item"></a>
            </div>
            <div class="items">
            <?php foreach($data["items"] as $item): ?>
                <?php require(TEMPLATES_DIR . "/catalog/item.php") ?>
            <?php endforeach ?>
            </div>
        </section>
        <?php require(TEMPLATES_DIR . "/sections/footer.php") ?>
        <script src="/js/catalog/list.js"></script>
    </body>
</html>