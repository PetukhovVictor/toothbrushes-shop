String.prototype.format = function(replacement, prefix) {
    var format = this,
        match,
        prefix = prefix || "";
    for (var i = 0; match = new RegExp("\\{" + prefix + "(\\d+|\\w+)?\\}", "gm").exec(format); i++) {
        var key = match[1];
        if (key) {
            format = format.replace(new RegExp("\\{" + prefix + key + "\\}", "gm"), replacement[key] ? replacement[key] : "");
        }
    }
    return format;
};

function Popup(params) {
    this.params = params || {};
    this.templates = {
        wrap: "<div class='popup-shadow'></div><div class='popup-body'><div class='popup-controls'><a href='#' class='popup-control-close'></a></div><div class='popup-title'>{title}</div><div class='popup-content'>{content}</div></div>"
    };
    this.handlers = function () {
        document.getElementsByClassName("popup-shadow")[0].onclick = this.close;
        document.getElementsByClassName("popup-body")[0].querySelector(".popup-controls .popup-control-close").onclick = this.close;
    };
    this.close = function () {
        var popupElement = document.getElementsByClassName("popup")[0];
        popupElement.parentNode.removeChild(popupElement);
        document.body.classList.remove("fixed");
        return false;
    };
    this.show = function () {
        var popupContainer = document.createElement('div');
        popupContainer.classList.add("popup");
        popupContainer.classList.add(params.name);
        popupContainer.innerHTML = this.templates.wrap.format({
            title: this.params.title,
            content: this.params.content
        });
        document.body.appendChild(popupContainer);
        document.body.classList.add("fixed");
        this.handlers();
    };
}

function ItemList() {
    this.templates = {
        itemLoadingInfo: "<div class='item-loading'></div>"
    };
    this.fixingToolbar = function () {
        var title = document.getElementsByClassName("title")[0],
            items = document.getElementsByClassName("items")[0];
        if (window.scrollY > 18) {
            title.classList.add("fix");
            items.classList.add("shifted");
        } else {
            title.classList.remove("fix");
            items.classList.remove("shifted");
        }
    };
    this.showSortingBlock = function () {
        var sortingBlock = document.getElementsByClassName("options-sort-setting")[0];
        if (sortingBlock.classList.contains("showed")) {
            sortingBlock.classList.remove("showed");
        } else {
            sortingBlock.classList.add("showed");
        }
        return false;
    };
    this.showItem = function (e) {
        var isImageClick = e.target.closest(".item-image-link") != null,
            isLinkClick = e.target.classList.contains("item-title-link");
        if (!isImageClick && !isLinkClick) {
            return false;
        }
        var linkElement = isLinkClick ? e.target : e.target.closest(".item-image-link"),
            itemTitle = linkElement.closest(".item").querySelector(".item-title-link").textContent;
        window.history.pushState({}, "test", linkElement.href);
        var itemPopup = new Popup({
            name: "item-info",
            title: itemTitle,
            content: this.templates.itemLoadingInfo
        });
        itemPopup.show();
        return false;
    };
    this.hideSortingBlock = function (e) {
        if (!e.target.classList.contains("options-sort") && !e.target.parentNode.classList.contains("options-sort")) {
            document.getElementsByClassName("options-sort-setting")[0].classList.remove("showed");
        }
    };
    this.handlers = function () {
        document.getElementsByClassName("options-sort")[0].onclick = itemList.showSortingBlock.bind(this);
        document.getElementsByClassName("items")[0].onclick = itemList.showItem.bind(this);
        window.onmousedown = itemList.hideSortingBlock.bind(this);
        window.onscroll = itemList.fixingToolbar.bind(this);
    }
}

var itemList = new ItemList();
itemList.handlers();