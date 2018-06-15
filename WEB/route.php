<?php
/**
 * Routage des appels URL vers les controllers.
 *
 * PHP version 5
 *
 */

Route::post('utilisateur/auth', 'LoginController@postAuth');
Route::delete('utilisateur/auth', 'LoginController@deleteAuth');
Route::get('utilisateur\?q=login:(\S+)', 'LoginController@getIdByLogin');
Route::get('utilisateur\?q=login%3A(\S+)', 'LoginController@getIdByLogin');
Route::get('utilisateur$', 'LoginController@getDetail');

Route::put('utilisateur/profil/(\d+)/(\d+)/(\d+)', 'DroitsController@ajouterProfilUtilisateur');
Route::delete('utilisateur/profil/(\d+)/(\d+)/(\d+)', 'DroitsController@supprimerProfilUtilisateur');

Route::post('utilisateur$', 'UtilisateurController@postConfiguration');

// Gestion des habilitations
// Utilisateurs
Route::get('utilisateur/utilisateursListe', 'UtilisateurController@getUtilisateursListe');
Route::get('utilisateur/details/(\d+)', 'UtilisateurController@getDetails');
Route::post('utilisateur/enregistrer', 'LoginController@enregistrerUtilisateur');
Route::get('utilisateur/existe\?login=(.*)$', 'LoginController@isLoginInUse');
//Route::delete('utilisateur/supprimer/(\d+)', 'UtilisateurController@supprimerUtilisateur');

Route::get('utilisateur/listeGroupes', 'UtilisateurController@getListeGroupes');

// Groupes
Route::get('groupe/groupesListe', 'GroupeController@getGroupesListe');
Route::post('gestionGroupe', 'GroupeController@gestionGroupe');
Route::post('supprimerGroupe', 'GroupeController@gestionGroupe');
Route::get('controleAvantSuppression', 'GroupeController@controleAvantSuppression');
Route::post('groupe/groupeParNiveau', 'GroupeController@getGroupesParNiveau');
Route::post('groupe/detailUnGroupe', 'GroupeController@getDetailUnGroupe');

// @@5.10 - on teste si les groupes/utilisateurs existent sur bdd de restitution
Route::get('verification/Groupe/(.*)$', 'VerificationFromConfController@getGroupe');

// Administration
Route::post('administration$', 'AdministrationController@getUtilisateursListe');
Route::post('administration/supprimerUtilisateur', 'AdministrationController@supprimerUtilisateur');
Route::post('administration/activerUtilisateur', 'AdministrationController@activerUtilisateur');


//PARIS
Route::post('paris$', 'ParisController@getListeMatch');
Route::post('paris/sauvegarder', 'ParisController@sauvegarderParis');
Route::post('utilisateur/parisAutre$', 'ParisController@getListeParisUser');

//Classement
Route::post('classement$', 'ClassementController@getListeClassement');
