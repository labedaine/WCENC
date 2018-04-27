

var GestionUtilisateursViewClass = function(args) {

    var Clazz = $.extend({}, ViewClass, {

        template : 'modules/gestion-utilisateurs/tmpl/gestionUtilisateursView.html?rd='+application.getUniqueId(),
        moduleName : 'gestion-utilisateurs',
        nomDuGrid : 'gridUtilisateurs',
        deps : [
                '/apps/restitution/modules/gestion-utilisateurs/gestionGroupesUtilisateursView.js'
        ],

        events : {},
//        timers : {},

        /**
         * Indexs de colonnes de l'objet DTO
         * utilisés pour les rowObject des formatters
         */
        idxCol_id : 0,
        idxCol_nom : 1,
        idxCol_prenom : 2,
        idxCol_login : 3,
        idxCol_email : 4,
        idxCol_groupes : 5,
        idxCol_role: 6,
        //idxCol_accesZ3 : 5,
        //idxCol_adminPSN : 5,
        //idxCol_ecranAccueil : 7,

        myCookie : null,
        listeBoutonsDisponibles: [],
        scrollPosition: 0,
        utilisateurData: null,
        nomFichier: "",
        okReinitPass : false,

        /**
         * initialisation
         */
        initialize : function() {
            var self = this;
            // Intégration des éléments communs
            jQuery.extend(this, classJqGridUtils);

            this.tooltips = {};
            this.parametrageVue();
        },

        renderView : function() {
            this.creerLaGrille();
        },

        /**
         * création de la JQGrid
         */
        creerLaGrille : function() {
            var self = this;
            // Creation de la grille
            jQuery('#'+self.nomDuGrid).jqGrid({
                jsonReader : { // Initialise le jsonReader voir doc jqGrid
                    root: "rows",
                    page: "page",
                    total: "total",
                    records: "records",
                    repeatitems: true,
                    cell: "cell",
                    id: "id",
                    userdata: "rows",
                    subgrid: {
                        root:"rows",
                        repeatitems: true,
                        cell:"cell"
                    }
                },
                pager: '#pager_' + self.nomDuGrid,
                caption: self.paramsUtilisateurs.gridTitle,
                url:'/apps/restitution/services/utilisateur/utilisateursListe',
//                editurl:'/apps/restitution/services/utilisateur/gestionUtilisateur',
                datatype: "json",
                postData:   {  filters : self.createFilters(self.nomDuGrid)   },
                colNames:['id', 'Nom', 'Prénom', 'Login', 'Adresse courriel', 'Groupes', 'Rôle' ],
                colModel:[
                    {name:'id',index:'id', width:55, hidden:true, key:true },
                    {name:'nom', index:'nom', title:false, width:80,  align:"left", editrules:{ required:true}, editable:true },
                    {name:'prenom', index:'prenom', title:false, width:60,  align:"left", sortable:true, editrules:{ required:true}, editable:true },
                    {name:'login', index:'login', title:false, width:80,  align:"left", sortable:true, editrules:{ required:true}, editable:true },
                    {name:'email', index:'email', title:false, width:130,  align:"left", sortable:true, editrules:{ required:true, email:true}, editable:true },
                    {name:'groupes',index:'groupes', title:false,edittype:'select', width:80, align:"left", sortable:true },
                    //{name:'accesZ3',index:'accesZ3', width:100, align:"center", sortable:false, formatter: self.formatAccesZ3, searchoptions: {sopt: ['eq'], value: ":Tous;0:non;1:oui"}, stype: 'select' },
                    {name:'role',index:'role', title:false, width:80, align:"left", sortable:true}
//                    {name:'role',index:'role', title:false, width:100, align:"center", sortable:false, formatter: self.formatRole, searchoptions: {sopt: ['eq'], value: ":Tous;1:Administrateur PSN;2:EOM"}, stype: 'select' },
                    //{name:'ecran',index:'ecran', width:100, align:"left", sortable:false },
                ],

                search : self.doitOnChercher(self.nomDuGrid + '.filtres'),
//                multiselect: true,
                height:  ConfigJqGrid.gridHeight, // Hauteur du jqGrid
                width: 950,
                rowNum: ConfigJqGrid.rowNum,
                rowList: ConfigJqGrid.rowList,
                multiSort: false,
                sortname: 'nom',
                sortorder: 'asc',
                viewrecords: true,
                viewsortcols: [true, 'vertical', true],

                beforeRequest: function() {
                },

                loadBeforeSend: function(xhr,settings) {

                },

                beforeProcessing: function(data, status, xhr) {
                    /**
                     * Invoque les fonctions:
                     *  - miseAJourDuContenuDesFiltresAvecCookie
                     *  - miseEnFormeToolbar
                     */
                    self.miseEnFormeBeforeProcessing(self.paramsUtilisateurs.nom_jqGrid);
                },

                loadComplete: function(data) {
					application.resizePage();
                    var grid = jQuery('#' + self.paramsUtilisateurs.nom_jqGrid);
                    // Gestion des cases à cocher
                    if (typeof(this.ojqGridMS) === "undefined") {
                        this.ojqGridMS = new jqGridMultiSelect(this.id, {deselectionOnRefresh: false});
                    }

                    self.initToolTips();
                    // Restaure la sélection si nécessaire
                    if (self.activeSelection !== undefined) {
                        grid.jqGrid('setSelection', self.activeSelection, false);
                        // On débloque
                        self.blocageBoutons(self.activeSelection);
                    }
                    // on stocke les données brutes
                    self.utilisateursData = data.rows;
                    /** **********************
                     *       MISE EN PAGE    *
                     *************************/

                    // Blocage des boutons
                    self.blocageBoutons(0);

                    /**
                     * Invoque les fonctions:
                     *  - cacherLignesGroupement
                     */
                    self.miseEnFormeLoadComplete();
                    self.miseEnFormeGridUtiliseNavGrid(self.paramsUtilisateurs.nom_jqGrid);

                    // On replace le scroll comme il était
                    grid.closest(".ui-jqgrid-bdiv").scrollTop(self.scrollPosition);

                    // Masque la colonne de droite contenant les icônes du subGrid et remontre les opérateurs
                    $('#'+this.id).jqGrid("hideCol", "subgrid");
                    // La commande précédente masque les opérateurs
                    // -> on reforce la mise en forme de la toolbar
                    self.miseEnFormeToolbar(this.id);

                    self.cacheTooltipDansLeCoin();

                    unsetWaitForm();
                },

                onSelectRow: function(id) {
                    self.scrollPosition = jQuery("#" + self.paramsUtilisateurs.nom_jqGrid).closest(".ui-jqgrid-bdiv").scrollTop();
                    /**
                     * Mémorise l'id sélectionné
                     */
                    self.activeSelection = id;
                    // On bloque
                    self.blocageBoutons(0);
                },
                beforeShowForm : function(formid) {
                // onInitializeForm : function(formid) {
                }
            });

            jQuery("#gridUtilisateurs").jqGrid(
                'navGrid',
                '#pager_gridUtilisateurs',
                {
                    add: true, // fonction "Ajouter" désactivée
                    addtext:"Ajouter",
                    addtitle:"Ajouter un utilisateur",
                    addfunc: function(){
                        self.afficherEcranEdition(0);
                    },
                    edit: true, // fonction "Modifier" désactivée
                    edittext:"Modifier",
                    edittitle:"Modifier un utilisateur",
                    editfunc: function(idUtilisateur){
                        self.afficherEcranEdition(idUtilisateur);
                    },
                    del: true, // fonction "Supprimer" désactivée
                    deltext:"Supprimer",
                    deltitle:"Supprimer un utilisateur",
                    delfunc: function(idUtilisateur){
                        var dial = new sinapsDialog('confirmationImport');
                        dial.modal(true).title("Confirmation de suppression")
                        .html("Voulez-vous supprimer l'utilisateur sélectionné ?")
                        .addButton('Confirmer', function() {

                            // On appelle la fonction de suppression
                            dial.close();
                            self.supprimerUtilisateur(idUtilisateur);

                        }).addButton('Annuler', function() {
                            // Ferme la boite si clique sur annuler
                            dial.close();
                        })
                        .onClose(function() {
                            dial.remove();
                        }).create().open();

                    },
                    search:false,
                    beforeRefresh: function() {
                        self.scrollPosition = $("#" + self.paramsUtilisateurs.nom_jqGrid).closest(".ui-jqgrid-bdiv").scrollTop();
                        $("#" + self.paramsUtilisateurs.nom_jqGrid)[0].triggerToolbar();
                    }
                },
                {
                    editCaption: "Modification de l'utilisateur",
                    closeOnEscape:true,
                    closeAfterEdit:true,
                    reloadAfterSubmit:true,
                    beforeShowForm : function(formid) {},
                    afterShowForm: function() {},
                    onInitializeForm : function(formid) {}
                }, // edit options
                {
                    addCaption: "Création d'un nouvel utilisateur",
                    closeOnEscape:true,
                    closeAfterAdd:true,
                    reloadAfterSubmit:true,
                    // Blocage des zones
                    afterShowForm: function() {
                        $('.navButton').hide();
                        $( "#login" ).prop( "disabled", false );
                    },
                    onInitializeForm : function(formid)
                    {
                        
                    }
                }, // add options
                {
                    caption: "Suppression d'un utilisateur",
                    msg: "Supprimer l'utilisateur sélectionné ?",
                    delicon: [true, "left", "ui-icon-trash"],
                    cancelicon: [true, "left", "ui-icon-close"],
                    closeOnEscape:true,
                    closeAfterDelete: true,
                    reloadAfterSubmit:true
                }  // del options
            );

            jQuery("#gridUtilisateurs").jqGrid(
                'filterToolbar',
                {
                    autosearch: true,
                    recreateFilter: true,
                    stringResult: true,
                    searchOnEnter : true,
                    groupOp: 'AND',
                    defaultSearch: 'cn',
                    ignoreCase: true,
                    searchOperators: true
                }
            );


            var grid = jQuery("#" + this.paramsUtilisateurs.nom_jqGrid);


            /** --------------------------------------------------------------------------
             * Affichage des boutons de l'écran
             * La liste des boutons visibles est fournie
             * par le paramètre paramsGroupes.boutonsVisibles
             -------------------------------------------------------------------------- */

            // Ce tableau se remplit au fur et à mesure de l'ajout des bouton à l'écran
            self.listeBoutonsDisponibles = ['del_gridUtilisateurs', 'edit_gridUtilisateurs'];

            /**
             * AJOUT BOUTON "Réinitialiser le mot de passe"
             */
            if (this.paramsUtilisateurs.boutonsVisibles.indexOf('btn_reinitPass') >= 0) {
                grid.navButtonAdd('#' + this.paramsUtilisateurs.nom_jqGridPager,{
                    // Ajout du bouton Reinitialiser
                    id: 'btn_reinitPass',
                    caption: "Réinitialiser le mot de passe",
                    title: "Réinitialiser le mot de passe",
                    buttonicon: "ui-icon-key",
                    onClickButton: function() {
                        var listeIdsSel = this.ojqGridMS.getElementsSelectionnes(false);
                        if (listeIdsSel.length === 0) {
                            // Pas de ligne séléctioné : Erreur
                            ErrorMessageBox("Aucune ligne sélectionnée", "Erreur");
                            return false;
                        }
                        if (listeIdsSel.length > 1) {
                            // + de 1 ligne séléctioné : Erreur
                            ErrorMessageBox("Il faut sélectionner une seule ligne.", "Erreur");
                            return false;
                        }
                        // Creation d'une boite de dialogue sinapsDialog modal
                        if (listeIdsSel.length === 1) {
                            // Si une ligne est selectionnee
                            var idSel = grid.getGridParam('selrow');
                            var donneesLigne = self.renvoieDonneesLigneCourante(idSel);

                            var nom = donneesLigne.cell[self.idxCol_prenom]+" "+donneesLigne.cell[self.idxCol_nom];

                            var dial = new sinapsDialog();
                            dial.modal(true).title("Réinitialisation du mot de passe")
                            .html("Voulez-vous réinitialiser le mot de passe de : <b>"+nom+"</b> ?")
                            .addButton('Réinitialiser le mot de passe', function(){
                                // On appelle la fonction d'import
                                dial.close();
                                self.reinitialiserUtilisateur(idSel, nom, false);

                            }).addButton('Annuler', function(){
                                // Ferme la boite si clique sur annuler
                                dial.close();
                                dial.remove();
                            }).beforeClose(function() {dial.remove();}).create().open();
                        } else {
                            ErrorMessageBox("Vous devez sélectionner une ligne", "Erreur");
                        }
                    },
                    position:"last"
                });
                // Ajoute l'id du bouton à la liste des boutons disponibles
                self.listeBoutonsDisponibles.push('btn_reinitPass');
            }

            /**
             * affichage zone de saisie avant bouton "Parcourir"
             */
            var codeHTML = '';
            codeHTML += '<td id="usersFile"><input id="input_usersFile" type="text" value=""readonly style="display:none;width: auto;" size="40"></td>';
            $("#btn_reinitPass").after(codeHTML);

        },

        /**
         * Fonction qui permet la réinitialisation d'un utilisateur
         */

        reinitialiserUtilisateur : function(idSel, nom, debug) {
            var self = this;

            var msg = "";
            var img = "";
            var error = "";

            // On récupère la commande
            RestApi.reinitPass(idSel, true, false, function(data) {

                // On ouvre la fenêtre d'abord pour montrer qu'on a reçu un ordre
                var msg = '<h1 id="titre"></h1>';
                msg += "<b>La commande suivante a été exécutée:</b><br/>";
                msg += $('<div/>').text(data.payload.cmd).html() + "<br/><hr/>";
                msg += "<b>Le résultat suivant a été obtenu:</b><br/><br/>";
                msg += '<div id="resultat" style="overflow-x:auto;height:300px;" >';
                msg += '<center><img src="../commun/images/ajax-wait/ajax-loader16x16.gif"/></center>';
                msg += '</div>';
                msg += '<div id="divErrorCollecte" style="display:none">';
                msg += '<div class="ui-widget ui-widget-error" style="margin-top:5px;width:auto">';
                msg += '<div class="ui-state-error ui-corner-all" style="padding: 5px">';
                msg += '<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span>';
                msg += '<span id="errorContentCollecte"></span></p>';
                msg += '</div>';
                msg += '</div>';
                msg += '</div>';
                var dial2 = new sinapsDialog('resultatReinitialisation');

                dial2.modal(true)
                .title("Réinitialisation du mot de passe et déploiement de l'utilisateur: "+nom)
                .html(msg)
                .height(500)
                .width(600)
                .addButton('Relancer en mode debug', function() {
                    dial2.close();
                    self.reinitialiserUtilisateur(idSel, nom, true);
                })
                .addButton('Fermer', function() {

                    dial2.close();
                    self.reloadGrid();
                })
                .onClose(function() {dial2.remove();}).create().open();

                if (data.success) {
                    $('#titre').html("Réinitialisation en cours de traitement");
                    $('#resultatReinitialisation').css('overflow', 'hidden');

                    // On demande cette fois le vrai import
                    RestApi.reinitPass(idSel, false, debug, function(data) {
                        if (data.success) {
                            $('#titre').html("Réinitialisation réussie.");

                            // On supprime le bouton de debug
                            $('#resultatReinitialisation').parent().find('.ui-dialog-buttonset').children().eq(0).remove();

                        } else {
                            $('#titre').html("Réinitialisation échoué.");
                        }
                        $('#resultat').html(data.payload);
                    });

                } else {
                    $('#titre').html("Réinitialisation échoué.");
                }
                $("#" + self.paramsUtilisateurs.nom_jqGrid).jqGrid('resetSelection');
            });
        },


        /**
         * Fonction de blocage de boutons
         * Appelé dans :
         *      - Grid onSelectRow
         *      - Grid onSelectAll
         */
        blocageBoutons : function(idUser){
            var self = this;
            var grid = $("#" + this.paramsUtilisateurs.nom_jqGrid);

            /**
             * Récupération du nombre d'éléments sélectionnés :
             * ajout toujours actif
             *  - si 1 : modif et suppr
             *
             */
            $('#add_gridUtilisateurs').switchClass('ui-state-disabled', 'ui-state-enabled');
            $('#edit_gridUtilisateurs').switchClass('ui-state-enabled', 'ui-state-disabled');
            $('#del_gridUtilisateurs').switchClass('ui-state-enabled', 'ui-state-disabled');

            var selection = grid.jqGrid('getGridParam','selrow');
            if (selection !== null) {
                $('#edit_gridUtilisateurs').switchClass('ui-state-disabled', 'ui-state-enabled');
                $('#del_gridUtilisateurs').switchClass('ui-state-disabled', 'ui-state-enabled');
            }
        },


        /**
         * Fonction d'initialisation des tooltips
         */
        initToolTips : function(){
            $("#" + this.paramsUtilisateurs.nom_jqGrid +", #pager_gridUtilisateurs").tooltip({
                content: function () {
                    return $(this).prop('title');
                },
                track:true
            });
            this.cacheTooltipDansLeCoin();
        },


        /**
         * Fonction qui renvoie les informations relatives à l'id sélectionné
         */
        renvoieDonneesLigneCourante : function(idUtilisateur){
            var listeUtilisateurs = this.utilisateursData;
            for (var idxUtilisateurs = 0; idxUtilisateurs < listeUtilisateurs.length; idxUtilisateurs++) {
                if (listeUtilisateurs[idxUtilisateurs].id === idUtilisateur) {
                    return listeUtilisateurs[idxUtilisateurs];
                }
            }
        },

        /**
         * Affiche l'écran d'édition d'un utilisateur : ajout ou modification
         * (classe GestionUtilisateurEditionView)
         * => idUtil
         * @param {type} idUtilisateur
         * @returns {undefined}
         */
        afficherEcranEdition: function(idUtilisateur) {
            // Affichage de la vue
            var self = this;
            this.utilisateurEditionView = new GestionUtilisateurEditionView(args, idUtilisateur);
            this.utilisateurEditionView.render('#div-utilisateur-edition');

        },

        /**
         * Fonction de suppression d'un utilisateur
         *
         * @param {type} idUtilisateur
         * @returns {undefined}
         */
        supprimerUtilisateur: function (idUtilisateur) {
            var self = this;

            RestApi.supprimerUtilisateur(idUtilisateur,
                                        function(data) {
                if (data.success) {
                    self.reloadGrid();
                } else {
                    ErrorMessageBox(data.payload);
                }
            });

        },

        /**
         * Fonction de rechargement de la grille
         */
        reloadGrid : function(){
            var self = this;

            self.scrollPosition = jQuery("#" + this.paramsUtilisateurs.nom_jqGrid).closest(".ui-jqgrid-bdiv").scrollTop();
            this.updateModuleContent();
        },


        /**
         * Callback
         */
        updateModuleContent : function(){
            jQuery("#" + this.paramsUtilisateurs.nom_jqGrid).trigger("reloadGrid");
        },


        parametrageVue : function() {
           /**-----------------------------------------------------------------------------*
            *
            *    Paramètrage spécifique à chaque écran :
            *      - nom du grid, du pager, titre du grid
            *      - url de récupération des données
            *      - liste des colonnes à masquer pour chaque vue
            *      - liste des boutons visibles
            *
            * -----------------------------------------------------------------------------*/
            var paramsUtilisateurs = {}; // Variable transmise à la classe
            paramsUtilisateurs.module = module; // Nom du module appelant

            paramsUtilisateurs.gridTitle = 'Gestion des utilisateurs';
            paramsUtilisateurs.gridHeight = ConfigJqGrid.gridHeight;
            paramsUtilisateurs.nom_jqGrid = 'gridUtilisateurs';

            paramsUtilisateurs.boutonsVisibles = [
                                                    //'btn_reinitPass',
                                                    'btn_assigner',
                                                    'add_gridUtilisateurs',
                                                    'edit_gridUtilisateurs',
                                                    'del_gridUtilisateurs'
                                                ];

            paramsUtilisateurs.nom_jqGridPager = 'pager_' + paramsUtilisateurs.nom_jqGrid;

            this.paramsUtilisateurs = paramsUtilisateurs;

            this.nomDuGrid = this.paramsUtilisateurs.nom_jqGrid;
        }

    });
    return Clazz;
};
