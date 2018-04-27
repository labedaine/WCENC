/**
 * Classe gérant les paris
 * La navigation se fait par l'intermédiaire de l'événement window.onhashchange (déclaré dans application.js
 * Le click sur un menu met à jour l'url et déclenche la navigation
 * Un trap du click est néanmoins nécessaire afin de raffraichir une vue déjà chargée. (voir renderView)
 */
var ParisViewClass = function(args) {

    var Clazz= $.extend({}, ViewClass, {

        template : 'view/pari/tmpl/paris.html?rd='+application.getUniqueId(),

        events : {
        },

        timers : {},

        initialize : function() {

        },

        renderView : function() {
            // on met en forme le menu
            this.miseEnForme();
        },

        miseEnForme : function() {
            // on crée les jqContainers
            this.initToolTips();
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
