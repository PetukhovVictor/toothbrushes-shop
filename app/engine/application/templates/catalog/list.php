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
        <div style="border: 1px solid gray; padding: 10px; border-radius: 3px; display: inline-block;">
            <?php foreach($items as $item): ?>
                <div style="padding: 10px;">
                    <img src="<?=$item["image"]?>" style="max-width: 200px; max-height: 200px" alt="" />
                </div>
                <div style="padding: 10px;text-align: center;">
                    <?=$item["title"]?>
                </div>
            <?php endforeach ?>
        </div>
    </body>
</html>