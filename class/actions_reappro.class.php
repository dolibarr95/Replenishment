<?php
/** 
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 * 	\file		reappro/class/actions_reappro.class.php
 *	\ingroup	reappro
 *	\brief		Ensemble de fonctions de base pour le module de réapprovisionnement
 *
 */


/**
 *	\class	Actionsreappro
 *	\brief	Classe d'action des souffleuses et machines clients
 */
class Actionsreappro
{
	private $db; //!< To store db handler
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

    /**
     * Constructor
     *
     * @param		DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
		return 1;
    }
	
	
	/**
 * Surcharger addMoreActionsButtons : affiche des boutons suplémentaire, ajoute des colones dans le détail de la commande fournisseur 
 *
 * @param   array()         $parameters     Hook metadatas (context, etc...)
 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
 * @param   string          &$action        Current action (if set). Generally create or edit or null
 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
 */
	

	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		
		if( $parameters['currentcontext'] == "ordersuppliercard" )
		{//commande fournisseur
	
			
		
			print "\n".'<!-- JS CODE pour masquer l\'édition de l\'extrafield reappro -->'."\n";
			print '<script>'."\n";	
	
			$tag = '"#order_supplier_extras_reappro_'.$object->id.'"';

			print '$(document).ready(function(){'."\n";
			   
			print '	if ( $( '.$tag.' ).length ) {'."\n";//la ligne existe
			print '		var childrenArray = $('.$tag.').parent("tr").children("td").children("table").children("tbody").children("tr").children("td").toArray();'."\n";//convertir en tableau l'objet
			print '		$(childrenArray[1]).html(\'\');'."\n";//vider l'index 1
			
			print '	}'."\n";
			   
			print '});'."\n";
		
			print '</script>'."\n";
		

		
		}

			
			
	}
		
	
	


	

}