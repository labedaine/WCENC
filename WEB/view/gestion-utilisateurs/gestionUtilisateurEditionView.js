/**
 * Classe GestionUtilisateurEditionView :
 * Ajout ou modification d'un utilisateur
 *
 * Cette classe est appelée depuis le fichier "gestionUtilisateursView.js"
 *
 * @param {array} args : non utilisé
 * @param {integer} idUtilisateur : si 0 : ajout, > 0 alors modification
 * @returns {@exp;$@call;extend
      -----------------------------------------------------------------------------}
 */
var GestionUtilisateurEditionView = function(args, idUtilisateur) {

    var Clazz= $.extend({}, ViewClass, {

        template : '/apps/restitution/modules/gestion-utilisateurs/tmpl/gestionUtilisateurEditionView.html?rd='+application.getUniqueId(),

        idUtilisateur: idUtilisateur,
        type : null,
        mainWindow : null,
        listeBoutonsDisponibles: ["del_gridGroupesUtilisateur", "edit_gridGroupesUtilisateur"],
        events : {},

        timers : {},


        /**
         * initialisation
         */
        initialize : function() {
            var self = this;

            // Intégration des éléments communs
            jQuery.extend(this, classJqGridUtils);
            this.tooltips = {};
        },

        /**
         * Fonction d'initialisation des tooltips
         */
        initToolTips : function(){
            $("#div-utilisateur-edition, #gridGroupesUtilisateur, button").tooltip({
                  content: function () {
                      return $(this).prop('title');
                  },
                  track:true
               });
               this.cacheTooltipDansLeCoin();
        },

        /**
         * rendu de la vue
         */
        renderView : function() {
			var self = this;
			
            // on met en forme la vue
            this.creerBoiteDeDialogue();

            /**
             * Ajout utilisateur, saisies automatiques :
             *  - le login est généré à partir de la partie gauche du champ email
             *  - les champs "Mot de passe" et "confiramtion" sont générés à partiur du champ Prénom
            */

			//NOM BZ 144748
			$('#input_NOM').keyup(function() {
				$('#input_NOM').val($('#input_NOM').val().toUpperCase());
			});
			// PWD1 et 2 = Prénom (avec 1ère lettre majuscule)
			$('#input_PRENOM').keyup(function () {
				var prenom = $('#input_PRENOM').val();
				var newPwd = prenom;
				if (prenom.length > 0) {
					// BZ 144748
					$('#input_PRENOM').val($('#input_PRENOM').val().substr(0,1).toUpperCase()+($('#input_PRENOM').val().substr(1)));
					newPwd = $('#input_PRENOM').val();
				}
				$('#input_PWD1').val(newPwd);
				$('#input_PWD2').val(newPwd);
			});
			// Login = email (à gauche du @)
			$('#input_EMAIL').keyup(function () {
				// Le champ login ne peut être renseigné qu'à la création
				if ( ! $('#input_LOGIN').prop('disabled')) {
					$('#input_LOGIN').val($('#input_EMAIL').val());
				}
			});
        },

        /**
         * Création de la boite de dialogue contenant les formulaires de saisie
         * @returns {undefined}
         */
        creerBoiteDeDialogue: function() {
            var self = this;

            // creation d'une boite de dialog sinapsDialog modal
            self.mainWindow = new sinapsDialog('mainWindow');

            var title = "Modification d'un utilisateur";
            if(!idUtilisateur) {
                title = "Création d'un nouvel utilisateur";
            }

            self.mainWindow.title(title).modal(true)
            .html('<div id="div-utilisateur-edition">'+self._template+'</div>')
            .addButton("Valider", function() { // Ajout du bouton valider
                self.enregistrerUtilisateur();
            }).addButton("Annuler", function(){ // Ajout du bouton annuler
                // Clique sur annuler ferme la boite de dialogue
                self.mainWindow.close();
            }).onClose(function() { self.mainWindow.remove();}).create().open();// Ferme la boite si clique sur annuler

            $("#div_infos_utilisateur").jqcontainer({
                draggable: false,
                collapsable: false,
                width: 'auto',
                height: 'auto',
                title: 'Informations générales'
            });

            $("#div_infos_utilisateur").parent().css('float', 'left');

            if (self.idUtilisateur > 0) {
                self.getInfosUtilisateur();
            }

            // Si idUtilisateur > 0 : on charge la liste des groupes
            self.createGridGroupesUtilisateur();

            // On replace la fenetre main
            $('[aria-describedby=mainWindow]').position({
                my: "center center",
                at: "center center",
                of: window
            });

            // On retourne que tout s'est bien passé
            return true;
        },

        /**
         * Retourne toutes les infos concernant l'utilisateur
         */

        getInfosUtilisateur : function() {
            var self = this;

            $('#input_LOGIN').prop('disabled', true);

            RestApi.getInfosUtilisateur(self.idUtilisateur, function(data) {
                if (data) {
                    if (data.success) {
                        application.infosUser = data.payload;

                        // On met à jour les différents champs INPUT
                        $('#input_NOM').val(application.infosUser.nom.toUpperCase());
                        $('#input_PRENOM').val(application.infosUser.prenom.substr(0,1).toUpperCase()+application.infosUser.prenom.substr(1));
                        $('#input_EMAIL').val(application.infosUser.email);
                        $('#input_LOGIN').val(application.infosUser.login);
                        $('#input_PWD1,#input_PWD2').val(application.infosUser.password);

                        var listePreferences = application.infosUser.preferences;
                        // On recherche la clef "droitsSpecifiques" -> valeur "gestionHabilitations"
                        // On recherche la clef "typeUtilisateur" -> valeur "EOM"
                        for(var idx in listePreferences){
                            if (listePreferences[idx].valeur === 'gestionHabilitations') {
                               $('#chk_Habilitations_Oui').attr('checked', 'checked');
                            }
                            if (listePreferences[idx].valeur === 'EOM') {
                               $('#chk_EOM_Oui').attr('checked', 'checked');
                            }
                        }
                        
                        // Mise en forme du contenu des champs
						$('.navButton').hide();
                        
                    } else {
                        ErrorMessageBox('Erreur lors de la récupération des informations relatives à l\'utilisateur');
                    }
                }
            });
        },

        /**
         * Permet la création du grid pour gestion des groupes
         */

        createGridGroupesUtilisateur : function () {

            var self = this;

            var colModel = [
                {name: 'idGroupe', index:'idGroupe', width:55, hidden:true, key:true },
                {name: 'nomGroupe', index:'nomGroupe', sortable:true, width:150, align:"left" }
            ];
            var colNames = ['id', 'Nom du groupe' ];

            jQuery('#gridGroupesUtilisateur').jqGrid({
                jsonReader : { // Initialise le jsonReader voir doc jqGrid
                    root: "rows",
                    page: "page",
                    total: "total",
                    records: "records",
                    repeatitems: true,
                    cell: "cell",
                    id: "idGroupe",
                    userdata: "rows",
                    subgrid: {
                        root:"rows",
                        repeatitems: true,
                        cell:"cell"
                    }
                },
                pager: '#pager_gridGroupesUtilisateur',
                caption: "Groupes de l'utilisateur",
                url: '/apps/restitution/services/utilisateur/listeGroupes',
                datatype: "json",
                postData: {idUtilisateur: self.idUtilisateur},
                colNames:   colNames,
                colModel:   colModel,

                multiselect: false,
                height: 280, // Hauteur du jqGrid
                width: 400,
                rowNum: ConfigJqGrid.rowNum,
                rowList: ConfigJqGrid.rowList,
                sortname: 'nom',
                viewrecords: true,
                sortorder: "asc",
                viewsortcols: [true, 'vertical', true],
                hidegrid: false,

                beforeRequest: function() {
                    self.blocageBoutons(0);
                },

                loadBeforeSend: function(xhr,settings) {

                },

                beforeProcessing: function(data, status, xhr) {
                    /**
                     * Invoque les fonctions:
                     *  - miseAJourDuContenuDesFiltresAvecCookie
                     *  - miseEnFormeToolbar
                     */
                    self.miseEnFormeBeforeProcessing("gridGroupesUtilisateur");
                },

                loadComplete: function(data) {

                    var grid = jQuery('#gridGroupesUtilisateur');

                    /** **********************
                     *       MISE EN PAGE    *
                     *************************/

                    /**
                     * Invoque les fonctions:
                     *  - cacherLignesGroupement
                     */
                    self.miseEnFormeLoadComplete();

                    // On replace le scroll comme il était
                    grid.closest(".ui-jqgrid-bdiv").scrollTop(self.scrollPosition);

                    // La commande précédente masque les opérateurs
                    // -> on reforce la mise en forme de la toolbar
                    self.miseEnFormeToolbar(this.id);

                    self.cacheTooltipDansLeCoin();
                    self.initialiseLienVersGroupe();
                    self.initToolTips();
                    unsetWaitForm();
                },

                onSelectRow: function(id) {

                    // ON débloque tous les boutons
                    self.blocageBoutons(id);

                    self.montreCacheBoutonSuppRetablir(id);
                }
            });

            jQuery("#gridGroupesUtilisateur").jqGrid(
                'navGrid',
                '#pager_gridGroupesUtilisateur',
                {
                    // EDIT
                    edit: true,
                    edittext:"Rétablir",
                    edittitle:"Rétablir une habilitation",
                    editicon: 'ui-icon-arrowrefresh-1-w',
                    editfunc: function(rowId) {
                        self.retablirLigne(rowId);
                    },

                    // ADD
                    add: true,
                    addtext:"Ajouter",
                    addtitle:"Ajouter un ou plusieurs groupes",
                    addfunc: function(idUser) {
                        self.ajouterGroupeWindow();
                    },

                    // DEL
                    del: true,
                    deltext:"Supprimer",
                    deltitle:"Supprimer le groupe sélectionnée",
                    delfunc: function(rowId) {
                        self.supprimeLigne(rowId);
                    },
                    // SEARCH
                    search:false,

                    refresh:true,
                    refreshtext: "Réinitialiser",

                    beforeRefresh: function() {
                        $("#gridGroupesUtilisateur").jqGrid('resetSelection');
                        self.scrollPosition = $("#gridGroupesUtilisateur").closest(".ui-jqgrid-bdiv").scrollTop();
                    }
                },
                {}, // edit options
                {}, // add options
                {}  // del options
            );

            // On cache le select du nombre de groupes sachant qu'il atteindra jamais 1000
            $('#pager_gridGroupesUtilisateur_center').hide();

            // Sert à passer le refresh à gauche dans les boutons du navgrid
            this.miseEnFormeGridUtiliseNavGrid("gridGroupesUtilisateur");
            // Par défaut on bloque tout
            self.blocageBoutons(0);
            // on désactive le bouton 'Rétablir'
            $('#edit_gridHabilitations').switchClass('ui-state-enabled', 'ui-state-disabled');

         },

              /**
         * Fonction de blocage de boutons
         * Appelé dans :
         *      - Grid onSelectRow
         *      - Grid onSelectAll
         */

        blocageBoutons : function(rowId){

            var self = this;

            if (self.listeBoutonsDisponibles.length > 0) {
                // Une ligne est sélectionnée :
                if (rowId > 0) {
                    $('#del_gridGroupesUtilisateur').switchClass('ui-state-disabled', 'ui-state-enabled');


                } else {
                    // Aucune ligne sélectionnée : on désactive les boutons
                    for (var idBtn = 0; idBtn < self.listeBoutonsDisponibles.length; idBtn++) {
                        $('#'+self.listeBoutonsDisponibles[idBtn]).switchClass('ui-state-enabled', 'ui-state-disabled');
                    }
                }
            }
        },

        /**
         * Rétablie une ligne auparavant supprimée
         */

        retablirLigne : function(rowId) {
            var self = this;

            $("#gridGroupesUtilisateur #" + rowId + " td").removeClass()
                                  .css('text-decoration', '');

            // On supprime l'attribut 'supprime' et on montre
            $("#gridGroupesUtilisateur #" + rowId + " td input").removeAttr('supprime').show();

            // Mise en forme
            self.montreCacheBoutonSuppRetablir(rowId);
        },

        montreCacheBoutonSuppRetablir : function(rowId) {

            // Est ce que la ligne est orange ? Auquel cas on débloque le bouton 'Rétablir" (ou on le bloque)
            if($('#gridGroupesUtilisateur #'+rowId).children().eq(1).hasClass('cellWarning')) {
                $('#edit_gridGroupesUtilisateur').switchClass('ui-state-disabled', 'ui-state-enabled');
                $('#del_gridGroupesUtilisateur').switchClass('ui-state-enabled', 'ui-state-disabled');
            } else {
                $('#edit_gridGroupesUtilisateur').switchClass('ui-state-enabled', 'ui-state-disabled');
                $('#del_gridGroupesUtilisateur').switchClass('ui-state-disabled', 'ui-state-enabled');
            }
        },

        initialiseLienVersGroupe : function () {
            var self = this;

            // On ajoute le lien pour aller directement vers groupes
            $('#gridGroupesUtilisateur button[id^=btnGoGp_]').button({
                icons: {
                    primary: "ui-icon-wrench"
                },
                text: false

            }).on('click', function() {

                var monCookieGroupe = {};
                var nomGroupe = $(this).attr('nom');

                var filtre = {"groupOp":"AND","rules":[{"field":"nom","op":"eq","data":""+nomGroupe+""}]};
                monCookieGroupe['filters'] = JSON.stringify(filtre);
                monCookieGroupe['from'] = true;
                monCookieGroupe['gs_nom'] = nomGroupe;

                self.setCookie('gridGroupes.filtres', monCookieGroupe);

                // On affiche la page des groupes
                window.location = '#gestion-groupes';
                return false;
            });
        },

        /**
         * Ajoute un nouveau groupe pour l'utilisateur
         */

        ajouterGroupeWindow : function() {
            var resultat = '';
            var self = this;

            // Correspond aux colonnes du grid des Groupes de lutilisateur
            var idxCol_idGroupe     = 0;
            var idxCol_nomGroupe    = 1;

            // On ouvre la fenêtre d'abord pour montrer qu'on a reçu un ordre
            var html = '<h1 id="titre"></h1><center>';
            html += '<div id="liste_groupe" style="float:left">';
            html += '<h3>Liste groupes</h3>';
            html += '<select id="select_groupes" class="select_grp" style="width: 250px;margin-right: 5px;" size="20">';
            html += '</select></div>';
            html += '<div style="clear:both"></div>';

            var popUpAjoutGroupe = new sinapsDialog('popUpAjoutGroupe');

            popUpAjoutGroupe.modal(true)
            .title("Ajout d'une nouveau groupe")
            .html(html)
            .width('auto')
            .height('auto')
            .resizable(false)
            .addButton('Ajouter', function() {
                // On ajoute la ligne à la table
                self.addGroupeDansGrid();
            })
            .addButton('Fermer', function() {
                popUpAjoutGroupe.close();
            })
            .onClose(function() {
                // on ferme et on réouvre la précédente
                popUpAjoutGroupe.remove();

            }).create().open();

            // Evenement doubleclick sur liste : ajout
            $( "#select_groupes" ).dblclick(function() {
                self.addGroupeDansGrid();
            });

            // UN peu de mise en forme
            $('#popUpAjoutElement').css('padding-top', '0px');

            self.remplitListeDeTousLesGroupes();
        },

        remplitListeDeTousLesGroupes : function() {

            RestApi.getAllGroupes( function(data) {
                if (data.success) {
                    // Traitement du retour
                    for(var idx in data.payload){
                        $('#select_groupes').append(
                                        new Option(
                                            data.payload[idx].nom,
                                            data.payload[idx].id)
                                        );
                    }

                } else {

                }
            });
        },

        supprimeLigne : function(rowId) {
            var self = this;

            // Cas d'une ligne que l'on vient d'ajouter nous
            if($("#gview_gridGroupesUtilisateur #" + rowId + " td.cellVertActive").length > 0) {
                $("#gridGroupesUtilisateur").jqGrid('delRowData',rowId);
                return;
            }

            $("#gview_gridGroupesUtilisateur #" + rowId + " td")
                                                .addClass('cellWarning')
                                                .css('text-decoration', 'line-through');
            // et on met à jour les boutons
            self.montreCacheBoutonSuppRetablir(rowId);
        },

        addGroupeDansGrid : function() {

            var self = this;
            if($('#select_groupes option:selected').text() ) {
                // On teste si la ligne n'existe pas déjà, auquel cas on ne l'ajoute pas (Ne retourne que les lignes égales)
                var result = $.grep($("#gridGroupesUtilisateur").jqGrid('getGridParam', 'userData'),
                    function(e){
                        if (e.cell[0] == $('#select_groupe option:selected').val() ) {
                            return e;
                        }

                    });


                if(result.length === 0) {

                    var idGroupe    = $('#select_groupes option:selected').val();
                    var nomGroupe   = $('#select_groupes option:selected').text();

                    var rowID = "idGroupeAAjouter_"+ idGroupe;

                    if( $("#" + rowID).length === 0) {
                        var parameters =
                        {
                            rowID: rowID,
                            initdata: {
                                idGroupe : idGroupe,
                                nomGroupe:  nomGroupe
                            },
                            useDefValues: false,
                            useFormatter: false,
                            addRowParams: { extraparam: {} }
                        };

                        // On l'ajoute derrière le dernier
                        $("#gridGroupesUtilisateur").jqGrid('addRowData', rowID, {
                                idGroupe : idGroupe,
                                nomGroupe:  nomGroupe
                            },
                            'last');

                        $("#" + rowID + " td").addClass('cellVertActive');

                    }
                }
            }
        },

        enregistrerUtilisateur : function() {
            var self = this;
            var idUtilisateur = self.idUtilisateur;
            var titleDialogDefaut = "Enregistrement de l'utilisateur";

            $('#popUpAjoutGroupe').parent().remove();

            if(self.verifieSaisies()) {
                /** Tout est Ok on appelle le controllers pour mettre à jour en BDD
                 */

                // Récupération de tous les champs
                var input_login = $('#input_LOGIN').val();
                var input_nom = $('#input_NOM').val();
                var input_prenom = $('#input_PRENOM').val();
                var input_email = $('#input_EMAIL').val();
                var input_pwd = $('#input_PWD1').val();
             
                var dataGridGroupes = $('#gridGroupesUtilisateur').jqGrid('getRowData');

                // On ne prend que ceux qui n'ont pas la classe "cellWarning"
                var listeGroupes = $.map( dataGridGroupes, function( obj ) {
                    var idGroupe = obj.idGroupe;

                    if (! $("#gview_gridGroupesUtilisateur #" + idGroupe + " td")
                                                .hasClass('cellWarning')) {
                        return ( idGroupe);
                    }
                });

                // on ouvre le dialog d'attente
                var html = "Utilisateur en cours d'enregistrement...<br/><br/>";
                html += '<div id="resultat" style="overflow-x:auto;height:auto;" >';
                html += '<center><img src="../commun/images/ajax-wait/ajax-loader16x16.gif"/></center>';
                html += '</div>';
                var OkDialog = new sinapsDialog('OkDialog',
                                                                    html,
                                                                    titleDialogDefaut, true);

                OkDialog.create().open().position({
                   my: "center",
                   at: "center",
                   of: window
                });

                // On supprime la croix, comme cela aucune manière de ferme pendant la mise à jour
                $('[aria-describedby=OkDialog] .ui-button').remove();

                var gestionHabilitations = '1';
                if ($('#chk_Habilitations_Oui:checked').val() === undefined) {
                    gestionHabilitations = '0';
                }
                var gestionEOM = '1';
                if ($('#chk_EOM_Oui:checked').val() === undefined) {
                    gestionEOM = '0';
                }

                RestApi.enregistrerUtilisateur(idUtilisateur,
                                                        input_login,
                                                        input_nom, input_prenom,
                                                        input_email, input_pwd,
                                                        gestionHabilitations,
                                                        gestionEOM,
                                                        listeGroupes,
                                                        function(data) {
                    if (! data.success) {

                        OkDialog.close();
                        var htmlError = self.getDivErreur('Erreur(s) de saisie : <br /><br />'+data.payload);

                        OkDialog = new sinapsDialog('OkDialog',
                                                htmlError,
                                                titleDialogDefaut, true);

                        OkDialog.addButton('Fermer', function() {
                            OkDialog.close();
                            //self.reloadGrid();
                        }).create().open().position({
                           my: "center",
                           at: "center",
                           of: window
                        });

                        return false;
                    } else {
                        // Enregistrement réussi
                        OkDialog.close();
                        var messageSucces = 'Enregistrement effectué avec succès';

                        if(data.payload.mailErrorMsg !== '') {
                            // ERREUR lors de l'envoi du mail
                            var errorMail = self.getDivWarning('Erreur(s) lors de l\'envoi du courriel : '
                                                                                + data.payload.mailErrorMsg);
                            messageSucces += '<br/>'+errorMail;
                        } else if (data.payload.mailEnvoye) {
                            messageSucces += '<br />Notification transmise à l\'utilisateur.';
                        }
                        var html = '<p>'+messageSucces+'</p>';
                        OkDialog = new sinapsDialog('OkDialog',
                                                html,
                                                titleDialogDefaut, true);

                        OkDialog.addButton('Fermer', function() {
                            OkDialog.close();
                            // Supprime la boite
                            self.mainWindow.close();
                            // Recharge le grid
                            $('#gridUtilisateurs').trigger('reloadGrid');
                        }).create().open().position({
                           my: "center",
                           at: "center",
                           of: window
                        });
                    }
                });
            }
        },

		// Veille à ce que l'email soit un email valide
        checkIfMailDgfip : function (value) {

            var mailRegex = /^[-a-z0-9~!$%^&*_=+}{\'?]+(\.[-a-z0-9~!$%^&*_=+}{\'?]+)*@[-a-z0-9~!$%^&*_=+}{\'?]+(\.[-a-z0-9~!$%^&*_=+}{\'?]+)*$/i;
            var resultat = value.match(mailRegex);

            if(value !== "") {
                if(resultat) {
                    return true;
                } else {
                    return false;
                }
            }
            return true;
        },

        /**
         * Vérifie les saisies effectuées si on demande à les enregistrer :
         *  - Champs obligatoires :
         *      * NOM
         *      * PRENOM
         *      * COURRIEL
         *      * LOGIN
         *      * PWD1
         *      * PWD2
         *      * count(listeGroupe) > 0
         *  - Contraintes de saisie
         *      * PWD1 = PWD2
         * Les vérifications d'unicité, et d'intégrité sont effectuées coté serveur
         */
        verifieSaisies: function() {
            var self = this;

            var msg = "";

            // Transformation des champs
            $('#input_NOM').focus();
            $('#input_PRENOM').focus().blur();

            // Champs non VIDES :

            if($('#input_LOGIN').val() === '') {
                msg += "Champ Login obligatoire. <br />";
            }
            if($('#input_NOM').val() === '') {
                msg += "Champ Nom obligatoire. <br />";
            }
            if($('#input_PRENOM').val() === '') {
                msg += "Champ Prénom obligatoire. <br />";
            }
            if($('#input_EMAIL').val() === '') {
                msg += "Champ Courriel obligatoire. <br />";
            } else {
				if(!self.checkIfMailDgfip($('#input_EMAIL').val())) {
					msg +=	"Le champ 'Adresse courriel' n'est pas valide. <br />";
				}
			}
            var input_PWD1 = $('#input_PWD1').val();
            var input_PWD2 = $('#input_PWD2').val();
            if(input_PWD1 === '') {
                msg += "Champ Mot de passe obligatoire. <br />";
            }
            if(input_PWD2 === '') {
                msg += "Champ Confirmation Mot de passe obligatoire. <br />";
            }
            if(input_PWD2 !== input_PWD1) {
                msg += "confirmation du mot de passe invalide <br />";
            }

            // Groupes : au moins un groupe
            var listeGroupes = $('#gridGroupesUtilisateur').jqGrid('getRowData');
            if(listeGroupes.length < 1) {
                msg += "Au moins un groupe doit être associé à l'utilisateur.<br />";
            }

            if( msg !== "" ) {
                var htmlError = self.getDivErreur('Erreur(s) de saisie : <br /><br />'+msg);
                ErrorMessageBox(htmlError);
                return false;
            }

            return true;
        },
        getDivWarning: function(message) {
            var blocDivErreur =
                    '   <div class="ui-state-highlight ui-corner-all" style="padding: 5px">' +
                    '        <p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span><span id="errorContent"></span>'+
                    message +
                    '       </p>' +
                    '</div>';
            return blocDivErreur;
        },

        getDivErreur: function(message) {
            var blocDivErreur =
                    '   <div class="ui-state-error ui-corner-all" style="padding: 5px">' +
                    '        <p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span><span id="errorContent"></span>'+
                    message +
                    '       </p>' +
                    '</div>';
            return blocDivErreur;
        }

    });


    return Clazz;
};
