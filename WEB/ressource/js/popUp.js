
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
 *
 */

function MsgBoxErreurCritique(message, title, callback){

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
        title = 'Erreur critique!!';
    }

    bootbox.dialog({
      size: "large",
      title: title,
      message: message,
      buttons: {
        ok: {
            label: "Ok",
            className: 'btn-danger',
            callback: callback()
        }
      },
      callback: function(){ callback(); }
    });

    $(".modal-header").addClass("bg-danger");
    $(".modal-title").addClass("text-white");
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

    bootbox.dialog({
      size: "large",
      title: title,
      message: message,
      buttons: {
        ok: {
            label: "Ok",
            className: 'btn-warning',
            callback: callback()
        }
      },
      callback: function(){ callback(); }
    });

    $(".modal-header").addClass("bg-warning");
    $(".modal-title").addClass("text-white");
}

function MessageBox(message, title, callback){

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
        title = 'Succès';
    }

    bootbox.dialog({
      size: "large",
      title: title,
      message: message,
      buttons: {
        ok: {
            label: "Ok",
            className: 'btn-success',
            callback: callback()
        }
      },
      callback: function(){ callback(); }
    });

    $(".modal-header").addClass("bg-success");
    $(".modal-title").addClass("text-white");
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

function bDialog(id, htmlContent, txtTitle, isModal, ) {

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
