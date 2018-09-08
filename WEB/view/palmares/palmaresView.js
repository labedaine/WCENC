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
					
					// Pour chaque element de la competition
					$("#contenuPalmares").append("<div id='header_"+element.competition_id+"' class='titleGroupe row col-md-12 col-sm-12 col-xs-12 '></div>");
					$("#header_"+element.competition_id).append("<div class='col-md-2 col-sm-2 col-xs-1 right'  style='margin-bottom:10px'><button type='button' competition='"+element.competition_id+"' class='btn btn-primary' style='height:40px;width:140px'>Voir les nuls</button></div>")
					.append("<div class='col-md-8 col-sm-8 col-xs-5 right'>"+competition+"</div>");
					
					$("#contenuPalmares").append("<table id='"+element.competition_id+"' class='classementTable table table-hover table-sm no-gutter' data-page-length='100'></table>");
					$("#"+element.competition_id).append('<thead  class="thead-light"><tr><th>#</th><th>Points</th><th>Login</th><th>Prénom</th><th>Promo</th></tr></thead><tbody>');

					var cpt = 0;
					var ligne = 0;
					
					$('button[competition='+element.competition_id+']').on('click', function() {
                		var competition = $(this).attr('competition');
                		
                		// Est ce que l'on est caché ou pas
                		if($("#"+competition).find("tr").eq(5).is(":visible")) {
							$("#"+competition).find(".ligneInter").find(":not(.nePasCacher)").parent().hide();
							$('button[competition='+competition+']').html('Voir les nuls');
						} else {
							$("#"+competition).find(".ligneInter").show();
							$('button[competition='+competition+']').html('Cacher les nuls');
						}
                	});
        
					$.each(element.detail, function(id, detail ) {
						$("#"+element.competition_id+" tbody").append("<tr ligne id='"+element.competition_id+"' class='ligneInter'></tr>");
						$("#"+element.competition_id+" tbody tr").last()
							.append("<td classement="+cpt+" class='left'>"+(cpt+1)+ "</td>")
							.append("<td classement="+cpt+" class='left'>" + detail.points + "</td>")
							.append("<td classement="+cpt+" class='left'>" + detail.login + "</td>")
							.append("<td classement="+cpt+" class='left'>" + detail.prenom + "</td>")
							.append("<td classement="+cpt+" class='left'>" + detail.promo + "</td>");
						cpt++;
                	});
                	
                	$("#"+element.competition_id).append('</tbody></table>');
                	$("#"+element.competition_id + " [classement=0]").addClass("bg-warning text-white nePasCacher");
					$("#"+element.competition_id + " [classement=1]").addClass("bg-danger text-white nePasCacher");
					$("#"+element.competition_id + " [classement=2]").addClass("bg-secondary text-white nePasCacher");
					
					// on les cache
					$(".ligneInter").hide();
					$(".nePasCacher").parent().show();
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
