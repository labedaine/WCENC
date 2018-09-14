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
          "click [lienParis]" : "goToPhase",
          "change #selPronoDiv" : "goToPhaseC"
        },
        
        phase : null,

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

            // Phase par défaut mais en attendant
            if(this.phase == null) {
				this.phase = application.competition.cmatchday;
			}
            this.afficheUnderligne();
            this.menuParis();
        },

        afficheUnderligne: function () {
			$("[lienParis]").removeClass('selected');
			$("#parisLink" + this.phase).addClass('selected');
		},
		
		goToPhaseC : function(e) {
			var self = this;
			self.phase = $(e.currentTarget).val();
			self.menuParis();
			return false;
		},
		
		goToPhase : function(e) {
			var self = this;
			self.phase = $(e.currentTarget).attr("lienParis");
			self.menuParis();
			return false;
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
            
            return false;
        },

        menuParis : function() {
			var self = this;
		  $('#contenuParis').html("");
		  $('#selPronoDiv').val(self.phase);

          // On affiche le vainqueur pronostiqué
          if(self.phase == "0") {
			  
			  $("#contenuParis").append("<center><h3 class='titlePage'>Votre pronostic sur le vainqueur de la compétition</h3></center>");
		
			  // La competition a commencée
			  if(application.competition.hasstart == 1 ) {
				  // on affiche le gagnant pronostiqué
				  var equipe = application.equipe[application.pronostic.id];
				  
				  $("#contenuParis").append("<center><br/><h3 class='titlePage'>"+equipe.pays+"</h3></center>")
									.append('<center><br/><img src="ressource/img/drapeaux/'+equipe.id+'.png"/></center>');
			  } else {
				  // On met un select
				  $("#contenuParis").append('<div id="containerSauvProno" style="position:fixed;right:35px;top:100px;z-index:999;margin:3px;background:#E8E9EB;border-radius:10px;"></div>');
				  $("#contenuParis").append('<center><select id="selProno" class="form-control form-group row match my-auto center" style="max-width:300px"></center>');
				  application.equipe.forEach(function (value) {
					  var selected = "";

					  if(value.id == application.pronostic.id) {
						selected = "selected";
					  }
					  $("#contenuParis select").append("<option value='"+value.id+"' "+selected+">"+value.pays+"</option>");

				  });
				  
				  $("#contenuParis").append('<center><br/><button type="button" class="btn btn-primary" id="sauvPronoCompet" >Sauvegarder votre pronostic</button></center>');
				  
			  }
		  } else {

			// Sinon la liste des matchs
			RestApi.getListeMatch(this.phase, function(data) {

              if (data.success) {

				  if(data.payload.length != 0) {
				  
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
							
							$('#titreListeMatch').html("Liste des matchs "+ $("#parisLink"+self.phase).html());

					   },
					   error: function(msg, textStatus, errorThrown) {
						   console.log("Status: " + textStatus);
						   console.log("Error: " + errorThrown);
						   console.log(msg);
					   }
					 
					});
				} else {
					$("#contenuParis").append("<center><br/><h3 class='titlePage'>Aucun match encore programmé</h3></center>")
				}
              }

            }, function(data) {  console.log(data);});
			}
			
			self.afficheUnderligne();
			return false;
          },
    });
    return Clazz;
};
