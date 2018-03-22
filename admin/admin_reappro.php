<?php
/** 
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 * 	\file		reappro/admin/admin_reappro.php
 * 	\ingroup	reappro
 * 	\brief		Page d'accueil de l'administration du module
 */
/// \cond IGNORER
// Load Dolibarr environment
require '../../../main.inc.php';


global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/reappro.lib.php';
// Translations
$langs->load("reappro@reappro");

//Contrôle d'accès et statut du module
require_once '../core/modules/modreappro.class.php';	
$mod_status = new modreappro($db);
$const_name = 'MAIN_MODULE_'.strtoupper($mod_status->name);
if ( !$user->admin  || empty($conf->global->$const_name) ){
	accessforbidden();
}

// Parameters


/*
 * Actions
 */


/*
 * View
 */
$page_name = "Informations générales";
llxHeader('', $page_name);

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
	. $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($page_name, $linkback,'title_setup');

// Configuration header
$head = reapproAdminPrepareHead();
dol_fiche_head(
	$head,
	'MyModuleIndex',
	$langs->trans("Module770000Name"),
	0,
	"reappro@reappro"
);

// Setup page goes here
echo 'Page d\'accueil du module reappro';
// echo '<br><br>';
$objMod = new modreappro($db);
print '<ul>';
print '<li>Fonctionne avec Dolibarr : ';
foreach($objMod->need_dolibarr_version as $value) print $value.' ';
print '</li><li>Version du module : '.$objMod->version.'</li>';
print '<li>Description : '.$objMod->description.'</li>';
print '<li>Auteur : '.$objMod->editor_name.'</li>';
print '</ul>';
// Page end
dol_fiche_end();
llxFooter();
/// \endcond
?>