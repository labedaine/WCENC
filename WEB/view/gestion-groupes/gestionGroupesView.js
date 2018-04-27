var GestionGroupesViewClass = function(args) {

    var Clazz = $.extend({}, ViewClass, {

        template : 'modules/gestion-groupes/tmpl/gestionGroupesView.html?rd='+application.getUniqueId(),
        moduleName : 'gestion-groupes',
        nomDuGrid : 'gridGroupes',

        events : {},
//        timers : {},

        /**
         * Indexes de colonnes de l'objet DTO
         * utilisés pour les rowObject des formatters
         */
        idxCol_id : 0,
        idxCol_nom : 1,
        idxCol_groupeMail : 2,
        idxCol_groupeTelephone : 3,
        idxCol_groupeDescription : 4,
        idxCol_nomSMA : 5,
        idxCol_nbApplications : 6,
        idxCol_nbUtilisateurs : 7,
        idxCol_htmlUtilisateurs : 8,
        idxCol_htmlApplications : 9,

        myCookie : null,
        listeBoutonsDisponibles: [],
        scrollPosition: 0,
        groupesData : null,


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


        /**
         * rendu de la vue
         */
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
                scrollrows : true,
                pager: '#pager_' + self.nomDuGrid,
                caption: self.paramsGroupes.gridTitle,
                url: '/apps/restitution/services/groupe/groupesListe',
                editurl: '/apps/restitution/services/groupe/gestionGroupes',
                datatype: "json",
                postData:   {  filters : self.createFilters(self.paramsGroupes.nom_jqGrid)   },
                colNames:['id', 'Groupe', 'Adresse courriel', 'Téléphone', 'Description', 'Correspondance SMA', 'Nb Appli.', 'Nb Util.' ],
                colModel:[
                    {name:'id', index:'id', width:55, hidden:true, key:true },
                    {name:'nom', index:'nom', width:100, align:"left", title:false, editrules:{ required:true, custom:true, custom_func: self.checkIfGroupeOk}, editable:true, editoptions: { dataInit: self.setInputWidth, onkeyup: '$(this).val($(this).val().toUpperCase());'}  },
                    {name:'groupeMail', index:'groupeMail', width:150, title:false,editable:true, sortable:false, hidden:false, editrules : { required:true, custom:true, custom_func: self.checkIfMailDgfip}, editoptions: { dataInit: self.setInputWidth} },
                    {name:'groupeTelephone', index:'groupeTelephone', title:false,width:70, editable:true, sortable:true,  align:"center", hidden:false, editrules : { required:true, custom:true, custom_func: self.checkIfNumber}, editoptions: { dataInit: self.setInputWidth} },
                    {name:'groupeDescription', index:'groupeDescription', title:false,width:150, editable:true, sortable:true,  align:"left", hidden:false, editrules : { required:true, edittype: "text" }, editoptions: { dataInit: self.setInputWidth} },
                    {name:'nomSMA', index:'nomSMA', title:false,width:150, editable:true, sortable:true,  align:"left", hidden:false, editrules : { required:false, edittype: "text" }, editoptions: { dataInit: self.setInputWidth} },
                    {name:'nbApplis', index:'nbApplis', width:45, title:true, editable:false, sortable:true,  align:"center", hidden:false, cellattr : self.formatCellnbApplication },
                    {name:'nbUsers', index:'nbUsers', width:45, title:true, editable:false, sortable:true,  align:"center", hidden:false, cellattr : self.formatCellnbUtilisateur }
                ],
                search : self.doitOnChercher(self.paramsGroupes.nom_jqGrid + '.filtres'),
                multiselect: false,
                height:  ConfigJqGrid.gridHeight, // Hauteur du jqGrid
                width: 950,
                rowNum: ConfigJqGrid.rowNum,
                rowList: ConfigJqGrid.rowList,
                sortname: 'nom',
                viewrecords: true,
                sortorder: "asc",
                viewsortcols: [true, 'vertical', true],

                beforeRequest: function() {
                    // Par défaut on bloque tout
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
                    self.miseEnFormeBeforeProcessing(self.paramsGroupes.nom_jqGrid);
                },

                loadComplete: function(data) {

                    application.resizePage();
                    var grid = jQuery('#' + self.paramsGroupes.nom_jqGrid);


                    // Restaure la sélection si nécessaire
                    if (self.activeSelection !== undefined) {
                        grid.jqGrid('setSelection', self.activeSelection, false);
                        // On débloque
                        self.blocageBoutons(self.activeSelection);
                    }
                    // on stocke les données brutes
                    self.groupesData = data.rows;
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

                    // Masque la colonne de droite contenant les icônes du subGrid et remontre les opérateurs
                    $('#'+this.id).jqGrid("hideCol", "subgrid");
                    // La commande précédente masque les opérateurs
                    // -> on reforce la mise en forme de la toolbar
                    self.miseEnFormeToolbar(this.id);

                    self.cacheTooltipDansLeCoin();

                    unsetWaitForm();
                    self.initToolTips();
                },

                onSelectRow: function(id) {
                    self.scrollPosition = jQuery("#" + self.paramsGroupes.nom_jqGrid).closest(".ui-jqgrid-bdiv").scrollTop();
                    /**
                     * Mémorise l'id sélectionné
                     */
                    self.activeSelection = id;

                    // Bug 122497: on supprime la sélection active si on ferme les infos complémentaires
                    if ($('.ui-subgrid').length === 0) {
                        self.blocageBoutons(id);
                    } else {
                        // On débloque
                        self.blocageBoutons(0);
                    }
                }
            });

            jQuery("#gridGroupes").jqGrid(
                'navGrid',
                '#pager_gridGroupes',
                {
                    edit: true,
                    edittext:"Modifier",
                    edittitle:"Modifier le groupe sélectionné",

                    add: true,
                    addtext:"Ajouter",
                    addtitle:"Ajouter un groupe",

                    del: true,
                    deltext:"Supprimer",
                    deltitle:"Supprimer le groupe sélectionné",
                    delfunc: function(id){
                        self.avertirAvantSuppression(id, false);
                    },

                    search:false,
                    beforeRefresh: function() {
                        self.scrollPosition = $("#" + self.paramsGroupes.nom_jqGrid).closest(".ui-jqgrid-bdiv").scrollTop();
                        $("#" + self.paramsGroupes.nom_jqGrid)[0].triggerToolbar();
                    }
                },
                {
                    editCaption: "Modification du groupe",
                    closeOnEscape:true,
                    closeAfterEdit:true,
                    reloadAfterSubmit:true,
                    afterShowForm: function(form) {
                        $('#editmodgridGroupes').position({
                           my: "center",
                           at: "center",
                           of: window
                        }).width(450);
                        $('#nData, #pData').hide();
                        $('#nom').val($('#nom').val().toUpperCase());
                    },
                    afterSubmit : function(response, postdata) {
                        var ret = $.parseJSON(response.responseText);
                        return [ret.success,ret.payload];
                    }
                }, // edit options
                {
                    addCaption: "Création d'un nouveau groupe",
                    closeOnEscape:true,
                    closeAfterAdd:true,
                    reloadAfterSubmit:true,
                    afterShowForm: function(form) {
                        $('#editmodgridGroupes').position({
                           my: "center",
                           at: "center",
                           of: window
                        }).width(450);
                    },
                    afterSubmit : function(response, postdata) {
                        var ret = $.parseJSON(response.responseText);
                        return [ret.success,ret.payload];
                    }
                }, // add options
                {}  // del options
            );

            var grid = jQuery("#" + this.paramsGroupes.nom_jqGrid);

            // Sert à passer le refresh à gauche dans les boutons du navgrid
            this.miseEnFormeGridUtiliseNavGrid(this.paramsGroupes.nom_jqGrid);

            /**
             * AJOUT BOUTON "Modifier"
             */
            var idJqGridPager = '#'+self.paramsGroupes.nom_jqGridPager;
            if (this.paramsGroupes.boutonsVisibles.indexOf('info_gridGroupes') >= 0) {
                grid.navButtonAdd(idJqGridPager,{
                    // Ajout du bouton Modifier
                    id: 'info_gridGroupes',
                    caption:"Afficher détails",
                    title: 'Afficher détails',
                    buttonicon:"ui-icon-contact",
                    onClickButton: function(){
                        var idSel = grid.getGridParam('selrow');
                        self.avertirAvantSuppression(idSel, true);
                    },
                    position:"last"
                });
                // Ajoute l'id du bouton à la liste des boutons disponibles
                self.listeBoutonsDisponibles.push('info_gridGroupes');
            }

            // Par défaut on bloque tout
            self.blocageBoutons(0);

            jQuery("#gridGroupes").jqGrid(
                'filterToolbar',
                {
                    autosearch: true,
                    recreateFilter: true,
                    stringResult: true,
                    searchOnEnter : true,
                    groupOp: 'AND',
                    defaultSearch: 'cn',
                    ignoreCase: true,
                    searchOperators: true,

                    // Gestion des cookie pour conservation des filtres
                    beforeSearch: function() {

                        var cookieASauver = {};
                        var nomGrid = self.paramsGroupes.nom_jqGrid;

                        // On récupère le postdata en entier pour en sauvegarde le filtre
                        var postdata = $('#' + nomGrid).jqGrid('getGridParam', 'postData');
                        if (postdata.filters) {
                            cookieASauver['filters'] = postdata.filters;
                        }

                        // Cas d'un * passé dans le filtre
                        //self.simuleMultipleCritere( nomGrid );
                        $('#gbox_'+nomGrid+' [id^=gs_]').each(function() {
                            if ($('#' + this.id).val() !== "") {
                                cookieASauver[this.id] = $('#' + this.id).val();
                            }
                        });

                        if ($.isEmptyObject(cookieASauver)) {
                            self.setCookie(nomGrid + '.filtres', null);
                        } else {
                            self.setCookie(nomGrid + '.filtres', cookieASauver);
                        }
                    }
                }
            );
        },

        /**
         * Fonction de callback de la grille pour le formatage de la colonne nb utilisateurs
         */

        formatCellnbUtilisateur : function(rowId, cellvalue, rowObject, cm, rdata ) {

            var html = rowObject[application.currentView.idxCol_htmlUtilisateurs];

            if(html === null) {
                return "";
            }
            var title= ' title= "'+ html +'" ';

            return " "+ title + " ";
        },

        /**
         * Fonction de callback de la grille pour le formatage de la colonne nb applications
         */

        formatCellnbApplication : function(rowId, cellvalue, rowObject, cm, rdata ) {
            var html = rowObject[application.currentView.idxCol_htmlApplications];

            if(html === null) {
                return "";
            }
            var title= ' title= "'+ html +'" ';

            return " "+ title + " ";
        },

        // Met une largeur pour les input dans les form d'edition
        setInputWidth : function(elem) {
            $(elem).width(200);
        },

        // Veille à ce que le numéro de tél soit un numéro de tél
        checkIfNumber : function (value, colname) {

            var intRegex = /^[0-9]{2} [0-9]{2} [0-9]{2} [0-9]{2} [0-9]{2}$/;
            var resultat = value.match(intRegex);

            if(value !== "") {
                if(resultat) {
                    return [true, ""];
                } else {
                    return [false, "Le champ 'Téléphone' n'est pas valide (Ex: 01 02 03 04 05)"];
                }
            }
            return [true, ""];
        },

        // Veille à ce que l'email soit un email valide
        checkIfMailDgfip : function (value, colname) {

            var mailRegex = /^[-a-z0-9~!$%^&*_=+}{\'?]+(\.[-a-z0-9~!$%^&*_=+}{\'?]+)*@[-a-z0-9~!$%^&*_=+}{\'?]+(\.[-a-z0-9~!$%^&*_=+}{\'?]+)*$/i;
            var resultat = value.match(mailRegex);

            if(value !== "") {
                if(resultat) {
                    return [true, ""];
                } else {
                    return [false, "Le champ 'Adresse courriel' n'est pas valide."];
                }
            }
            return [true, ""];
        },

        // Veille à ce que l'email soit un email valide
        checkIfGroupeOk : function (value, colname) {

            var groupeRegex = /^[0-9A-Z\- ]+$/i;
            var resultat = value.match(groupeRegex);

            if(value !== "") {
                if(resultat) {
                    return [true, ""];
                } else {
                    return [false, "Le champ 'Groupe' n'est pas valide (En majuscule alphanumérique, tiret et espace)."];
                }
            }
            return [true, ""];
        },

        avertirAvantSuppression : function(id, isInfo) {
            var self = this;
            var message = "";

            // verifier s'il y a des TableauDeBord pour le groupe
            RestApi.controleAvantSuppression(id, function(data) {
                if (data) {
                    if(data.success) {

                        var rubriques = data.payload;

                        var html = "";
                        var titre = "";

                        if(isInfo) {
                            html += "<div><h2>Détails du groupe '" + rubriques.nom + "':</h2>";
                            titre = "Affichage du détail";
                        } else {
                            html += "<div><h2>La suppression du groupe '" + rubriques.nom + "' entrainera la suppression:</h2>";
                            titre = "Confirmation de la suppression";
                        }

                        html += "<div id='tdbs-container'></div>";
                        html += "<div id='acces-container'></div>";
                        html += "<div id='utilisateurs-container'></div>";
                        html += "<div id='derogations-container'></div>";
                        html += "<div style='clear:both'></div>";

                        self.afficheMessage(id, "del", titre, html, isInfo);

                        // Gestion des TDB
                        $("#tdbs-container").jqcontainer({
                            draggable: false,
                            collapsable: false,
                            width: 'auto',
                            height: 'auto',
                            title: 'De tableaux de bords'
                        });
                        $('#tdbs-container').children().eq(2).css('overflow','auto').css('max-height','200px');
                        $('#tdbs-container').children().eq(2).html(rubriques.tdbs.join('<br />'));


                        // Acces
                        $("#acces-container").jqcontainer({
                            draggable: false,
                            collapsable: false,
                            width: 'auto',
                            height: 'auto',
                            title: "De l'accès aux applications"
                        });
                        $('#acces-container').children().eq(2).css('overflow','auto').css('max-height','200px');
                        var htmlAcces = "<table width='100%'>";
                        for(var i=0;i<rubriques.acces.length;i++) {
                            htmlAcces+= "<tr><td style='width:50%'>"+rubriques.acces[i]+"</td>";
                            i++;
                            if(rubriques.acces[i]===undefined) {
                                htmlAcces+= "<td style='width:50%;margin-left:10px;border-left: 1px solid #dddddd;padding-left: 10px;'></td></tr>";
                            } else {
                                htmlAcces+= "<td style='width:50%;margin-left:10px;border-left: 1px solid #dddddd;padding-left: 10px;'>"+rubriques.acces[i]+"</td></tr>";
                            }
                        }
                        $('#acces-container').children().eq(2).html(htmlAcces);

                        // Utilisateurs
                        $("#utilisateurs-container").jqcontainer({
                            draggable: false,
                            collapsable: false,
                            width: 'auto',
                            height: 'auto',
                            title: "De l'appartenance des utilisateurs"
                        });
                        $('#utilisateurs-container').children().eq(2).css('overflow','auto').css('max-height','200px');
                        var htmlUtilisateur = "<table width='100%'>";
                        for(var j = 0;j<rubriques.utilisateurs.length;j++) {
                            htmlUtilisateur+= "<tr><td style='width:50%'>"+rubriques.utilisateurs[j]+"</td>";
                            j++;
                            if(rubriques.utilisateurs[j]===undefined) {
                                htmlUtilisateur+= "<td style='width:50%;margin-left:10px;border-left: 1px solid #dddddd;padding-left: 10px;'></td></tr>";
                            } else {
                                htmlUtilisateur+= "<td style='width:50%;margin-left:10px;border-left: 1px solid #dddddd;padding-left: 10px;'>"+rubriques.utilisateurs[j]+"</td></tr>";
                            }
                        }
                        $('#utilisateurs-container').children().eq(2).html(htmlUtilisateur);


                        // Derogations
                        $("#derogations-container").jqcontainer({
                            draggable: false,
                            collapsable: false,
                            width: 'auto',
                            height: 'auto',
                            title: "Du paramétrages des alertes sur les indicateurs (destinataire Alerte)"
                        });
                        $('#derogations-container').children().eq(2).css('overflow','auto').css('max-height','200px');
                        $('#derogations-container').children().eq(2).html(rubriques.derogations.join('<br />'));

                        //$('#del [id$=-container]').css('float', 'left').css('margin-right','5px');
                        $('#del [id$=-container]').css('margin-top','5px');

                        $('#del').parent().position({
                           my: "center",
                           at: "center",
                           of: window
                        });

                    } else {
                        ErrorMessageBox(data.payload, "Erreur lors de la suppression du groupe.");
                    }
                } else {
                    ErrorMessageBox("Erreur inconnue.", "Erreur lors de la suppression du groupe.");
                }
            });
        },


        afficheMessage: function(id, oper, titre, contenu, isInfo) {
            var self = this;

            var dial = new sinapsDialog(oper);
            dial.title(titre)
                .modal(true)
                .height("auto")
                .width(600)
                .onClose(
                    function(){ dial.remove(); }
                )
                .html(contenu);

            if(!isInfo) {
                dial.addButton(
                    "Confirmer",
                    function(){
                        RestApi.supprimerGroupe(id, oper, function(data) {
                           if (data.success) {
                                self.reloadGrid();
                            } else {
                                ErrorMessageBox(data.payload, "Erreur lors de la suppression du groupe.");
                            }
                        });
                        dial.close();
                    }
                );
            }

            dial.addButton(
                "Annuler",
                function() {
                    dial.close();
                }
            )
            .create()
            .open();
        },


        /**
         * Fonction d'initialisation des tooltips
         */
        initToolTips : function(){
            $("#" + this.paramsGroupes.nom_jqGrid + " ,#pager_gridGroupes").tooltip({
                content: function () {
                    return $(this).prop('title');
                },
                track:true
            });
            this.cacheTooltipDansLeCoin();
        },

        /**
         * Fonction de blocage de boutons
         * Appelé dans :
         *      - Grid onSelectRow
         *      - Grid onSelectAll
         */

        blocageBoutons : function(idUser){
            var grid = $("#" + this.paramsGroupes.nom_jqGrid);
            var listeBoutons = this.listeBoutonsDisponibles;

            if (listeBoutons.length > 0) {

                // Une ligne est sélectionnée :
                if (idUser > 0) {
                    $('#edit_gridGroupes').switchClass('ui-state-disabled', 'ui-state-enabled');
                    $('#del_gridGroupes').switchClass('ui-state-disabled', 'ui-state-enabled');
                    $('#info_gridGroupes').switchClass('ui-state-disabled', 'ui-state-enabled');
                } else {
                    // Aucune ligne sélectionnée : on désactive les boutons
                    for (var idBtn = 0; idBtn < listeBoutons.length; idBtn++) {
                        $('#'+listeBoutons[idBtn]).switchClass('ui-state-enabled', 'ui-state-disabled');
                    }
                }
            }
        },

        /**
         * Fonction de rechargement de la grille
         */
        reloadGrid : function(){
            var self = this;

            self.scrollPosition = jQuery("#" + this.paramsGroupes.nom_jqGrid).closest(".ui-jqgrid-bdiv").scrollTop();
            this.updateModuleContent();
        },


        /**
         * Callback
         */
        updateModuleContent : function(){
            jQuery("#" + this.paramsGroupes.nom_jqGrid).trigger("reloadGrid");
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
           var paramsGroupes = {}; // Variable transmise à la classe
           paramsGroupes.module = module; // Nom du module appelant

           paramsGroupes.gridTitle = 'Gestion des groupes d\'utilisateurs';
           paramsGroupes.gridHeight = ConfigJqGrid.gridHeight;
           paramsGroupes.nom_jqGrid = 'gridGroupes';

           paramsGroupes.boutonsVisibles = ['edit_gridGroupes', 'del_gridGroupes', 'info_gridGroupes'];

           paramsGroupes.nom_jqGridPager = 'pager_' + paramsGroupes.nom_jqGrid;

           this.paramsGroupes = paramsGroupes;

           this.nomDuGrid = this.paramsGroupes.nom_jqGrid;

           /** --------------------------------------------------------------------------
             * Affichage des boutons de l'écran
             * La liste des boutons visibles est fournie
             * par le paramètre paramsGroupes.boutonsVisibles
             -------------------------------------------------------------------------- */

            // Ce tableau se remplit au fur et à mesure de l'ajout des bouton à l'écran
            this.listeBoutonsDisponibles = [ 'del_gridGroupes', 'edit_gridGroupes', 'info_gridGroupes'];
        }

    });
    return Clazz;
};
