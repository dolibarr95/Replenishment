<?php
/** 
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 * 	\file		reappro/admin/about.php
 *	\ingroup    reappro
 *	\brief      A propos de ce module
 */

/// \cond IGNORER

require '../../../main.inc.php';
global $langs, $user;


// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/reappro.lib.php';
require __DIR__ . '/../vendor/autoload.php';

// Translations
$langs->load("reappro@reappro");


//Contrôle d'accès et statut du module
require_once '../core/modules/modreappro.class.php';	
$mod_status = new modreappro($db);
$const_name = 'MAIN_MODULE_'.strtoupper($mod_status->name);
if (  !$user->admin  || empty($conf->global->$const_name) ){
	accessforbidden();
}






 
 
/*
 * View
 */
$page_name = "À propos de ce module";
llxHeader('', $page_name);

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
	. $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($page_name, $linkback,'title_setup');

// Configuration header
$head = reapproAdminPrepareHead();
dol_fiche_head(
	$head,
	'about',
	$langs->trans("Module770000Name"),
	0,
	'reappro@reappro'
);





print "
<h2>Id du module</h2>
<p>Ce module fonctione sous l'id ".$mod_status->numero.".";

				
print"<br />Plus d'informations sur la liste des id disponibles sur : <a href=\"https://wiki.dolibarr.org/index.php/List_of_modules_id\" title=\"wiki.dolibarr.org\">https://wiki.dolibarr.org/index.php/List_of_modules_id</a></p>


<h2>Objet</h2>
<p>Alimenter des commandes fournisseur brouillons en fonction de commande client validées.</p>

<h2>Modules affectés</h2>
<ul><li>product</li></ul>

<h2>Todo</h2>
<p>Créer un extrafield dans les commandes fournisseur ( Attributs supplémentaires (commandes) ): reappro, case à cocher issues d'une table, commande:ref:rowid.</p>";




// Page end
dol_fiche_end();
llxFooter();
/// \endcond
?>