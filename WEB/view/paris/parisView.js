/**
 * Classe gérant les paris
 * La navigation se fait par l'intermédiaire de l'événement window.onhashchange (déclaré dans application.js
 * Le click sur un menu met à jour l'url et déclenche la navigation
 * Un trap du click est néanmoins nécessaire afin de raffraichir une vue déjà chargée. (voir renderView)
 */
var ParisViewClass = function(args) {

    var Clazz= $.extend({}, ViewClass, {

        template : 'view/paris/tmpl/paris.html?rd='+application.getUniqueId(),

        events : {
          "click #sauvParis" : "sauvegardeParis"
        },

        timers : {},

        initialize : function() {
          if (!args[0])
          {
            args[0] = "GroupeA";
          }

        },

        renderView : function() {
            // on met en forme le menu
            this.miseEnForme();
        },

        miseEnForme : function() {
            // on crée les jqContainers
            this.initToolTips();
            $(".activeGroupe").removeClass('activeGroupe');
            $(".parisNav li a[href='#paris/" + args[0] + "']").parent('li').addClass('activeGroupe');
            this.menuParis();
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
        sauvegardeParis : function(e) {
          var listParis = 'listeMatch';
          RestApi.sauvegarderParis(listParis, function(data) {
              if (data.success) {
                console.log('sauvegarde paris ' + data.payload);
                console.log(data);
              }

            }, function(data) {  console.log(data);});
        },

        menuParis : function(e = null) {
          if (!e)
          {
            nom = args[0].slice(-1);
          }
          else {
            var target = $( e.target );
            var nom =  target.data('nom');
          }
          console.log('Chargement paris ' + nom);
          RestApi.getListeMatch(nom, function(data) {
              if (data.success) {
                console.log('Chargement paris ' + nom);
                $.ajax({
                   beforeSend: function() { $('#contenuParis').hide();},
                   type: "POST",
                   url: "view/paris/tmpl/paris.ajax.php",
                   data: { data: data.payload },
                   success: function(result){
                       $('#contenuParis').html(result);
                       $('#contenuParis').show();
                   },
                   error: function(msg, textStatus, errorThrown) {
                       console.log("Status: " + textStatus);
                       console.log("Error: " + errorThrown);
                       console.log(msg);
                   }
                 });

              }

            }, function(data) {  console.log(data);});
          },
    });
    return Clazz;
};
