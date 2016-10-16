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
        <?php foreach($items as $item): ?>
        <div style="border: 1px dashed gray; text-align: center; margin: 5px; padding: 10px; float: left; border-radius: 3px; display: block;">
            <div style="padding: 10px;">
                <img src="<?=$item["image"]?>" style="max-width: 200px; max-height: 200px" alt="" />
            </div>
            <div style="padding: 10px;">
                <?=$item["title"]?>
            </div>
        </div>
        <?php endforeach ?>
        <script src="/js/general.js"></script>
        <script src="/js/catalog/list.js"></script>
    </body>
</html>