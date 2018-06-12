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

          RestApi.sauvegarderParis(listParis, function(data) {
              if (data.success) {
                MessageBox("Vos paris ont été sauvegardés !" );
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

                       // Mise en forme
                       $('#tabParis').before("<p style='font-size:12px'><i class='fas fa-question-circle' style='color:blue'></i><i> Vos pronostics ne sont pas définitifs tant que le match n'a pas commencé... Changez les à votre guise.</i></p>");
                       if($("td:contains(Equipe inconnu)").length>1) {
                           $('#tabParis').before("<p style='font-size:12px'><i class='fas fa-question-circle' style='color:blue'></i><i> Les équipes de phase finales seront mises à jour automatiquement dès qu'elles seront connues. Vous n'êtes pas obligés de pronostiquer sans connaitre les équipes.</i></p>");
                       }

                       // Bon css au bon endroit
                       $('tr[etat]').each(function() {
                           var etat = $(this).attr('etat');
                           var classEtat = "biseauteEtat" + etat;
                           $(this).find('span').first().addClass(classEtat);
                       });
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
