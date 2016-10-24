function ItemList() {
    this.data = {
        page: 1,
        states: {
            loading: false
        }
    };
    this.settings = {
        homepage: "/",
        urls: {
            item: "/catalog/get?id={id}",
            itemPage: "/catalog/item/{id}",
            itemAddPage: "/catalog/item/add",
            itemEditPage: "/catalog/item/edit/{id}",
            catalogPage: "/catalog/list?page={page}&wrap={wrap}",
        },
        addForm: {
            fields: {
                title: {
                    fieldName: "Название",
                    fieldType: "input:text"
                },
                price: {
                    fieldName: "Цена",
                    fieldType: "input:number"
                },
                description: {
                    fieldName: "Описание",
                    fieldType: "textarea"
                },
                image: {
                    fieldName: "Ссылка на изображение",
                    fieldType: "input:text"
                }
            }
        }
    };
    this.templates = {
        itemLoadingInfo: "<div class='item-loading'></div>",
        item: "<div class='item-info-wrap'><div class='item-image'><img src='{image}' alt='{title}' /></div><div class='item-info'><h2>{title}</h2><div class='item-description'>{description}</div></div></div>",
        manageItemForm: "<div class='manage-item-form' data-type='{type}'><form method='post' id='manage_item_form'>{fields}</form></div>",
        manageItemFormItem: "<div class='field {class}'><div class='field-name'>{title}</div><div class='field-value'>{field}</div></div>",
        manageItemFormSuccess: "<div class='manage-item-form-message success'>{message}</div>",
        manageItemFormFieldsType: {
            "input:text": "<input type='text' name='{name}' value='{value}' />",
            "input:number": "<input type='number' name='{name}' value='{value}' />",
            "textarea": "<textarea name='{name}'>{value}</textarea>",
            "submitButton": "<button type='submit'>{value}</button>"
        }
    };
    this.fixingToolbar = function () {
        var title = document.getElementsByClassName("title")[0],
            items = document.getElementsByClassName("items")[0];
        if (window.scrollY > 52) {
            title.classList.add("fix");
            items.classList.add("shifted");
        } else {
            title.classList.remove("fix");
            items.classList.remove("shifted");
        }
    };
    this.clickItem = function (e) {
        var itemElement = e.target.closest(".item");
        if (itemElement == null || e.target.closest(".item-controls") != null) {
            return false;
        }
        this.showItem(this.settings.urls.item.format({
            id: itemElement.getAttribute("data-id")
        }));
        return false;
    };
    this.showItem = function (href) {
        var o = this,
            currentDocumentTitle = document.title,
            currentLocationPath = location.pathname,
            itemPopup = new Popup({
                name: "item-info",
                title: "Информация о товаре",
                content: this.templates.itemLoadingInfo,
                close: function () {
                    document.title = currentDocumentTitle;
                    window.history.pushState({}, "", currentLocationPath == location.pathname ? o.settings.homepage : currentLocationPath);
                }
            });
        itemPopup.show();
        Helpers.request(href, {
            dataType: "JSON",
            callback: function (response) {
                var item = response["data"]["item"][0],
                    itemInfo = o.templates.item.format(item);
                document.title = item["title"];
                window.history.pushState({}, "", o.settings.urls.itemPage.format({ id: item["id"] }));
                document.getElementsByClassName("popup item-info")[0].querySelector(".popup-content").innerHTML = itemInfo;
            }
        });
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
    this.hideSortingBlock = function (e) {
        if (!e.target.closest(".options-sort") && !e.target.closest(".options-sort-setting")) {
            document.getElementsByClassName("options-sort-setting")[0].classList.remove("showed");
        }
    };
    this.loadMoreItems = function () {
        var o = this;
        Helpers.request(this.settings.urls.catalogPage.format({ page: ++this.data.page, wrap: "without" }), {
            callback: function (response) {
                document.querySelector("section.content .items").innerHTML += response;
                var loadingElement = document.querySelector(".loading-more-items");
                loadingElement.parentNode.removeChild(loadingElement);
                o.data.states.loading = false;
            }
        });
    };
    this.loadMoreItemsEvent = function () {
        if (this.data.states.loading) {
            return;
        }
        var scrollPosition = document.body.scrollTop + window.innerHeight,
            scrollHeight = document.body.scrollHeight;
        if (scrollHeight - scrollPosition < 100) {
            var loadingElement = document.createElement('div');
            loadingElement.classList.add("loading-more-items");
            document.querySelector("section.content").appendChild(loadingElement);
            this.data.states.loading = true;
            this.loadMoreItems();
        }
    };
    this.setOrderHistory = function () {
        var order = Helpers.cookie.get("order");
        if (order == null) {
            return;
        }
        document.querySelectorAll(".options .options-sort-setting .options-sort-setting-item").forEach(function (element) {
            element.classList.remove("selected");
        });
        var targetOrderTypeItem = document.querySelector(".options .options-sort-setting .options-sort-setting-item[data-type=\"" + order + "\"]");
        targetOrderTypeItem.classList.add("selected");
        document.querySelector(".options .options-sort .options-sort-setting-link").innerHTML = targetOrderTypeItem.innerHTML;
    };
    this.setOrder = function (e) {
        if (!e.target.classList.contains("options-sort-setting-item") || e.target.classList.contains("selected")) {
            return;
        }
        var element = e.target,
            options = element.getAttribute("data-type");
        Helpers.cookie.delete("order");
        Helpers.cookie.set("order", options, {
            expires: new Date().getTime() + 60 * 60 * 24 * 7
        });
        document.querySelector(".items").innerHTML = "";
        if (document.querySelector(".loading-more-items") == null) {
            var loadingElement = document.createElement('div');
            loadingElement.classList.add("loading-more-items");
            document.querySelector("section.content").appendChild(loadingElement);
        }
        document.querySelectorAll(".options .options-sort-setting .options-sort-setting-item").forEach(function (element) {
            element.classList.remove("selected");
        });
        element.classList.add("selected");
        document.querySelector(".options .options-sort .options-sort-setting-link").innerHTML = element.innerHTML;
        document.querySelector(".options .options-sort-setting").classList.remove("showed");
        this.data.page = 0;
        this.loadMoreItems();
    };
    this.manageItemFormGenerate = function (item) {
        var fieldsHTML = "",
            fields = this.settings.addForm.fields;
        for (var field in fields) {
            if (fields.hasOwnProperty(field)) {
                fieldsHTML += this.templates.manageItemFormItem.format({
                    title: fields[field]["fieldName"] + ":",
                    field: this.templates.manageItemFormFieldsType[fields[field]["fieldType"]].format({
                        name: field,
                        value: item != null ? item[field] : ""
                    })
                });
            }
        }
        fieldsHTML += this.templates.manageItemFormItem.format({
            class: "button",
            field: this.templates.manageItemFormFieldsType.submitButton.format({
                value: item != null ? "Редактировать" : "Добавить"
            })
        });
        return this.templates.manageItemForm.format({
            fields: fieldsHTML,
            type: item != null ? "edit" : "add"
        });
    };
    this.editItemFormEvent = function (e) {
        if (!e.target.classList.contains("item-controls-edit") && !e.target.classList.contains("add-item")) {
            return;
        }
        var formType = e.currentTarget.classList.contains("add-item") ? "add" : "edit",
            id = formType == "add" ? null : e.target.closest(".item").getAttribute("data-id");
        this.editItemForm(formType, id);
    };
    this.editItemForm = function (formType, id) {
        var o = this,
            popupTitle,
            popupClass,
            popupContent,
            url;
        if (formType == "add") {
            popupClass = "add-item";
            popupTitle = "Добавление товара";
            popupContent = this.manageItemFormGenerate();
            url = this.settings.urls.itemAddPage;
        } else {
            popupClass = "edit-item";
            popupTitle = "Редактирование товара";
            popupContent = this.templates.itemLoadingInfo;
            url = this.settings.urls.itemEditPage.format({
                id: id
            });
            Helpers.request(this.settings.urls.item.format({ id: id }), {
                dataType: "JSON",
                callback: function (response) {
                    var item = response["data"]["item"][0];
                    document.getElementsByClassName("popup edit-item")[0].querySelector(".popup-content").innerHTML = o.manageItemFormGenerate(item);
                }
            });
        }
        var currentDocumentTitle = document.title,
            currentLocationPath = location.pathname,
            o = this,
            itemPopup = new Popup({
                name: popupClass,
                title: popupTitle,
                content: popupContent,
                close: function () {
                    document.title = currentDocumentTitle;
                    window.history.pushState({}, "", currentLocationPath == location.pathname ? o.settings.homepage : currentLocationPath);
                }
            });
        document.title = popupTitle;
        window.history.pushState({}, "", url);
        itemPopup.show();
        if (formType == "add") {
            document.querySelector("#manage_item_form .field:first-child .field-value").querySelector("input, textarea, select").focus();
        }
    };
    this.addItemSubmit = function (e) {
        if (e.target.id != "manage_item_form") {
            return false;
        }
        var submitButtonElement = e.target.querySelector(".field-value button[type=\"submit\"]");
        submitButtonElement.setAttribute("disabled", "disabled");
        submitButtonElement.innerHTML = "Подождите...";
        var data = {},
            fields = e.target.querySelectorAll(".field"),
            o = this;
        for (var i = 0; i < fields.length; i++) {
            var fieldControl = fields[i].querySelector(".field-value").querySelector("input, textarea, select");
            if (fieldControl == null) {
                continue;
            }
            fieldControl.setAttribute("disabled", "disabled");
            data[fieldControl.name] = fieldControl.value
        }
        Helpers.request(this.settings.urls.itemAddPage, {
            dataType: "JSON",
            method: "POST",
            data: data,
            callback: function (response) {
                var formElement = document.querySelector(".manage-item-form"),
                    formType = formElement.getAttribute("data-type");
                formElement.innerHTML = o.templates.manageItemFormSuccess.format({
                    message: formType == "edit" ? "Товар успешно отредактирован." : "Товар успешно добавлен."
                });
            }
        });
        return false;
    };
    this.autoLoad = function () {
        var item = location.pathname.match(/^\/catalog\/item\/([1-9]\d*)$/i);
        if (item != null) {
            this.showItem(this.settings.urls.item.format({ id: item[1] }));
        }
        var addItem = location.pathname.match(/^\/catalog\/item\/add$/i);
        if (addItem != null) {
            this.editItemForm("add");
        }
        var editItem = location.pathname.match(/^\/catalog\/item\/edit\/([1-9]\d*)$/i);
        if (editItem != null) {
            this.editItemForm("edit", editItem[1]);
        }
    };
    this.deleteItem = function (e) {
        if (!e.target.classList.contains("item-controls-delete")) {
            return;
        }
        var deletePopupConfirm = new PopupConfirm({
            title: "Удаление товара",
            name: "delete-item",
            confirmText: "Вы действительно хотите удалить товар?"
        });
        deletePopupConfirm.showConfirm();
    };
    this.handlers = function () {
        Helpers.eventController.add("ItemList.showSortingBlock", {
            selector: "section.content > .title > .options .options-sort",
            type: "click",
            callback: itemList.showSortingBlock.bind(this)
        });
        Helpers.eventController.add("ItemList.clickItem", {
            selector: ".items",
            type: "click",
            callback: itemList.clickItem.bind(this)
        });
        Helpers.eventController.add("ItemList.setOrder", {
            selector: ".options .options-sort-setting",
            type: "click",
            callback: itemList.setOrder.bind(this)
        });
        Helpers.eventController.add("ItemList.hideSortingBlock", {
            type: "mousedown",
            callback: itemList.hideSortingBlock.bind(this)
        });
        Helpers.eventController.add("ItemList.fixingToolbar", {
            type: "scroll",
            callback: itemList.fixingToolbar.bind(this)
        });
        Helpers.eventController.add("ItemList.loadMoreItems", {
            type: "scroll",
            callback: itemList.loadMoreItemsEvent.bind(this)
        });
        Helpers.eventController.add("ItemList.addItem", {
            selector: ".title .add-item",
            type: "click",
            defaultAction: false,
            callback: itemList.editItemFormEvent.bind(this)
        });
        Helpers.eventController.add("ItemList.addItemSubmit", {
            type: "submit",
            defaultAction: false,
            callback: itemList.addItemSubmit.bind(this)
        });
        Helpers.eventController.add("ItemList.editItem", {
            type: "click",
            selector: ".items",
            callback: itemList.editItemFormEvent.bind(this)
        });
        Helpers.eventController.add("ItemList.deleteItem", {
            type: "click",
            selector: ".items",
            callback: itemList.deleteItem.bind(this)
        });
    }
}

var itemList = new ItemList();
itemList.handlers();
itemList.autoLoad();
itemList.setOrderHistory();