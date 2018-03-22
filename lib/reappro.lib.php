<?php
/** 
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 * 	\file		reappro/lib/reappro.lib.php
 *	\ingroup	reappro
 *	\brief		Ensemble de fonctions de base pour le module reappro
 */
 
 /**
 *	Affficher une barre onglets de navigation pour l'administration
 *	@return     array		
 */ 
function reapproAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("reappro@reappro");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/reappro/admin/about.php", 1);
	$head[$h][1] = $langs->trans("about");
	$head[$h][2] = 'about';
	$h++;

	
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'reappro');

	return $head;
}

/**
 *	Affficher une barre onglets de navigation pour utilisateur
 *	@return     array		
 */ 
function reapproPrepareHead()
{
	global $langs, $conf, $obj_b;

	$langs->load("reappro@reappro");

	$h = 0;
	$head = array();

	
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'reappro');

	return $head;
}