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
          "click #sauvParis" : "sauvegardeParis",
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
            $('.phaseItem').on('click', function () {
              $(this).next('ul').toggle();
              if ($(this).find('.fas').hasClass('fa-chevron-down'))
              {
                $(this).find('.fas').removeClass('fa-chevron-down').addClass('fa-chevron-up')
              }
              else {
                  $(this).find('.fas').removeClass('fa-chevron-up').addClass('fa-chevron-down');
              }


            });
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
          var listParis = [];
          console.log("Récupération des paris");

          $('.match').each(function (e) {
            var id = $(this).data('idmatch');
            var dom = $(this).find('.inputParisDom').first().val();
            var ext = $(this).find('.inputParisExt').first().val();

            if (dom != "" && ext != "")
            {
              listParis.push({ "id" : id, "scoreDom" : dom, "scoreExt" : ext});
            }
          });

          console.log(listParis);
          RestApi.sauvegarderParis(listParis, function(data) {
              if (data.success) {
                console.log("Sauvegarde terminée");
              }

            }, function(data) {  console.log(data);});
        },

        showPhase : function(e) {
          console.log($(e));
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
          RestApi.getListeMatch(nom, function(data) {
              if (data.success) {
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
