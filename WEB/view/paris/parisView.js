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
          "click #sauvPronoCompet" : "sauvegardeProno",
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
            $(".active").removeClass('active');
            $(".parisNav li a[href='#paris/" + args[0] + "']").parent('li').addClass('active');
            //$(".parisNav .active").parent().parent().toggle();
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
        
        sauvegardeProno : function(e) {
			
          RestApi.sauvegarderProno($("#selProno").val(), function(data) {
              if (data.success) {
                MessageBox("Votre pronostic sur le vainqueur de la compétition a été sauvegardé !" );
                application.pronostic.id = $("#selProno").val();
              } else {
                ErrorMessageBox('Erreur lors de la prise en compte de votre pronostic.');
              }

            }, function(data) {  console.log(data);});
        },

        sauvegardeParis : function(e) {
          var listParis = [];

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
              } else {
                ErrorMessageBox('Erreur lors de la prise en compte de vos paris.');
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
                    console.log(application.competition);
			  console.log(application.equipe);
			  console.log(application.pronostic);
          // On affiche le vainqueur pronostiqué
          if(nom == "0"||nom=="A") {
			  
			  $("#contenuParis").append("<h3 class='titlePage'>Votre pronostic sur le vainqueur de la compétition</h3>");
		
			  
			  // La competition a commencée
			  if(application.competition.hasstart == 1 ) {
				  // on affiche le gagnant pronostiqué
				  $("#contenuParis").append("<h3 class='titlePage'>Votre pronostic sur le vainqueur de la compétition</h3>");
			  } else {
				  // On met un select
				  $("#contenuParis").append('<div id="containerSauvProno" style="position:fixed;right:35px;top:100px;z-index:999;margin:3px;background:#E8E9EB;border-radius:10px;"></div>');
				  $("#contenuParis").append('<select id="selProno" class="form-control form-group row match my-auto center" style="max-width:300px">');
				  application.equipe.forEach(function (value) {
					  var selected = "";

					  if(value.id == application.pronostic.id) {
						selected = "selected";
					  }
					  $("#contenuParis select").append("<option value='"+value.id+"' "+selected+">"+value.pays+"</option>");
				  });
				  
				  $("#contenuParis").append('<br/><button type="button" class="btn btn-primary" id="sauvPronoCompet" >Sauvegarder votre pronostic</button>');
				  
			  }
		  } else {

			// Sinon la liste des matchs
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
                       $('div[etat]').each(function() {
                           var etat = $(this).attr('etat');
                           var classEtat = "biseauteEtat" + etat;
                           $(this).find('span').first().addClass(classEtat);
                       });

                       // On cré les dropdown
                       $('#tabParis').find('div[past=1]').first().before('<div class="matchItem">Voir les matchs précédents<i class="dropicon fas fa-chevron-down"></i></div><ul id="matchPast"></ul>');
                       $('#tabParis').find('div[past=1]').appendTo('#matchPast');

                       $('#tabParis').find('div[past=0]').first().before('<div class="matchItem">Voir les matchs du jour et suivant<i class="dropicon fas fa-chevron-down"></i></div><ul id="matchFuture"></ul>');
                       $('#tabParis').find('div[past=0]').appendTo('#matchFuture');

                       // Evenement
                       $('.matchItem').on('click', function () {
                          $(this).next('ul').toggle();
                              if ($(this).find('.fas').hasClass('fa-chevron-down'))
                              {
                                $(this).find('.fas').removeClass('fa-chevron-down').addClass('fa-chevron-up')
                              }
                              else {
                                  $(this).find('.fas').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                              }
                            });

                        // On masque les matchs précédents
                        $('#matchPast').toggle();
                   },
                   error: function(msg, textStatus, errorThrown) {
                       console.log("Status: " + textStatus);
                       console.log("Error: " + errorThrown);
                       console.log(msg);
                   }
                 });

              }

            }, function(data) {  console.log(data);});
			}
          },
    });
    return Clazz;
};
