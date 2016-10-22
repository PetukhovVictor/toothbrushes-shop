<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="utf-8" />
        <title>Toothbrushes Shop</title>
        <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link href="/css/basis.css" rel="stylesheet" />
        <link href="/css/catalog/list.css" rel="stylesheet" />
    </head>
    <body>
        <header>
            <a href="/" class="logo"></a>
        </header>
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
            </div>
            <div class="items">
            <?php foreach($data["items"] as $item): ?>
                <div class="item">
                    <div class="item-image">
                        <a href="/catalog/item/<?=$item["id"]?>" class="item-image-link"><img src="<?=$item["image"]?>" alt="<?=$item["title"]?>" /></a>
                    </div>
                    <div class="item-title">
                        <a href="/catalog/item/<?=$item["id"]?>" class="item-title-link" title="<?=$item["title"]?>"><?=$item["title"]?></a>
                    </div>
                    <div class="item-price">
                        <?=$item["price"]?>
                    </div>
                </div>
            <?php endforeach ?>
            </div>
        </section>
        <script src="/js/general.js"></script>
        <script src="/js/catalog/list.js"></script>
    </body>
</html>