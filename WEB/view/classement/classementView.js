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
            "click #ind" : "showInd",
            "click #coll" : "showColl",
            "click #promo" : "showPromo",
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
            this.showInd();
        },


        showClassementInter : function () {
          $('#promoSelect').change();

        },

        showInd : function() {
            var self = this;
            self.chargementClassement('Individuel');
        },

        showColl : function() {
            var self = this;
            self.chargementClassement('Collectif');
        },


        showPromo : function() {
            var self = this;
            self.chargementClassement('Promo');
        },


        chargementClassement : function (type) {

          var self  =this;
          var tclass = this;

          RestApi.getListeClassement({type}, function(data) {

              if (data.payload) {

                $.ajax({
                   beforeSend: function() { $('#contenuClassement').hide();},
                   type: "POST",
                   url: "view/classement/tmpl/classement.ajax."+type+".php",
                   data: {
                     dataCollec: data.payload.collec,
                     dataIndiv: data.payload.indiv,
                     dataPromo: data.payload.promo,
                     type: type
                    },
                   success: function(result) {

                       $('#contenuClassement').html(result);



                        /*$("#tabParisColl").hide();
                        $("#tabParisPromo").hide();
                        $("#tabParisIndiv").hide();
*/
                        $('#coll').removeClass("activeGroupe");
                        $('#promo').removeClass("activeGroupe");
                        $('#ind').removeClass("activeGroupe");

                        if(type == "Collectif") {
                            $("#tabParisColl").show();
                            $('#coll').addClass("activeGroupe");
                            $('#titreClassement').html("Classement collectif");

                        } else if(type == "Promo") {
                            $("#tabParisPromo").show();
                            $('#promo').addClass("activeGroupe");
                            $('#titreClassement').html("Classement par promotion");

                        } else {

                            $("#tabParisIndiv").show();
                            $('#ind').addClass("activeGroupe");
                            $('#titreClassement').html("Classement individuel");

                            $('#promoSelect').on('change', function () {
                                $('.ligneInter').hide();

                                if( $(this).val() === "Toutes") {

                                    $('.ligneInter').show();

                                    $('[name="tabParisIndiv_length"] option[value=100]').prop('selected', true);
                                    $('[name="tabParisIndiv_length"]').change();


                                } else {

                                    $('.ligneInter[data-promo="' + $(this).val() + '"]').show();

                                    $('[name="tabParisIndiv_length"] option[value=100]').prop('selected', true);
                                    $('[name="tabParisIndiv_length"]').change();

                                }


                              });
                              $('#promoSelect').change();

                        }



                       $('#tabParisIndiv').DataTable( {
                         "language": { "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/French.json"  }
                          });
                       $('#contenuClassement').show();

                       // Evenement sur les boutons
                       $('tr[ligne]').each(function() {
                            var id = $(this).attr('id');
                            var login = $(this).attr('login');

                            $(this).find('button').on('click', function() {

                                RestApi.getParisUtilisateur(id, function(data) {

                                    if(data.success) {
                                        var payload = data.payload;
                                        var html = "";

                                        html += '<h3 class="titlePage">légende</h3><div>';
                                        html += '<div class="p-3 mb-2 bg-warning text-white pariUtilisateur">3 points</div>';
                                        html += '<div class="p-3 mb-2 bg-danger text-white pariUtilisateur">2 points</div>';
                                        html += '<div class="p-3 mb-2 bg-secondary text-white pariUtilisateur">1 points</div>';
                                        html += '<div class="p-3 mb-2 bg-dark text-white pariUtilisateur">0 points</div>';
                                        html += '<div style="clear:both"></div>';
                                        html += "</div>";

                                        var phase = "";

                                        $.each(payload, function(index, value) {

                                            coeff = value.phase_id-2;
                                            if(coeff <= 1 ) {
                                                coeff = 1;
                                            }

                                            if(phase != value.libelle) {
                                                if(phase != "") {
                                                    html += "</div><div style='clear:both'></div>";
                                                }
                                                if(coeff > 1) {
                                                    html += "<h3 class='titlePage'>" + value.libelle + " (x " + coeff + ")</h3><div>";
                                                } else {
                                                    html += "<h3 class='titlePage'>" + value.libelle + "</h3><div>";
                                                }

                                                phase = value.libelle;
                                            }


                                            var classPari = "bg-info";
                                            if(value.paris_dom !== null && value.paris_ext != null) {

                                                points = value.points_acquis/coeff;
                                                switch(points) {
                                                    case 0:classPari = "bg-dark";
                                                    break;
                                                    case 1:classPari = "bg-secondary";
                                                    break;
                                                    case 2:classPari = "bg-danger";
                                                    break;
                                                    case 3:classPari = "bg-warning";
                                                    break;
                                                }
                                            } else {
                                                classPari = "bg-light";
                                            }

                                            html += '<div class="p-3 mb-2 '+classPari+' text-white pariUtilisateur">';
                                            html += '<img style="padding:2px" src="ressource/img/drapeaux/drapeau-'+(value.pays1).toLowerCase()+'.png" title="'+value.pays1+'"/>';

                                            if(value.paris_dom !== null && value.paris_ext != null) {
                                                html += '<span style="padding:2px">' + value.paris_dom + '</span><span style="padding:2px">  -  </span><span style="padding:2px">' + value.paris_ext + "</span>";
                                            } else {
                                                html += '<span style="padding:2px;font-size:10px">Aucun pari</span>';
                                            }


                                            html += '<img style="padding:2px" src="ressource/img/drapeaux/drapeau-'+(value.pays2).toLowerCase()+'.png" title="'+value.pays2+'"/>';
                                            html += "</div>";
                                        });

                                        html += "</div><div style='clear:both'></div>";

                                        MessageBoxLarge(html, "Liste des paris de " + login);

                                        // Un ascenseur pour faire joli
                                        $('.bootbox-body').css('overflow-x','auto').css('max-height','350px');
                                        $('.bootbox-body').find('.bg-light').css('border', '1px solid black').find('span').css('color', 'black');

                                    } else {
                                        ErrorMessageBox('Erreur lors de la récupération des paris de '+login);
                                    }
                                });
                            });
                       });
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
