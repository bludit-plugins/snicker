/*
 |  Snicker     The first native FlatFile Comment Plugin 4 Bludit
 |  @file       ./admin/js/admin.snicker.js
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
     |  HELPER :: LOOP
     |  @since  0.1.0
     */
    var each = function(elements, callback){
        if(elements instanceof HTMLElement){
            callback.call(elements, elements);
        } else if(elements.length && elements.length > 0){
            for(var l = elements.length, i = 0; i < l; i++){
                callback.call(elements[i], elements[i], i);
            }
        }
    };

    // Ready?
    d.addEventListener("DOMContentLoaded", function(){
        "use strict";

        /*
         |  BOOTSTRAP POPOVER
         |  @since  0.1.0
         */
        jQuery('[data-toggle="popover"]').popover({
            content: function(){
                var data = d.querySelector(this.getAttribute("data-target"));
                return data.innerHTML;
            },
            html: true
        }).click(function(event){
            event.preventDefault();
        }).on("inserted.bs.popover", function(event){
            d.querySelector("#" + this.getAttribute("aria-describedby")).style.width = "410px";
            d.querySelector("#" + this.getAttribute("aria-describedby")).style.maxWidth = "410px";
        })

        /*
         |  MAIN MENU LINK HANDLER
         |  @since  0.1.0
         */
        var mainMenu = d.querySelector("[data-handle='tabs']");
        if(mainMenu){
            var menuLink = function(link){
                if(typeof(link) === "undefined"){
                    if(w.location.hash.length == 0){
                        var link = mainMenu.querySelector("li a");
                    } else {
                        var link = mainMenu.querySelector("[href='#snicker-" + w.location.hash.substr(1) + "']");
                    }
                }
                if(!(link instanceof Element)){
                    return false;
                }

                // Handle
                if(link && !link.classList.contains("active")){
                    link.click();
                }
                if(link){
                    w.location.hash = link.getAttribute("href").replace("snicker-", "");
                }
            };

            // Current Hash Handler
            if(w.location.hash.length > 0){
                menuLink();
            }

            // Local Hash Handler
            each(mainMenu.querySelectorAll("li > a"), function(){
                this.addEventListener("click", function(event){
                    menuLink(this);
                });
            });

            // History Hash Handler
            w.onhashchange = function(event){
                if(w.location.hash.length == 0){
                    var link = mainMenu.querySelector("li a");
                } else {
                    var link = mainMenu.querySelector("[href='#snicker-" + w.location.hash.substr(1) + "']");
                }
                menuLink(link);
            };
        }

        /*
         |  MAIN MENU LINK HANDLER
         |  @since  0.1.1
         */
        var avatar = document.getElementById("sn-avatar"),
            gravatar = document.getElementById("sn-gravatar");
        if(avatar && gravatar){
            var GravatarOption = function(){
                console.log(avatar.value)

                if(avatar.value == "gravatar"){
                    gravatar.disabled = false;
                    document.querySelector("label[for='sn-gravatar']").classList.remove("text-muted");
                } else {
                    gravatar.disabled = true;
                    document.querySelector("label[for='sn-gravatar']").classList.add("text-muted");
                }
            };

            avatar.addEventListener("change", function(){
                GravatarOption();
            });
            GravatarOption();
        }
    });
})(window);
