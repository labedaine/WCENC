/**
 * Gère le palmares
 */
var PalmaresViewClass = function(args) {

    var Clazz= $.extend({}, ViewClass, {

        template : 'view/palmares/tmpl/palmares.html?rd='+application.getUniqueId(),

        events : {
            "click #ind" : "showInd",
            "click #coll" : "showColl",
            "click #promo" : "showPromo",
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
					$("#lignes_" + element.competition_id).append("<div class='col-sm-12 right'>"+competition+"</div>");
					var cpt = 0;
					var ligne = 0;
					
					$.each(element.detail, function(id, detail ) {
						
						if(cpt % 3 == 0) {
							$("#lignes_" + element.competition_id).append("<div id='ligne_"+cpt+"' class='row col-md-12 col-xs-3'></div>");
							ligne = cpt;
						}

						// Pour les trois premiers on les met en forme
						$("#ligne_"+ligne)
									.append("<div case=" + cpt + " classement="+cpt+" class='row col-md-4 col-xs-4'></div>");
									
						$("[case="+cpt+"]")
									.append("<div classement="+cpt+" class='col-md-2 col-xs-1 center' style='padding:10px;max-width:50px'>"+(cpt+1)+ "/</div>")
									.append("<div classement="+cpt+" class='col-md-2 col-xs-1 center' style='padding:10px;max-width:50px'>" + detail.points + "</div>")
									.append("<div classement="+cpt+" class='col-md-4 col-xs-2 center' style='padding:10px;max-width:50px'>" + detail.login + "</div>");
						// Pour les suivants on les cache
						cpt++;
                	});
				});
				
				$("[classement=0]").addClass("bg-warning text-white");
				$("[classement=1]").addClass("bg-danger text-white");
				$("[classement=2]").addClass("bg-secondary text-white");
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
