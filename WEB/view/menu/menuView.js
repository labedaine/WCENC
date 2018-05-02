/**
 * Classe gérant la barre de menus
 * La navigation se fait par l'intermédiaire de l'événement window.onhashchange (déclaré dans application.js)
 * Le click sur un menu sinaps met à jour l'url et déclenche la navigation.
 * Un trap du click est néanmoins nécessaire afin de raffraichir une vue déjà chargée (bug 122367). (voir renderView)
 */
var MenuViewClass = function(args) {

    var Clazz= $.extend({}, ViewClass, {

        template : 'view/menu/tmpl/menu.html?rd='+application.getUniqueId(),

        events : {
            // Bind sur les menus
            "click #logoutLink"              : "logout"
        },

        timers : {"affichageinfoApplisMasquees" : 5000},

        initialize : function() {

        },

        renderView : function() {
            // on met en forme le menu
            this.miseEnForme();

            /**
            * Sur un click de menu, on recharge systèmatiquement le module,
            * même si c'est le module actif
            * Ce code n'est à exécuter qu'en cas de click sur un module déjà affché
             * (sinon, géré par l'événement window.onhashchange (application.js)
             */
            $('#navbarBet a').each(function() {
                $(this).bind("click", function () {
                    var hashACharger=$(this).attr('href');
                    var hashDejaCharge=window.location.hash;
                    if (hashACharger === hashDejaCharge) {
                        window.location.hash = hashACharger;
                        application.gotoModuleByHash();
                    }
                });
            });
        },

        logout : function() {
            application.logout();
        },

        affichageinfoApplisMasquees : function() {

        },

        miseEnForme : function() {
            // on crée les jqContainers
            this.initToolTips();
            $('#pageContainer').css('padding-top',80);
            $("#nomUser").html(application.user.login);
        },

        checkDroitsAcces : function() {
            if (application.user) {

                // ADMINISTRATION
                if (!application.user.hasModuleAccess('administration')) {
                    $('#administration').remove();
                }
            }
        },

        /**
         * Fonction d'initialisation des tooltips
         */
        initToolTips : function(){

            $("a, img").tooltip({
                  content: function () {
                      return $(this).prop('title');
                  },
                  track:true
               });
        },
    });
    return Clazz;
};
