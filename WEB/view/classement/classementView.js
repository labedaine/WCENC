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
            $(".activeGroupe").removeClass('activeGroupe');
            $(".classementNav li a[href='#classement/" + args[0] + "']").parent('li').addClass('activeGroupe');
            this.chargementClassement();


        },


        showClassementInter : function () {

          $('#promoSelect').on('change', function () {
            console.log($(this).val());
            $('.ligneInter').hide();
            $('.ligneInter[data-promo="' + $(this).val() + '"]').show();
          });
          $('#promoSelect').change();

        },

        chargementClassement : function () {
          var tclass = this;
          var type = args[0];

          RestApi.getListeClassement({}, function(data) {
              if (data.payload) {
                console.log('Chargement classement ');
                console.log(data.payload);
                $.ajax({
                   beforeSend: function() { $('#contenuClassement').hide();},
                   type: "POST",
                   url: "view/classement/tmpl/classement.ajax.php",
                   data: {
                     dataCollec: data.payload.collec,
                     dataIndiv: data.payload.indiv,
                     dataPromo: data.payload.promo,
                     type: type

                    },
                   success: function(result) {

                       console.log('result ajax');
                       $('#contenuClassement').html(result);
                       tclass.showClassementInter();
                       $('#tabParisIndiv').DataTable( {
                         "language": { "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/French.json"  }
                          });
                       $('#contenuClassement').show();
                   },
                   error: function(msg, textStatus, errorThrown) {
                       console.log("Status: " + textStatus);
                       console.log("Error: " + errorThrown);
                       console.log(msg);
                   }
                 });

              }

            }, function(data) {  console.log(data); });
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
