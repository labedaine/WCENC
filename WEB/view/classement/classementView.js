/**
 * Classe gérant les paris
 * La navigation se fait par l'intermédiaire de l'événement window.onhashchange (déclaré dans application.js
 * Le click sur un menu met à jour l'url et déclenche la navigation
 * Un trap du click est néanmoins nécessaire afin de raffraichir une vue déjà chargée. (voir renderView)
 */
var ClassementViewClass = function(args) {

    var Clazz= $.extend({}, ViewClass, {

        template : 'view/classement/tmpl/classement.html?rd='+application.getUniqueId(),

        events : {
          "click .classementNav a" : "menuClassement"
        },

        timers : {},

        initialize : function() {
          if (!args[0])
          {
            args[0] = "Individuel";  
          }
        },

        renderView : function() {
            // on met en forme le menu
            this.miseEnForme();
        },

        miseEnForme : function() {
            // on crée les jqContainers
            this.initToolTips();
            //sousmenu classement
            $(".activeGroupe").removeClass('activeGroupe');
            $(".classementNav li a[href='#classement/" + args[0] + "']").parent('li').addClass('activeGroupe');
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

        menuClassement : function(e) {
          var target = $( e.target );
          console.log('Chargement classement ' + target.data('nom'));

        },
    });
    return Clazz;
};
