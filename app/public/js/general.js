String.prototype.format = function(replacement, prefix) {
    var format = this,
        match;
    prefix = prefix || "";
    for (var i = 0; match = new RegExp("\\{" + prefix + "(\\d+|\\w+)?\\}", "gm").exec(format); i++) {
        var key = match[1];
        if (key) {
            format = format.replace(new RegExp("\\{" + prefix + key + "\\}", "gm"), replacement[key] ? replacement[key] : "");
        }
    }
    return format;
};

var Helpers = {
    cookie: {
        get: function(name) {
            var matches = document.cookie.match(new RegExp(
                "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
            ));
            return matches ? decodeURIComponent(matches[1]) : undefined;
        },
        set: function(name, value, options) {
            options = options || {};

            var expires = options.expires;

            if (typeof expires == "number" && expires) {
                var d = new Date();
                d.setTime(d.getTime() + expires * 1000);
                expires = options.expires = d;
            }
            if (expires && expires.toUTCString) {
                options.expires = expires.toUTCString();
            }

            value = encodeURIComponent(value);

            var updatedCookie = name + "=" + value;

            for (var propName in options) {
                updatedCookie += "; " + propName;
                var propValue = options[propName];
                if (propValue !== true) {
                    updatedCookie += "=" + propValue;
                }
            }
            document.cookie = updatedCookie;
        },
        delete: function(name) {
            Helpers.cookie.set(name, "", {
                expires: -1
            })
        }
    },
    request: function (url, options) {
        options = options || {};
        options.method = options.method || "GET";
        var xhr = new XMLHttpRequest();
        xhr.open(options.method, url);
        xhr.onload = function() {
            var response = options.dataType == "JSON" ? JSON.parse(xhr.response) : xhr.response;
            options.callback(response);
        };
        var params = null;
        if (options.method == "POST") {
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            if (options.data != null) {
                params = [];
                for (var field in options.data) {
                    if (options.data.hasOwnProperty(field)) {
                        params.push(field + "=" + encodeURIComponent(options.data[field]));
                    }
                }
                params = params.join("&");
            }
        }
        xhr.send(params);
    },
    eventController: {
        handlers: { },
        updateHandler: function (targetElement, params, handlers) {
            targetElement["on" + params.type] = function (e) {
                for (var handler in handlers) {
                    if (handlers.hasOwnProperty(handler)) {
                        handlers[handler](e);
                    }
                }
                if (this != window || params.defaultAction === false) {
                    return false;
                }
            }
        },
        add: function (name, params) {
            params.selector = params.selector || window;
            var selectorKey = params.selector == window ? "window" : params.selector;
            if (this.handlers[selectorKey] == null) {
                this.handlers[selectorKey] = {};
            }
            if (this.handlers[selectorKey][params.type] == null) {
                this.handlers[selectorKey][params.type] = {};
            }
            this.handlers[selectorKey][params.type][name] = params.callback;
            var targetElement = params.selector == window ? window : document.querySelector(params.selector);
            this.updateHandler(targetElement, params, this.handlers[selectorKey][params.type]);
        },
        remove: function (name, params) {
            params.selector = params.selector || window;
            var selectorKey = params.selector == window ? "window" : params.selector;
            if (this.handlers[selectorKey] == null || this.handlers[selectorKey][params.type] == null || this.handlers[selectorKey][params.type][name] == null) {
                return false;
            }
            var targetElement = params.selector == window ? window : document.querySelector(params.selector),
                handlers = this.handlers[selectorKey][params.type];
            delete handlers[name];
            this.updateHandler(targetElement, params, handlers);
        }
    }
};

function Popup(params) {
    this.params = params || {};
}

Popup.prototype.templates = {
    wrap: "<div class='popup-shadow'><div class='popup-controls'><a href='#' class='popup-control-close'></a></div></div><div class='popup-body-wrapper'><div class='popup-body'><div class='popup-title'>{title}</div><div class='popup-content'>{content}</div></div></div>",
};

Popup.prototype.handlers = function () {
    document.getElementsByClassName("popup-shadow")[0].onclick = this.close.bind(this);
    document.getElementsByClassName("popup")[0].querySelector(".popup-controls .popup-control-close").onclick = this.close.bind(this);
};

Popup.prototype.close = function () {
    var popupElement = document.getElementsByClassName("popup")[0];
    popupElement.parentNode.removeChild(popupElement);
    document.body.classList.remove("fixed");
    if (typeof this.params.close == "function") {
        this.params.close();
    }
    return false;
};

Popup.prototype.show = function () {
    var popupContainer = document.createElement('div');
    popupContainer.classList.add("popup");
    popupContainer.classList.add(this.params.name);
    popupContainer.innerHTML = this.templates.wrap.format({
        title: this.params.title,
        content: this.params.content
    });
    document.body.appendChild(popupContainer);
    document.body.classList.add("fixed");
    this.handlers();
};

function PopupConfirm(params) {
    this.params = params || {};
    this.showConfirm = function () {
        this.params.content = "<div class='popup-confirm-text'>" + this.params.confirmText + "</div><button class='popup-confirm-button'>Подтвердить</button>";
        this.show();
    };
}

PopupConfirm.prototype = Object.create(Popup.prototype);
PopupConfirm.prototype.constructor = PopupConfirm;