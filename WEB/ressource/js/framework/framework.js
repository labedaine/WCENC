var Framework = {

        // dependences propres au framework
        _deps : [
             'ressource/js/framework/view/view.js',
             'ressource/js/framework/models/model.js',
             'ressource/js/framework/models/userModel.js',
             'ressource/js/framework/models/competitionModel.js',
             'ressource/js/framework/models/equipeModel.js',
             'ressource/js/framework/models/pronosticModel.js'
        ],

        // permet de savoir quand les dependences propres au framework ont �t� charg�es
        frameworkReady : $.Deferred(),
        // permet de savoir quand les dependences propres � l'applications ont �t� charg�es
        applicationReady : $.Deferred(),

        // initialisation
        initializeFramework : function() {
            var self = this;
            // chargement des js necessaires au framework
            this._loadDependencies();
            // une fois les dependances propres au framework charg�es, on charge celle de l'application
            $.when(this.frameworkReady).done(
                    function() {
                        self.loadDependencies();
                    });
            // une fois les dépendences de l'applications charg�es, on d�marre l'application
            $.when(this.applicationReady).done(
                    function() {
                        if (RestApi) {
                            // si on a inclu RestApi, on enregistre un callback par défaut qui appelera
                            // la méthode erreurTechnique (à surcharger par l'application)
                            RestApi.registerErrorCallbacks({
                                'fallback' : function(x, e, t) {
                                    // t : si renseigné, libellé d'erreur renvoyé par apache (exemple Internal Server Error)
                                    self.erreurTechnique(t);
                                }});
                            if (self.restApiApplicationContext) {
                                RestApi.setApplicationContext(self.restApiApplicationContext);
                            }
                        }
                        self.start();
                    });
        },
        start : function() {
            // a surcharger par l'application
        },
        erreurTechnique : function() {
            // a surcharger par l'application
        },

        _loadDependencies : function() {
            if (this._deps) {
                var self = this;
                var numberLoaded = 0;
                $.each(this._deps, function(idx, val) {
                    $.ajax({url : val,
                            dataType : 'script',
                            success : function(data, textStatus, jqXhr) {
                                            eval(data);
                                            numberLoaded++;
                                            if (numberLoaded == self._deps.length) {
                                                self.frameworkReady.resolve();
                                            }

                                      },
                            error : function(jqXHR, textStatus, errorThrown) {
                                            console.log(textStatus);
                                            console.log(errorThrown);
                                    } 
                            });
                });
            }
        },

        // chargement des dependences
        loadDependencies : function() {
            if (this.deps) {
                var self = this;
                var numberLoaded = 0;
                $.each(this.deps, function(idx, val) {
                    $.ajax({url : val,
                            dataType : 'script',
                            success : function(data, textStatus, jqXhr) {
											eval(data);
											numberLoaded++;
											if (numberLoaded == self.deps.length) {
												self.applicationReady.resolve();
											}
                                      },
                            error : function(jqXHR, textStatus, errorThrown) {
                                            console.log(val+ " "+ textStatus);
                                            console.log(errorThrown);
                                    }
                            });
                });
            }
        }
}
