/**
 * Gère le palmares
 */
var PalmaresViewClass = function(args) {

    var Clazz= $.extend({}, ViewClass, {

        template : 'view/palmares/tmpl/palmares.html?rd='+application.getUniqueId(),

        events : {},

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
            this.chargementPalmares();
        },

        chargementPalmares : function (type) {

          var self  =this;
          var tclass = this;

          RestApi.getPalmares(function(data) {

              if (data.payload) {

				// Pour chaque competition
				$.each(data.payload, function(competition, element ) {
					$("#contenuPalmares").append("<div class='titleGroupe row' id='lignes_" + element.competition_id + "'></div>");
					
					// Pour chaque element de la competition
					$("#lignes_" + element.competition_id).append("<div class='col-sm-8 col-xs-5 right'>"+competition+"</div>");
					$("#lignes_" + element.competition_id).append("<div class='col-sm-4 col-xs-3 right'  style='margin-bottom:10px'><button type='button' competition='"+element.competition_id+"' class='btn btn-primary' style='height:40px'>Voir les nuls</button></div>");
					var cpt = 0;
					var ligne = 0;
					
					$('button[competition='+element.competition_id+']').on('click', function() {
                		var competition = $(this).attr('competition');
                		
                		// Est ce que l'on est caché ou pas
                		if($("#lignes_"+competition).find("[classement]").parent().eq(4).is(":visible")) {
							$("#lignes_"+competition).find("[classement]").find(":not(.nePasCacher)").parent().parent().hide();
							$('button[competition='+competition+']').html('Voir les nuls');
						} else {
							$("#lignes_"+competition).find("[classement]").find(":not(.nePasCacher)").parent().parent().show();
							$('button[competition='+competition+']').html('Cacher les nuls');
						}
                	});
					
					$.each(element.detail, function(id, detail ) {
						
						if(cpt % 3 == 0) {
							$("#lignes_" + element.competition_id).append("<div id='ligne_"+cpt+"' class='row col-md-12 col-xs-3'></div>");
							ligne = cpt;
						}

						// Pour les trois premiers on les met en forme
						$("#ligne_"+ligne)
									.append("<div case=" + cpt + " classement="+cpt+" class='row col-md-4 col-xs-4'></div>");
									
						$("[case="+cpt+"]")
									.append("<div classement="+cpt+" class='col-md-2 col-xs-1 left' style='padding:10px;max-width:20px'>"+(cpt+1)+ "/</div>")
									.append("<div classement="+cpt+" class='col-md-2 col-xs-1 right' style='padding:10px;max-width:20px'>" + detail.points + "</div>")
									.append("<div classement="+cpt+" class='col-md-4 col-xs-2 center' style='padding:10px;max-width:100px'>" + detail.login + "</div>");
						cpt++;
                	});
                	
				});
				
				$("[classement=0]").addClass("bg-warning text-white nePasCacher");
				$("[classement=1]").addClass("bg-danger text-white nePasCacher");
				$("[classement=2]").addClass("bg-secondary text-white nePasCacher");
				
				// on les cache
				$("[classement]:not(.nePasCacher)").parent().parent().hide();
				$(".nePasCacher").parent().parent().show();
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
