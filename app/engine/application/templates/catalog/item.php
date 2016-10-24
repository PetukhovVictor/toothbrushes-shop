<a href="/catalog/item/<?=$item["id"]?>" data-id="<?=$item["id"]?>" draggable="false" class="item">
    <span class="item-controls">
        <span class="item-controls-edit"></span>
        <span class="item-controls-delete"></span>
    </span>
    <span class="item-image">
        <span class="item-image-container"><img src="<?=$item["image"]?>" alt="<?=$item["title"]?>" /></span>
    </span>
    <span class="item-title">
        <span class="item-title-link" title="<?=$item["title"]?>"><?=$item["title"]?></span>
    </span>
    <span class="item-price">
        <?=$item["price"]?> &#8381;
    </span>
</a>