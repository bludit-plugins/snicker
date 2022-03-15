/*
 |  Snicker     The first native FlatFile Comment Plugin 4 Bludit
 |  @file       ./system/themes/default/snicker.js
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.1.2 [0.1.0] - Alpha
 |
 |  @website    https://github.com/pytesNET/snicker
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2019 SamBrishes, pytesNET <info@pytes.net>
 */
;(function(root){
    "use strict";
    var w = root, d = root.document;

    /*
     |  AJAX HELPER
     */
    function ajax(url, type, data, callback, self){
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function(){
            if(this.readyState == 4){
                callback.call((self? self: this), this.responseText, this);
            }
        };
        xhttp.open(type, url, true);
        xhttp.setRequestHeader("Cache-Control", "no-cache");
        xhttp.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        if(type == "POST"){
            if(!(data instanceof FormData)){
                xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            }
            xhttp.send(data);
        } else {
            xhttp.send();
        }
    }

    /*
     |  ALERT HELPER
     */
    function showAlert(type, message, parent){
        var alert = d.createElement("DIV");
            alert.className = "comment-alert alert-" + type;
            alert.innerText = message;

        // Remove Existing Status
        var alerts = parent.querySelectorAll(".comment-alert");
        if(alerts.length > 0){
            Array.prototype.forEach.call(alerts, function(item){
                item.parentElement.removeChild(item);
            });
        }

        // Add Status
        if(parent.children.length > 0){
            parent.insertBefore(alert, parent.children[0]);
        } else {
            parent.appendChild(alert);
        }
    }

    // Ready?
    d.addEventListener("DOMContentLoaded", function(){
        "use strict";

        // Main Elements
        var form = d.querySelector("form.comment-form"),
            list = d.querySelector(".snicker-comments-list"),
            captcha = d.querySelector("a[data-captcha='reload']");

        /*
         |  HANDLE COMMENT FORM
         */
        if(form){
            form.addEventListener("submit", function(event){
                event.preventDefault();
                if(typeof(FormData) !== "function" || !SNICKER_AJAX){
                    return true;
                }
                var data = new FormData(this), self = this;

                // Check Button
                var btn = this.querySelector("[name='snicker']");
                if(btn.disabled){
                    return true;
                }
                event.preventDefault();

                // AJAX Call
                btn.disabled = true;
                btn.classList.add("loading");
                data.append("snicker", btn.value);
                ajax(SNICKER_PATH, "POST", data, function(json){
                    var data = JSON.parse(json);

                    // Add Comment
                    if(list && data.status == "success" && "comment" in data){
                        if(list.querySelector(".comment")){
                            list.querySelector(".comment").insertAdjacentHTML("beforebegin", data.comment);
                        } else {
                            list.insertAdjacentHTML("afterbegin", data.comment);
                        }
                        list.querySelector(".comment").classList.add("new-comment");
                        list.querySelector(".comment").scrollIntoView({ behavior: "smooth", block: "center" });

                        // Empty Form
                        var field = self.querySelectorAll("#comment-title,#comment-text");
                        for(var i = 0, l = field.length; i < l; i++){
                            if(field[i].tagName == "SELECT"){
                                field[i].options[0].selected = true;
                            } else if(field[i].getAttribute("type") === "checkbox"){
                                field[i].checked = false;
                            } else {
                                field[i].value = "";
                            }
                        }

                        showAlert("success", data.success, form.querySelector(".comment-article"));
                    } else if(data.status === "error"){
                        showAlert("error", data.error, form.querySelector(".comment-article"));

                        if(captcha && "captcha" in data){
                            d.querySelector("input[name='comment[captcha]']").value = "";
                            captcha.querySelector("img").src = data.captcha;
                        }
                    }

                    // Re-Enable Button
                    btn.disabled = false;
                    btn.classList.remove("loading");
                });
            });
        }

        /*
         |  HANDLE CAPTCHA RELOAD
         */
        if(captcha){
            captcha.addEventListener("click", function(event){
                if(!SNICKER_AJAX){
                    return false;
                }
                event.preventDefault();
                captcha.classList.add("reload");

                var data = "action=snicker&snicker=captcha&tokenCSRF=";
                var token = d.querySelector("input[name='tokenCSRF']").value;
                ajax(SNICKER_PATH, "POST", data + token, function(json){
                    var data = JSON.parse(json);
                    if(data.status !== "success"){
                        window.location.replace(captcha.getAttribute("href"));
                    }
                    captcha.querySelector("img").src = data.captcha;
                    captcha.classList.remove("reload");
                });
            })
        }

        /*
         |  HANDLE COMMENT REPLY
         */
        if(list){
            list.addEventListener("click", function(event){
                if(event.target.tagName != "A" || !form){
                    return true;
                }

                // Check Link
                var href = event.target.getAttribute("href");
                if(href.indexOf("snicker=reply") < 0){
                    return true;
                }

                // Handle Reply
                event.preventDefault();
                var comment = (function getComment(element){
                    var parent = element.parentElement;
                    return (parent.classList.contains("comment"))? parent: getComment(parent);
                })(event.target);

                // Create Elements
                var reply = d.createElement("DIV");
                    reply.className = "comment-reply";
                    reply.innerHTML = '<a href="' + window.location.href + '" class="reply-cancel"></a>'
                                    + '<div class="reply-title">' + comment.querySelector(".author-username").innerText + ' wrotes:</div>'
                                    + '<div class="reply-content">' + comment.querySelector(".comment-comment").innerHTML + '</div>';
                var parent = d.createElement("INPUT");
                    parent.type = "hidden";
                    parent.name = "comment[parent_uid]";
                    parent.value = comment.id.replace("comment-", "");

                // Append Cancel
                reply.querySelector(".reply-cancel").addEventListener("click", function(event){
                    event.preventDefault();

                    // Remove Elements
                    reply.parentElement.removeChild(reply);
                    parent.parentElement.removeChild(parent);

                    // Switch Button Text
                    var old = form.querySelector("button").innerText;
                    form.querySelector("button").value = "comment";
                    form.querySelector("button").innerText = form.querySelector("button").getAttribute("data-string");
                    form.querySelector("button").setAttribute("data-string", old);
                });

                // Inject Elements
                var art = form.querySelector(".comment-article");
                if(art.querySelector(".comment-reply")){
                    art.replaceChild(reply, art.querySelector(".comment-reply"));
                } else {
                    art.insertBefore(reply, form.querySelector("textarea").parentElement);
                }

                var foo = form.querySelector(".comment-footer");
                if(foo.querySelector("input[name='comment[parent_uid]']")){
                    foo.replaceChild(parent, foo.querySelector("input[name='comment[parent_uid]']"));
                } else {
                    foo.appendChild(parent);
                }

                // Switch Button Text
                var old = form.querySelector("button").innerText;
                form.querySelector("button").value = "reply";
                form.querySelector("button").innerText = form.querySelector("button").getAttribute("data-string");
                form.querySelector("button").setAttribute("data-string", old);
            });
        }

        /*
         |  HANDLE COMMENT RATING
         */
        if(list){
            list.addEventListener("click", function(event){
                if(event.target.tagName != "A" || !SNICKER_AJAX){
                    return true;
                }
                if(event.target.classList.contains("disabled")){
                    return true;
                }

                // Check Link
                var href = event.target.getAttribute("href");
                if(href.indexOf("&type=like") < 0 && href.indexOf("&type=dislike") < 0){
                    return true;
                }

                // Event Handler
                event.preventDefault();
                event.target.classList.add("disabled");
                var comment = (function getComment(element){
                    var parent = element.parentElement;
                    return (parent.classList.contains("comment"))? parent: getComment(parent);
                })(event.target), self = event.target;
                href = href.split("?");

                // AJAX REQUEST
                ajax(SNICKER_PATH, "POST", href[1], function(json){
                    var data = JSON.parse(json);

                    if(data.status === "success" && "rating" in data){
                        var like = comment.querySelector("[data-snicker='like']");
                        if(like){
                            like.innerText = String(data.rating[0]);
                        }
                        like.parentElement.classList[(like.parentElement == self? "add": "remove")]("active");

                        var dislike = comment.querySelector("[data-snicker='dislike']");
                        if(dislike){
                            dislike.innerText = String(data.rating[1]);
                        }
                        dislike.parentElement.classList[(dislike.parentElement == self? "add": "remove")]("active");
                    }

                    // Re-Enable Button
                    self.classList.remove("disabled");
                });
            });
        }
    });
})(window);
