<?php
/** 
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 * 	\file		reappro/core/modules/modreappro.class.php
 *	\ingroup    reappro	
 *	\brief      Ce module à pour objectif de créer des commande fournisseur de réappro à partir de commande client
 *

 */ 
include_once DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php";

/**
 *	\class      modreappro
 *	\brief      Classe de description et d'activation du module
 */
class modreappro extends DolibarrModules
{

	/**
	 *	\brief	Constructeur. Définir des noms, des constantes, des répertoires, des boîtes, des autorisations
	 *
	 * 	@param	DoliDB		$db	Gestionnaire de base de données
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		// DolibarrModules is abstract in Dolibarr < 3.8
		if (is_callable('parent::__construct')) {
			parent::__construct($db);
		} else {
			$this->db = $db;
		}

		// Id du module (doit etre unique).
		// Utiliser un id libre ici
		// (Voir http://wiki.dolibarr.org/index.php/List_of_modules_id pour les plages disponibles).
		$this->numero = 770000;//numero de module a confirmer 
		$this->rights_class = 'reappro';//
		// Texte clé utilisé pour identifier le module (pour les autorisations, les menus, etc ...)
		// Family peut être 'crm','financial','hr','projects','products','ecm','technic','other'
		// Il est utilisé pour regrouper des modules dans la page de configuration du module
		$this->family = "products";
		$this->editor_name = "^..^";//
		// $this->core_enabled = 1;
		// Etiquette du module (pas d'espace autorisé)
		// utilisé si la traduction de 'ModuleXXXName' n'est pas trouvée
		// (où XXX est la valeur de la propriété numérique 'numero' du module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Description du module
		// Utilisé si la traduction de 'ModuleXXXDesc' n'est pas trouvée
		// (où XXX est la valeur de la propriété numérique 'numero' du module)
		$this->description = "Réapprovisionnement de stock";
		// Possible values for version are: 'development', 'experimental' or version
		$this->version = '1.0.0';
		// Key used in llx_const table to save module status enabled/disabled
		// (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
		// Where to store the module in setup page
		// (0=common,1=interface,2=others,3=very specific)
		$this->special = 0;//dans quel onglet apparait ce module
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png
		// use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png
		// use this->picto='pictovalue@module'
		$this->picto = 'reappro@reappro'; // Copyright fatcow.com
		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		// for default path (eg: /mymodule/core/xxxxx) (0=disable, 1=enable)
		// for specific path of parts (eg: /mymodule/core/modules/barcode)
		// for specific css file (eg: /mymodule/css/mymodule.css.php)
		 $this->module_parts = array(
            // 'triggers' => 1,
			// 'js' => array('reappro/js/clickToDialModal.js.php')
			'hooks' => array('ordersuppliercard'),//à l'ajout d'un objet dans la commande propale etc
        );

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/mymodule/temp");
		$this->dirs = array();

		// Config pages. Put here list of php pages
		// stored into mymodule/admin directory, used to setup module.
		$this->config_page_url = array("admin_reappro.php@reappro");

		// Dependencies
		// A condition to hide module
		$this->hidden = false;
		// List of modules class name as string that must be enabled if this module is enabled
		// Example : $this->depends('modAnotherModule', 'modYetAnotherModule')
		// $this->depends = array('');//'modgmaps'
		// List of modules id to disable if this one is disabled
		$this->requiredby = array();//
		// List of modules id this module is in conflict with
		$this->conflictwith = array();
		// Minimum version of PHP required by module
		$this->phpmin = array(5, 3);
		// Minimum version of Dolibarr required by module
		$this->need_dolibarr_version = array(6);
		// Language files list (langfiles@mymodule)
		$this->langfiles = array("reappro@reappro");
		// Constants
		// List of particular constants to add when module is enabled
		// (name, type ['chaine' or ?], value, description, visibility, entity ['current' or 'allentities'], delete on unactive)
		// Example:
		$this->const = array();

		// Array to add new pages in new tabs
		// Example:

		$this->tabs = array();
		
		


		// Dictionaries
		if (! isset($conf->mymodule->enabled)) {
			$conf->mymodule=new stdClass();
			$conf->mymodule->enabled = 0;
		}
		$this->dictionaries = array();
		

		// Boxes
		// Add here list of php file(s) stored in core/boxes that contains class to show a box.
		$this->boxes = array(); // Boxes list
		// Example:
		
	
		// Permissions
		$this->rights = array(); // Permission array used by this module
		// $r = 0;


	$this->menu = array();			// List of menus to add bug dans la liste impossible a afficher
	

	
		// Réappro
		
		$r=0;
		
				
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=commercial,fk_leftmenu=orders',
					'type'=>'left',
					'titre'=>'Réappro',
					'mainmenu'=>'',
					'leftmenu'=>'',
					'url'=>'/reappro/list.php',
					'langs'=>'reappro@reappro',
					'position'=>0,
					'enabled'=>'1',
					'perms'=>'1',
					'target'=>'',
					'user'=>0);
		$r++;
		
		
		
		
		
	
		// Exports
		$r = 0;

	
		
		
		
	}

	/**
	 * Function called when module is enabled.
	 * The init function add constants, boxes, permissions and menus
	 * (defined in constructor) into Dolibarr database.
	 * It also creates data directories
	 *
	 * 	@param		string	$options	Options when enabling module ('', 'noboxes')
	 * 	@return		int					1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		$sql = array();

		$result = $this->loadTables();

		return $this->_init($sql, $options);
	}

	/**
	 * Function called when module is disabled.
	 * Remove from database constants, boxes and permissions from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * 	@param		string	$options	Options when enabling module ('', 'noboxes')
	 * 	@return		int					1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		

		
		$sql = array();

		return $this->_remove($sql, $options);
	}

	/**
	 * Create tables, keys and data required by module
	 * Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * and create data commands must be stored in directory /mymodule/sql/
	 * This function is called by this->init
	 *
	 * 	@return		int		<=0 if KO, >0 if OK
	 */
	private function loadTables()
	{
		return $this->_load_tables('/reappro/sql/');
		
	}

}
