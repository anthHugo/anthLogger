<?php
session_start();
require_once('../../../config/globals.inc.php');

// Vérifiaction authorisation à visualiser la page.
$utilisateur = new gestionUtilisateur( $_COOKIE["connexionID"] );
if ( $utilisateur->verifAcces() == FALSE )
	header( "Location: ../index.php" );

$template_mxt = new ModeliXe( "fiche-mxt.html" );
$template_mxt->SetModelixe();

$idFamilleToEdit = $_REQUEST["identifiant"];
$saveFormulaire = $_POST["saveFormulaire"];

if (isset($_REQUEST["message"]))
	$arrayMessages[] = $_REQUEST["message"];

$ongletEnCours = ( $_REQUEST["ongletEnCours"] != "" )?$_REQUEST["ongletEnCours"]:1;

// Création de l'objet produit
$objCatalogue = new catalogue();
$objCatalogue->produits->verbose = FALSE;
$objCatalogue->produits->arbreFamilles->setOrdreAffichageDefaut('LibFamille');

$objPublications = new publications('', TABLE_FAMILLES_DOCUMENTS);
$objPublications->racineSite = WEBROOT;
$objPublications->repFichiers = REP_DOCS_FAMILLES;

// Récupération de la famille pertinente
$objFamilles = new familles( 2, FALSE );
$idFamillePertinente = $objFamilles->getIdFamillePertinente();

// On récupère le niveau de la famille perniente
if ( $idFamillePertinente != "" && $idFamillePertinente != "#" )
	$niveauEnCours = $objCatalogue->produits->getNiveau( $idFamillePertinente );
else
	$niveauEnCours = 0;

$template_mxt->MxText( "nbListes", $niveauEnCours + 1 );

if ( $saveFormulaire == "oui" )
{
	$verifSaveFamille = saveInfosFamille();
}
// Affichage des information sur la famille
afficheInfosFamille();

// Documents joints à la famille
afficheDocuments();

afficheMessages( $arrayMessages, $template_mxt );

$template_mxt->MxAttribut( "actionForm", 'fiche.php' );
$template_mxt->MxText( "ongletActif", $ongletEnCours );
// Url de retour
$template_mxt->MxAttribut( "urlRetour", "window.location.href='liste.php?idFamille" . $niveauEnCours . "=" . $idFamillePertinente . "'" );
// Champs caché
$template_mxt->MxHidden( "hidden", "identifiant=" . $idFamilleToEdit . "&saveFormulaire=oui&effacerVignette=non&ongletEnCours=" . $ongletEnCours . "&typeDocToDel=" );

$template_mxt->MxWrite();

/**
 * *-----------------------------------------------------------------------------
 * 								FONCTIONS
 * -------------------------------------------------------------------------------
 */

function afficheInfosFamille()
{
	global $template_mxt, $idFamilleToEdit, $idFamillePertinente, $objCatalogue, $niveauEnCours, $objFamilles;
	// Recuperation des infos de la familles
	$arrayInfosFamille = $objCatalogue->produits->getInfosFamille( $idFamilleToEdit );

	// On affiche la liste box des familles parentes
	// Sous-Famille
	if ( $idFamillePertinente < 0 )
	{
		$objFamilles->afficheFilAriane( $template_mxt, $arrayIdFamille, $objCatalogue->produits, "Toutes les familles", "Fiche famille" );
		$template_mxt->MxHidden( 'hiddenFamille', 'idFamille' . $niveauEnCours . '=' . $idFamillePertinente );

		// Affichage de la description
		$template_mxt->MxFormField( "blocDescription.DescriptionFamille", "textarea", "DescriptionFamille", $arrayInfosFamille["DescriptionFamille"] );

		$template_mxt->MxBloc( "blocChoixFamille", "delete" );
		// $template_mxt->MxBloc("blocDescription", "delete");
	}
	else if ( $idFamillePertinente != "" && $idFamillePertinente != "#" )
	{
		// Affichage des listes box de choix des familles
		$familleAAfficher = ( $idFamilleToEdit != "" )?$arrayInfosFamille["IdFamilleParente"]:$idFamillePertinente;
		$arrayIdFamille = $objFamilles->getListesFormIDFamille( $familleAAfficher, $objCatalogue->produits );

		$objCatalogue->produits->arbreFamilles->arrayFiltre[] = "IdFamille > 0";

		$objFamilles->afficheListesBox( $arrayIdFamille, $template_mxt, $objCatalogue->produits );
		$objFamilles->afficheFilAriane( $template_mxt, $arrayIdFamille, $objCatalogue->produits, "Toutes les familles", "Fiche famille" );

		// Affichage de la description
		$template_mxt->MxFormField( "blocDescription.DescriptionFamille", "textarea", "DescriptionFamille", $arrayInfosFamille["DescriptionFamille"] );

		$template_mxt->MxBloc( "blocAltAccueil", "delete" );
	}
	else
	{
		// Famille de niveau 0
		$template_mxt->MxBloc( "blocChoixFamille", "delete" );

		$template_mxt->MxBloc( "blocDescription", "delete" ); // Ajoute le 05/07/2011 - N - N'est plus utilisé en FRONT pour le prmier niveau seulment

		$template_mxt->MxFormField( "blocAltAccueil.AltNavAccueil", "text", "AltNavAccueil", $arrayInfosFamille["AltNavAccueil"] );
	}


	$template_mxt->MxFormField( "AltCatalogue", "text", "AltCatalogue", $arrayInfosFamille["AltCatalogue"] );
	// Affichage des champs de formulaire
	$template_mxt->MxFormField( "LibFamille", "text", "LibFamille", $arrayInfosFamille["LibFamille"] );
	$template_mxt->MxText( "LibFamille", $arrayInfosFamille["LibFamille"] );

	$template_mxt->MxFormField( "IdFamille", "text", "IdFamille", $arrayInfosFamille["IdFamille"] );
	// On créer un faut objet Formulaire car la méthode SETUP_PAGE de modeleReferencement ne supporte que des objFormulaire ....
	$objFakeFormulaire = new gestionFormulaire( TABLE_FAMILLES );
	$objFakeFormulaire->editerChamps( 'IdModeleReferencement', $arrayInfosFamille['IdModeleReferencement'] );
	$objFakeFormulaire->editerChamps( 'MetaTitle', $arrayInfosFamille['MetaTitle'] );
	$objFakeFormulaire->editerChamps( 'MetaDescription', $arrayInfosFamille['MetaDescription'], 'area' );
	$objFakeFormulaire->editerChamps( 'MetaKeywords', $arrayInfosFamille['MetaKeywords'], 'area' );

	modeleReferencement::$libPersonnalise = 'Personnalisé...';

	// On affecte le modèle de référencement par défaut en fonction de la famille parente (si déstockage = modèle de référencement déstockage)
	if ($idFamillePertinente >= 0)
		modeleReferencement::SETUP_PAGE( $objFakeFormulaire, $template_mxt, DEFAULT_MODELE_PRODUITS_LISTE, TABLE_FAMILLES, 'IdFamille' );
	else
		modeleReferencement::SETUP_PAGE( $objFakeFormulaire, $template_mxt, DEFAULT_MODELE_DESTOCKAGE_LISTE, TABLE_FAMILLES, 'IdFamille' );

	try
	{
		$dictionnaire = new DataDictionnary(REFERENCEMENT_PATH);
		$dictionnaire->show('Catalogue / déstockage (Liste)', $template_mxt);
	}
	catch(Exception $exception)
	{
		die($exception->getMessage());
	}

	$objFakeFormulaire->creerFormulaire( $template_mxt );
	// Gestion particulière pour le déstockage
	if ( $idFamilleToEdit == - 1 )
		$template_mxt->MxFormField( "IdFamille", "text", "IdFamille", - 1, "disabled=\"disabled\"" );

	if ( !$idFamilleToEdit )
		$arrayInfosFamille['BoolActif'] = 1;

	$template_mxt->MxCheckerField( "BoolActifOui", "radio", "radio_BoolActif", 1, ( $arrayInfosFamille['BoolActif'] == 1 )?TRUE:FALSE, ( $idFamilleToEdit < 0 )?"disabled=\"disabled\"":'' );
	$template_mxt->MxCheckerField( "BoolActifNon", "radio", "radio_BoolActif", 0, ( $arrayInfosFamille['BoolActif'] == 0 )?TRUE:FALSE, ( $idFamilleToEdit < 0 )?"disabled=\"disabled\"":'' );
}

/** ^^ --------------------------------------- Affichage des documents --------------------------------------- ^^ */
function afficheDocuments()
{
	global $template_mxt,$arrayMessages,$utilisateur, $idFamilleToEdit;

	$objListingDocs = new listing(TABLE_FAMILLES_DOCUMENTS, $template_mxt, 1);
	$objListingDocs->champsOrderDefaut = "OrdreAffichage";
	$objListingDocs->libBtnEditer = "Editer";
	$objListingDocs->strNomElements = "document";
	$objListingDocs->strAdjectifElements = "listé";
	$objListingDocs->dateFormat = "jmahm";
	$objListingDocs->nbreResultParPage = 0;
	$objListingDocs->afficheBtnOrdreAffichage = FALSE;
	$objListingDocs->afficheBtnExport = FALSE;
	$objListingDocs->dragDrop = TRUE;
	$objListingDocs->ajoutFiltreOrdre('IdFamille = "'.$idFamilleToEdit.'"');
	$objListingDocs->fonctionEdit = 'popupDoc';
	$objListingDocs->ajoutLienSurBtnEdit('IdFamille'); // Pour passer l'ID de contenu à la fonction popup();
	$objListingDocs->blocListing = 'blocDocuments';
	//$objListingDocs->setVerbose();

	// Activation du gestionnaire d'evenement sur le listing
	$objListingDocs->enableActionsListener();
	$objListingDocs->objActionsListener->setCallBackAction("effacer", "callBackDeleteDocs"); // Pour suppression spécifique uniquement

	// Mise en place de l'ecouteur d'actions demandées
	$verifAction = $objListingDocs->verifActionsListener();
	if ($verifAction !== NULL) // Bien laisser NULL et pas FALSE
		$arrayMessages[] = ($verifAction == TRUE)?"Action effectuée avec succès.":"Erreur! Impossible d'effectuer l'action demandée.";

	$requete = 'SELECT *, BoolActif AS EnLigne
				FROM '.TABLE_FAMILLES_DOCUMENTS.'
				WHERE IdFamille = "'.$idFamilleToEdit.'"';

	$objListingDocs->getListeRequete($requete);
	// Gere le changement de position en drag and drop
	$objListingDocs->verifChangementPosition();

	$objListingDocs->setFormatageTexte('CheminDoc', 'callBackBtnVoirDocs', array(), FALSE);
	$objListingDocs->setFormatageTexte('EnLigne', 'callBackBoolActif', array(), FALSE);

	// Affichage des infos
	$objListingDocs->afficheInfos();

	// Affichage du CSS qui va bien pour la drop zone
	if (count($objListingDocs->arrayInfos) > 0)
		$template_mxt->MxAttribut($objListingDocs->blocListing.'.classDropZone', 'upMlt_dragZone upMlt_encartVide_petit');
	else
		$template_mxt->MxAttribut($objListingDocs->blocListing.'.classDropZone', 'upMlt_dragZone upMlt_encartVide_grand');


	// Affichage du bloc pour ajouter un document
	if ($idSejour != '')
		$urlAjoutDoc = 'popupDoc(\'\', \''.$idSejour.'\')';
	else
		$urlAjoutDoc = 'alert(\'Vous devez effectuer une première sauvegarde avant de pouvoir ajouter un lien\')';

	$template_mxt->MxAttribut("blocDocuments.urlAjoutLien", "javascript:".$urlAjoutDoc);
}

function callBackDeleteDocs($idEnregistrement)
{
	global $template_mxt,$objFamilles,$objPublications,$arrayMessages,$utilisateur;

	$verif = $objPublications->deletePublication($idEnregistrement);

	return $verif;
}

function callBackBtnVoirDocs($cheminDoc)
{
	global $template_mxt,$objFamilles,$arrayMessages,$utilisateur;

	if (publications::getProtocole($cheminDoc) == 'HTTP')
		$urlDoc = $cheminDoc;
	else
		$urlDoc =  "../../".REP_DOCS_FAMILLES.$cheminDoc;

	if ($cheminDoc != "")
	{
		$template_mxt->MxFormField("voir", "button", "btnVoir", "Voir", "class=\"adminBouton\" style=\"width:50px\" onClick=\"window.open('".$urlDoc."');\"");
		$template_mxt->MxUrl('urlVoir', $urlDoc);
	}
}

function callBackBoolActif($boolActif)
{
	global $template_mxt;

	if ($boolActif == 1)
		$template_mxt->MxText('BoolActif', 'En ligne');
	else
		$template_mxt->MxText('BoolActif', 'Hors ligne');
}
/** $$ --------------------------------------- Affichage des documents --------------------------------------- $$ */


/** ^^ --------------------------------------- Sauvegarde de la Famille --------------------------------------- ^^ */
function saveInfosFamille()
{
	global $template_mxt, $idFamilleToEdit, $idFamillePertinente, $objCatalogue, $niveauEnCours, $dontRefresh, $ongletEnCours, $arrayMessages;

	$formulaire = $_POST;

	$arrayToSave = array();
	// si ils'agit d'un nouvel enregistrement
	// on verif que l'id est dispo et on créer l'id dans la table
	if ( $idFamilleToEdit == "" )
	{
		$arrayFamExiste = $objCatalogue->produits->arbreFamilles->selectAllFromTable( "IdFamille=" . $_POST["IdFamille"] );

		if ( count( $arrayFamExiste ) == 0 )
		{
			if ( $niveauEnCours == "" )
				$niveauFamille = 0;
			else
				$niveauFamille = $niveauEnCours + 1;

			$arrayToSave['IdFamille'] = $_POST["IdFamille"];
			$arrayToSave['NiveauFamille'] = $niveauFamille;

			$ordreAffichage = new ordreAffichage( $objCatalogue->produits->arbreFamilles->nomTable, "OrdreAffichage", 'IdFamille' );
			if ( $niveauEnCours != '' )
				$ordreAffichage->ajoutFiltre( 'IdFamilleParente="' . $_POST["idFamille" . $niveauEnCours] . '"' );
			else
				$ordreAffichage->ajoutFiltre( 'NiveauFamille=0' );

			$arrayToSave['OrdreAffichage'] = $ordreAffichage->getNewOrdreAffichage();
		}
		else
			$arrayMessages[] = "Cet ID est déjà utiliser par une autre famille";
	}

	if ( count($arrayMessages) == 0 )
	{
		$arrayToSave['IdFamille'] = $_POST["IdFamille"];
		$arrayToSave['LibFamille'] = $_POST["LibFamille"];
		$arrayToSave['DescriptionFamille'] = $_POST["DescriptionFamille"];
		$arrayToSave['AltNavAccueil'] = $_POST["AltNavAccueil"];
		$arrayToSave['AltCatalogue'] = $_POST["AltCatalogue"];
		$arrayToSave['BoolActif'] = $_POST["radio_BoolActif"];

		if ( $idFamillePertinente > 0 )
			$arrayToSave['IdFamilleParente'] = $idFamillePertinente;

		// Référencement
		$arrayToSave["IdModeleReferencement"] = $_POST["select_IdModeleReferencement"];

		if ( isset( $_POST["ch_MetaTitle"] ) )
		{
			$arrayToSave["MetaTitle"] = $_POST["ch_MetaTitle"];
			$arrayToSave["MetaDescription"] = $_POST["area_MetaDescription"];
			$arrayToSave["MetaKeywords"] = $_POST["area_MetaKeywords"];
		}

		// On sauvegarde les infos
		// $objCatalogue->produits->arbreFamilles->debug = TRUE;
		$verifUpdate = $objCatalogue->produits->arbreFamilles->saveRecord( $arrayToSave, $idFamilleToEdit, "IdFamille" );

		if ( $verifUpdate !== FALSE )
		{
			$idFamilleToEdit = $verifUpdate;
			$arrayMessages[] = "Sauvegarde effectuée avec succès.";


			// ^^ Création du slug -----------------------------------------------------------------------------------------------------
			$objSlug = new Slug();
			// Pour rafraichir les infos sur les familles elles ne sont récupérer en base qu'a l'initialisation de l'objet
			$objCatalogue->produits->arbreFamilles->reInit();
			$arrayHierarchieSlug = $objCatalogue->produits->arbreFamilles->getHierarchie( $idFamilleToEdit );

			if ( count( $arrayHierarchieSlug ) > 0 )
			{
				$libFamille = DS;
				foreach( $arrayHierarchieSlug as $familleSlug )
					$libFamille .= formatageTexte::formatUrl( $familleSlug['LibFamille'], TRUE ) . DS;

				$objSlug->addSlug( array(
							':Slug' => $libFamille,
							':Controller' => 'catalogue',
							':Methode' => 'index',
							':IdObjet' => $idFamilleToEdit,
							':IdParent' => $idFamillePertinente,
							':TypePage' => 'Famille'
						), TRUE, 'Produit');
			}
			// $$ Création du slug -----------------------------------------------------------------------------------------------------


		}
		else
			$arrayMessages[] = "Impossible d'effectuer la sauvegarde.<br/>" . $objCatalogue->produits->arbreFamilles->_error;
		// Pour rafraichir les infos sur les familles elles ne sont récupérer en base qu'a l'initialisation de l'objet
		$objCatalogue->produits->arbreFamilles->reInit();
	}

	return $verifUpdate;
}
/** $$ --------------------------------------- Sauvegarde de la Famille --------------------------------------- $$ */
?>
