<?php
/**
 * Extension pour le Groupe.
 * 
 * PHP version 5
 *
 * @author Damien ANDRE <damien.andre@dgfip.finances.gouv.fr>
 */

class GroupeExt extends SinapsModel {

	/**
	 * Renvoie une ligne avec le cas échéant nomGroupe <mailGroupe> (telGroupe)
	 */

	public function getLigneInfoNoHtml() {

		$ligneGroupe = $this->nom . " ";
		if($this->groupeMail) {
			$ligneGroupe .= "<" . $this->groupeMail . ">";
		}
		if($this->groupeTelephone) {
			$ligneGroupe .= " (" . $this->groupeTelephone . ")";
		}
		if($this->nomSMA) {
			$ligneGroupe .= " - Groupe SMA: " . $this->nomSMA;
		}
		if($this->groupeDescription) {
			$ligneGroupe .= " - " . $this->groupeDescription;
		}
		return $ligneGroupe;
    }
    
	public function getLigneInfo() {

		$ligneGroupe = $this->nom . " ";
		if($this->groupeMail) {
			$ligneGroupe .= "<" . $this->groupeMail . ">";
		}
		if($this->groupeTelephone) {
			$ligneGroupe .= " (" . $this->groupeTelephone . ")";
		}
		if($this->nomSMA) {
			$ligneGroupe .= " - Groupe SMA: " . $this->nomSMA;
		}
		if($this->groupeDescription) {
			$ligneGroupe .= " - " . $this->groupeDescription;
		}
		return htmlentities($ligneGroupe);
    }
}
