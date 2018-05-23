function getCaller() {
    alert("caller is " + arguments.callee.caller.toString());
}

function isIP(obj) {
    var ary = $(obj).val().split(".");
    var ip = true;

    for (var i in ary) {ip = (!ary[i].match(/^\d{1,3}$/) || (Number(ary[i]) > 255)) ? false : ip;}
    ip = (ary.length != 4) ? false : ip;

    if (!ip) {    // the value is NOT a valid IP address
        $(obj).removeClass('is_ok');
        $(obj).addClass('is_ko');
        $(obj).focus();
        return false;
    }
    else {
        $(obj).removeClass('is_ko');
        $(obj).addClass('is_ok');
    }
}

function isEmpty(obj) {
    if( $(obj).val() == "" ) {
        $(obj).removeClass('is_ok');
        $(obj).addClass('is_ko');
        $(obj).focus();
        return false;
    }
    else {
        $(obj).removeClass('is_ko');
        $(obj).addClass('is_ok');
    }
}

function isStdmsName(obj) {
    var regex = new RegExp( /n=[^,]+,ou=[^,]+,o=[A-Za-z0-9]+/ );
    if( !$(obj).val().match(regex) ) {
        $(obj).removeClass('is_ok');
        $(obj).addClass('is_ko');
        $(obj).focus();
        return false;
    }
    else {
        $(obj).removeClass('is_ko');
        $(obj).addClass('is_ok');
    }
}

/**
 * Equivalent de la fonction trim :
 * supprime les espace en début et fin de chaine
 * @param {type} chaine
 * @returns {trim.chaine|@exp;@exp;chaine@pro;replace@call;@call;replace|@exp;chaine@pro;replace@call;@call;replace}
 */
function trim(chaine) {
    if (typeof(chaine) === 'string') {
        return chaine.replace(/^\s+/g,'').replace(/\s+$/g,'');
    } else {
        return chaine;
    }
}

function log(type, message) {
    var title = "";
    if( type == 'error' ) {
        title = "Erreur";
    } else {
        title = "Info";
    }

    var $dialog = $('<div></div>')
        .html(message)
        .dialog({
            autoOpen: false,
            title: title,
            modal: true,
            width: 400,
            buttons: {
                Ok: function() {
                    $( this ).dialog( "close" );
                }
            }
        });

    $dialog.dialog('open');
}

function loadContent(element, url) {
    $("<div></div>").appendTo("body")
            .attr("id", element );
    $(element).load(url);
}

function jq_id( id ) {
    return "#"+id;
}

function ShowHide( div, hidden_message, visible_message ) {
    if ( $(jq_id(div)).is(':hidden') ) {
        $(jq_id("show_"+div)).html(hidden_message);
        $(jq_id(div)).show();
    }
    else {
        $(jq_id("show_"+div)).html(visible_message);
        $(jq_id(div)).hide();
    }

    return false;
}

(function($) {
        $.fn.EqualWidth = function(settings) {
                var config = {};

                if (settings) {
                    $.extend(config, settings);
                }
                var max = 0;
                this.each(function() {
                        if ($(this).width() > max) {
                            max = $(this).width();
                        }

                });

                $(this).width(max);

                return this;
        };
})(jQuery);

if (!Array.prototype.indexOf)
{
  Array.prototype.indexOf = function(elt /*, from*/)
  {
    var len = this.length;

    var from = Number(arguments[1]) || 0;
    from = (from < 0)
         ? Math.ceil(from)
         : Math.floor(from);
    if (from < 0) {
        from += len;
    }

    for (; from < len; from++)
    {
      if (from in this &&
          this[from] === elt) {
              return from;
          }
    }
    return -1;
  };
}


if (!Array.prototype.filter)
{
  Array.prototype.filter = function(fun /*, thisp*/)
  {
    var len = this.length;
    if (typeof fun != "function") {
        throw new TypeError();
    }

    var res = [];
    var thisp = arguments[1];
    for (var i = 0; i < len; i++)
    {
      if (i in this)
      {
        var val = this[i]; // in case fun mutates this
        if (fun.call(thisp, val, i, this)) {
            res.push(val);
        }
      }
    }

    return res;
  };
}

if (!Array.prototype.map)
{
  Array.prototype.map = function(fun /*, thisp*/)
  {
    var len = this.length;
    if (typeof fun != "function") {
        throw new TypeError();
    }

    var res = new Array(len);
    var thisp = arguments[1];
    for (var i = 0; i < len; i++)
    {
      if (i in this) {
          res[i] = fun.call(thisp, this[i], i, this);
      }
    }

    return res;
  };
}


function MultiColumnArraySort(varArray, ColName, ascDesc){

    if (ascDesc == 'asc'){

        varArray.sort(function(a, b){
                var x = a[ColName].toLowerCase();
                var y = b[ColName].toLowerCase();
                return ((x < y) ? -1 : ((x > y) ? 1 : 0));
        });

    } else if (ascDesc == 'desc'){

        varArray.sort(function(a, b){
                var x = a[ColName].toLowerCase();
                var y = b[ColName].toLowerCase();
                return ((x > y) ? -1 : ((x < y) ? 1 : 0));
        });
    }
    return varArray;
}


function encode_utf8(s)
{
    if (window.encodeURIComponent)//check fn present in old browser
    {
        return unescape(encodeURIComponent(s));
    }
    else
    {
        return escape(s);
    }
}

function decode_utf8(s)
{
    if (window.decodeURIComponent)//check fn present in old browser
    {
        return decodeURIComponent(escape(s));
    }
    else
    {
        return unescape(s);
    }
}

function submitenter(input,e) {
    var keycode;
    if (window.event) {
        keycode = window.event.keyCode;
    } else if (e) {
        keycode = e.which;
    } else {
        return true;
    }

    if (keycode == 13) {
        jQuery(input.form+":input[name='offset']").val(0);
        search(input.form);
        return false;
    }
    return true;
}


function search_more(form, count, offset, max ) {
    $("div#more").html("");
    var max_pages = jQuery(form+":input[name='max_pages']").val();
    var current_page = Math.round(offset / max);
    var last_page = Math.floor(count/max);

    var input = $("<input></input>");
    input.attr("type", "button");
    input.click(function() {
        jQuery(form+":input[name='offset']").val(0);
        search(form);
    });
    input.val("<<");
    $("div#more").append(input);

    var start_button = 0;
    var stop_button = max_pages;

    if( last_page < max_pages ) {
        start_button = 0;
        stop_button = last_page;
    }
    else if( current_page > ( last_page - max ) ) {
        start_button = last_page - max_pages;
        stop_button = last_page;
    }

    else if( current_page > (max_pages/2) ) {
        start_button = current_page - max_pages/2;
        stop_button = current_page + max_pages/2;
    }

    for( var i = start_button; i <= stop_button ;i++) {
        input = $("<input></input>");
        input.attr("type", "button");
        input.click(function() {
            jQuery(form+":input[name='offset']").val(jQuery(this).val()*jQuery(this.form+":input[name='max']").val());
            search(form);
        });
        input.val(i);
        if( i == current_page ) {
            input.addClass("current");
        }
        $("div#more").append(input);
    }
    input = $("<input></input>");
    input.attr("type", "button");
    input.click(function() {
        jQuery(form+":input[name='offset']").val(
            last_page*jQuery(this.form+":input[name='max']").val()
        );
        search(form);
    });
    input.val(">>");
    $("div#more").append(input);
}

function search(form) {
    var url = jQuery(form).attr("action");
    var method = jQuery(form).attr("method");
        $.ajax({
                type: method,
                url: url,
                data: jQuery(form).serialize(),
                dataType: 'json',
        success: function(data) {
            $("#surveillances li").remove();
            if( data == null ) {
                return false;
            }
            if( data.data == undefined ) {
                return false;
            }
            $.each(data.data, function(index, value) {
                $.tree.reference("surveillances").create(value, -1);
            });
            search_more( form, data.count, data.offset, data.max );
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            log("error", "Call("+url+"): "+XMLHttpRequest.statusText );
        }
    });

    return true;
}

/**
 * MsgBoxErreurApplicative: Fonction analogue à ErrorMessageBox
 * Affiche une boite de dialogue :
 * ------------------------
 * titre
 * ------------------------
 * message
 * ---------
 * commentaire
 * ------------------------
 * 
 * Le message s'affiche en rouge 
 * Le commentaire est affiché normalement en dessous
 * @param {type} message
 * @param {type} commentaires
 * @param {type} title
 * @param {type} callback
 * @returns {MessageBoxErreurApplicative}
 */
function MsgBoxErreurApplicative(message, commentaires, title, callback){

    if (arguments.length === 3) {
        if (Object.prototype.toString.call(title) === "[object Function]") {
            callback = title;
            title = 'Erreur....';
        }
    }
    if (callback === undefined) {
        callback = (function () {
            return;
        });
    }

    if (title === undefined) {
        title = 'Erreur....';
    }
    // Construction du contenu de la boite de dialogue
    var htmlMsgBox = 
            '<br/><div class="ui-state-highlight ui-corner-all" style="padding: 5px">' +
            '    <p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span>'+
            '   <span id="spn_message">'+message+'</span></p>' +
            '</div>';
            if (commentaires !== '') {
                htmlMsgBox += '<br/>'+commentaires;
            }
    
    
    var modalComment = $('<div style="display: none;" id="div_msgbox_appli"></div>');
    $('body').append(modalComment);
    modalComment.html(htmlMsgBox);
    modalComment.dialog({
        autoOpen: true,
        modal: true,
        width: '400',
        height: 'auto',
        title: title,
        close: function(){
            $(this).remove();
        },
        buttons: [
            {
            text: "Fermer",
                click: function() {
                    $(this).dialog("close");
                    callback();
                }
            }
        ]
    });
}

function MsgBoxErreurCritique(message, commentaires, title, callback){

    if (arguments.length === 3) {
        if (Object.prototype.toString.call(title) === "[object Function]") {
            callback = title;
            title = 'Erreur critique....';
        }
    }
    if (callback === undefined) {
        callback = (function () {
            return;
        });
    }

    if (title === undefined) {
        title = 'Erreur critique....';
    }
    // Construction du contenu de la boite de dialogue
    var htmlMsgBox = 
            '<br/><div class="ui-state-error ui-corner-all" style="padding: 5px">' +
            '    <p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span>'+
            '   <span id="spn_message">'+message+'</span></p>' +
            '</div>';
            if (commentaires !== '') {
                htmlMsgBox += '<br/>'+commentaires;
            }
    
    
    var modalComment = $('<div style="display: none;" id="div_msgbox_appli"></div>');
    $('body').append(modalComment);
    modalComment.html(htmlMsgBox);
    modalComment.dialog({
        autoOpen: true,
        modal: true,
        width: '400',
        height: 'auto',
        title: title,
        close: function(){
            $(this).remove();
        },
        buttons: [
            {
            text: "Fermer",
                click: function() {
                    $(this).dialog("close");
                    callback();
                }
            }
        ]
    });
}

function ErrorMessageBox(message, title, callback){

    if (arguments.length === 2) {// if only two arguments were supplied
        if (Object.prototype.toString.call(title) === "[object Function]") {
            callback = title;
            title = 'Erreur';
        }
    }

    if (callback === undefined) {
        callback = (function () {
            return;
        });
    }

    if (title === undefined) {
        title = 'Erreur';
    }
    var modalComment = $('<div style="display: none;" id="ErrorMessageBox"></div>');
    $('body').append(modalComment);
    modalComment.html(message);
    modalComment.dialog({
        autoOpen: true,
        modal: true,
        width: '400',
        height: 'auto',
        title: title,
        close: function(){
            $(this).remove();
        },
        buttons: [
            {
            text: "Fermer",
                click: function() {
                    $(this).dialog("close");
                    callback();
                }
            }
        ]
    });
}

function unclosableMessageBox(html, title, openFunction){
    var dial = $('<div id="unclosableMessageBox" style="dispaly:none"></div>');
    $('body').append(dial);
    title = title == undefined ? '' : title;
    dial.html(html);
        dial.dialog({
        autoOpen: true,
        modal: true,
        width: '400',
        height: 'auto',
        title: title,
        beforeClose: function(event, ui) {
            return false;
        },
        open: function(event, ui){
           openFunction(event, ui);
        }
    });
}

function unclosableMessageBoxShowCloseButton(){
    $("#unclosableMessageBox").dialog({buttons: {"Fermer": function() {$(this).dialog("close");$(this).remove();}}});
}

/**
 * En cas d'oublie de console.log dans le code javascript
 * il n'y aura pas d'incidence si les navigateurs
 * qui ne sont pas compatibles avec la console de debugage
 */

/**
 * Class consoleSubstitute : class de substitution de la class
 * console
 */
function consoleSubstitute() {
    this.log = function() {return true;};
    this.debug = function() {return true;};
    this.info = function() {return true;};
    this.warn = function() {return true;};
    this.error = function() {return true;};
    this.time = function() {return true;};
    this.timeEnd = function() {return true;};
    this.profile = function() {return true;};
    this.profileEnd = function() {return true;};
    this.trace = function() {return true;};
    this.group = function() {return true;};
    this.groupEnd = function() {return true;};
    this.dir = function() {return true;};
    this.dirxml = function() {return true;};
}

/**
 * Si window.console n'existe pas alors création de console
 */
if (!window.console) {
    window.console = new consoleSubstitute();
}


////////////////////// DIALOGUES DE L'APPLICATION /////////////////////////////////

function idGenerator() {
    var S4 = function() {
       return (((1+Math.random())*0x10000)|0).toString(16).substring(1);
    };
    return (S4()+S4()+S4()+S4()+S4()+S4()+S4()+S4());
}

function sinapsDialog(id, htmlContent, txtTitle, isModal, width, height) {

    this.isRendered = false;
    this.id = id;
    this.mainObject = null;
    this.buttons = [];
    this.beforeCloseCallBack = undefined;
    this.closeCallBack = undefined;
    this.openCallBack = undefined;
    this.resizeStopCallBack = undefined;

    var me = this;

    if (id == undefined){
        id = idGenerator();
    } else {
        if ($("#" . id).length != 0){
            throw "sinapsDialog : argument 'id' must be unique in DOM";
        }
    }

    if (htmlContent == undefined){
        htmlContent = "";
    }

    if(isModal == undefined){
        isModal = false;
    }

    if (txtTitle == undefined){
        txtTitle = "";
    }

    if (width == undefined){
        width = 'auto';
    }

    if (height == undefined){
        height = 'auto';
    }

    this.sizeWidth = width;
    this.sizeHeight = height;
    this.isModal = isModal;
    this.txtTitle = txtTitle;
    this.htmlContent = htmlContent;
    this.id = id;
    this.isResizable = true;
    this.isDraggable = true;
    this.posMy = null;
    this.posAt = null;
    this.posOf = null;
    this.hasMaximizeButton = false;

    this.maximizeButton = function(boolMaximizeButton){
        this.hasMaximizeButton = boolMaximizeButton;
        return this;
    };

    this.resizable = function(boolResizable){
        this.isResizable = boolResizable;
        return this;
    };

    this.draggable = function(boolDraggable){
        this.isDraggable = boolDraggable;
        return this;
    };

    this.jqObject = function() {
        return this.mainObject;
    };

    this.modal = function(isModal){
        this.isModal = isModal;
        return this;
    };

    this.close = function (){
        this.mainObject.dialog({beforeClose: function(){return true;}});
        this.mainObject.dialog("close");
    };

    this.isOpen = function(){
        return this.mainObject.dialog( "isOpen" );
    };

    this.remove = function (){
        $(this.mainObject).remove();
    };

    this.beforeClose = function(func){
        this.beforeCloseCallBack = func;
        return this;
    };

    this.onClose = function(func){
        this.closeCallBack = func;
        return this;
    };

    this.onOpen = function(func){
        this.openCallBack = func;
        return this;
    };

    this.html = function (htmlContent) {
        this.htmlContent = htmlContent;
        return this;
    };

    this.title = function(txtTitle){
        this.txtTitle = txtTitle;
        return this;
    };

    this.width = function(width){
        this.sizeWidth = width;
        return this;
    };

    this.height = function(height){
        this.sizeHeight = height;
        return this;
    };

    this.position = function(my, at, of){

        this.posMy = my;
        this.posAt = at;
        this.posOf = of;
        return this;
    };

    this.create = function (){
        this.mainObject = $('<div style="display: none;" id="' + this.id + '"></div>');
        $('body').append(this.mainObject);
        this.mainObject.html(this.htmlContent);
        this.refresh();
        return this;
    };

    this.applyOn = function(jsObj){
        this.mainObject = $(jsObj);
        if (this.mainObject.attr('id') == ''){
            this.mainObject.attr('id', this.id);
        }
        this.refresh();
        return this;
    };

    this.open = function(){
        this.mainObject.dialog({resizeStop: this._resizeStop});
        this.mainObject.dialog('open');
        return this;
    };

    this.resizeStop = function(fun){
        this.resizeStopCallBack = fun;
    };


    this._resizeStop = function(event, ui){

        var height = ui.size.height;
        var width = ui.size.width;
        var top = ui.position.top;
        var left = ui.position.left;

        if(me.resizeStopCallBack != undefined){
            me.resizeStopCallBack(width, height, top, left);
        }
    };

    this.refresh = function(){
        var me = this;

        if (this.hasMaximizeButton){

            var titleBar = this.jqObject().parent().find('.ui-dialog-titlebar');

            var linkFullScreen = $('<a href="#" class="ui-dialog-titlebar-close ui-corner-all ui-dialog-titlebar-fullscreen" role="button" style="right: 20px;"><span class="ui-icon ui-icon-plusthick"></span></a>');
            linkFullScreen.click(function(event){
                event.stopPropagation();
                var fullscreen = me.jqObject().attr('fullscreen') == undefined ? false : true;

                var left = 0;
                var top = 0;
                var width = 0;
                var height = 0;

                if (fullscreen){

                    me.jqObject().parent().find('.ui-dialog-titlebar-fullscreen').find('span').removeClass('ui-icon ui-icon-minusthick').addClass('ui-icon ui-icon-plusthick');
                    me.jqObject().removeAttr('fullscreen');

                    // Recuperation de l'ancienne position
                    left = me.jqObject().attr('old_left');
                    top = me.jqObject().attr('old_top');
                    width = me.jqObject().attr('old_width');
                    height = me.jqObject().attr('old_height');

                    me.position(left, top);
                    me.width(width);
                    me.height(height);

                    me.jqObject().dialog( "option" , 'height' , height);
                    me.jqObject().dialog( "option" , 'width' , width);
                    me.jqObject().dialog( "option" , "position", [left , top]);

                } else {

                    me.jqObject().parent().find('.ui-dialog-titlebar-fullscreen').find('span').removeClass('ui-icon ui-icon-plusthick').addClass('ui-icon ui-icon-minusthick');
                    me.jqObject().attr('fullscreen', 'true');

                    var currentPosition = me.jqObject().closest('.ui-dialog').offset();
                    me.jqObject().attr('old_left', currentPosition.left);
                    me.jqObject().attr('old_top', currentPosition.top);
                    me.jqObject().attr('old_width', me.jqObject().closest('.ui-dialog').width());
                    me.jqObject().attr('old_height', me.jqObject().closest('.ui-dialog').height());

                    // affichage en plein ecran
                    width = $(window).width() - 20;
                    height = $(window).height() -20;
                    left = 10;
                    top = 10;

                    me.position(left, top);
                    me.width(width);
                    me.height(height);

                    me.jqObject().dialog( "option" , "position", [left , top]);
                    me.jqObject().dialog( "option" , 'height' , height);
                    me.jqObject().dialog( "option" , 'width' , width);

                }

                if (me.resizeStopCallBack != undefined){
                    me.resizeStopCallBack(width, height, top, left);
                }
                return false;
            });

            titleBar.prepend(linkFullScreen);
        }

        this.options = {
                modal: this.isModal,
                width: this.sizeWidth,
                height: this.sizeHeight,
                autoOpen: false
        };

        this.mainObject.dialog(this.options);

        if(this.posMy && this.posAt && this.posOf) {
            this.mainObject.dialog({
                position: { my: this.posMy, at: this.posAt, of: this.posOf }
            });
        } else {
            if (this.posMy != null && this.postAt != null){
                this.mainObject.dialog( "option" , "position", [this.posMy , this.postAt]);
            }
        }
        this.mainObject.dialog({ title: this.txtTitle });

        this.mainObject.dialog({resizeStop: this._resizeStop});

        if (this.beforeCloseCallBack != undefined){
            this.mainObject.dialog({beforeClose: this.beforeCloseCallBack});
        } else {
            this.mainObject.dialog({beforeClose: function(){return true;}});
        }

        if (this.closeCallBack != undefined){
            this.mainObject.dialog({close: this.closeCallBack});
        } else {
            this.mainObject.dialog({close: function(){}});
        }

        if (this.openCallBack != undefined){
            this.mainObject.dialog({open: this.openCallBack});
        } else {
            this.mainObject.dialog({open: function(){}});
        }

        if (this.isResizable){
            this.mainObject.dialog( "option", "resizable", true );
        } else {
            this.mainObject.dialog( "option", "resizable", false );
        }

        if (this.isDraggable){
            this.mainObject.dialog( "option", "draggable", true );
        } else {
            this.mainObject.dialog( "option", "draggable", false );
        }

        this.mainObject.dialog({buttons: []});
        this.mainObject.dialog({buttons: this.buttons});
        return this;
    };

    this.clearButtons = function(){
        this.buttons = [];
        return this;
    };

    this.removeButton = function (label){

        if (this.buttons.length == 0){
            return this;
        }

        for(var i in this.buttons){
            if (this.buttons[i].text == label) {
                this.buttons.splice(i, 1);
                return this;
            }
        }
        return this;
    };

    this.addButton = function (label, callBack){
        var btn = {text: label, click: function(){callBack();}};
        this.buttons.push(btn);
        return this;
    };

}

/*
 * Fonction qui permet de supprimer les accents d'un texte
 */
function stripAccents(toStrip) {
    var toReplace = "àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ";
    var regex = new RegExp('([' + toReplace + '])',"g");
    var replacement = 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY';
    var stripped = toStrip.replace( regex, function(foundCharacter) {
        return replacement.charAt(toReplace.indexOf(foundCharacter));
    });
    return stripped;
}
