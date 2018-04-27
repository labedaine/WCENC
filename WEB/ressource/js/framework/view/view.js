var ViewClass = {
    // classe ViewClass

    template : '',

    events : {},

    timers : {},

    initialize : function(){

    },

    render : function(selector) {
        this.initialize();
        this._selectorForTemplate = selector;

        // chargement du template
        this.loadTemplate();
        // on affiche le template
        this.renderTemplate();

        this.renderView();
        this.bindEvents();
        this.bindTimers();
    },

    renderView : function() {
    },

    bindEvents : function() {
        var self = this;
        if (this.events) {
            for (var evenement in this.events) {
                var evenementArr = evenement.split(" ");
                var type = evenementArr[0];
                var target = evenementArr[1];
                var callbackName = this.events[evenement];

//              console.log(type);
//              console.log(target);
//              console.log(callbackName);
//              console.log(self[callbackName]);

                /* on bind le callback sur this, de mani�re � ce que "this" dans le callback */
                /* corresponde � la vue */
                $(document).on(type, target, self._bind(self[callbackName], this));
            }
        }
    },

    unbindEvents : function() {
        var self = this;
        if (this.events) {
            for (var evenement in this.events) {
                var evenementArr = evenement.split(" ");
                var type = evenementArr[0];
                var target = evenementArr[1];
//              console.log("UNBIND " + type + " " + target);
                $(document).off(type, target);
            }
        }
    },

    addTimer : function(delai, callback) {
        var self = this;

        var _timerId = this._timerIds.length;
        this._timerIds.push(_timerId);

        $('body').everyTime(delai, _timerId, self._bind(callback, this));
    },

    bindTimers : function() {
        var self = this;

        // on stocke un tableau d'identifiants incrémentés de 1 en 1 comme label de timer
        // ces "labels" seront utilisés pour désactiver les timers
        self._timerIds = [];

        if (this.timers) {
            for (var timer in this.timers) {
                var _timerId = this._timerIds.length;
                this._timerIds.push(_timerId);

                var nomCallbackAAppeler = timer;
                var toutesLes = this.timers[nomCallbackAAppeler];

                $(this._selectorForTemplate).everyTime(this.timers[timer], _timerId, self._bind(self[nomCallbackAAppeler], this));
            }
        }
    },

    unbindTimers : function() {

        for (_timerId in this._timerIds) {
            // on stoppe les timers à partir de leur identifiant, utilisé en tant que "label"
            $(this._selectorForTemplate).stopTime(_timerId);
        }
        // On réinitialise le tableau des identifiants de timers
        this._timerIds = [];
    },

    destroy : function() {
        this.unbindEvents();
        this.unbindTimers();
        $(this._selectorForTemplate).empty();
    },

    loadTemplate : function(templateUrl) {
        var self = this;
        var templateUrlToLoad = null;
        if (templateUrl) {
            templateUrlToLoad = templateUrl;
        } else if (this.template) {
            templateUrlToLoad = this.template;
        }

        if (templateUrlToLoad) {
            $.ajax({url : templateUrlToLoad,
                dataType : 'text',
                async : false, // synchrone : on ne rend pas la main tant que le template n'est pas chargé
                success : function(data, textStatus, jqXhr) {
                    self._template = data;
                },
                error : function(jqXHR, textStatus, errorThrown) {
                    console.log(textStatus);
                    console.log(errorThrown);
                }
            });
        }
    },

    renderTemplate : function() {
        if (this._template) {
            $(this._selectorForTemplate).html(this._template);
        }
    },

    /**
     * fonction provenant du framework "underscore.js"
     * permet de binder une fonction sur un contexte
     * utile pour que le "this" dans une fonction corresponde bien à l'objet qui déclare la fonction
     */
    ctor : function(){},
    _bind : function(func, context) {
        var args, bound;

        args = Array.prototype.slice.call(arguments, 2);
        return bound = function() {
            if (!(this instanceof bound)) {
                return func.apply(context, args.concat(Array.prototype.slice.call(arguments)));
            }
            ctor.prototype = func.prototype;
            var self = new ctor;
            ctor.prototype = null;
            var result = func.apply(self, args.concat(Array.prototype.slice.call(arguments)));
            if (Object(result) === result) {
                return result;
            }
            return self;
        };
    }
}
