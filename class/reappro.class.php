<?php
/** 
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 * 	\file		reappro/class/reappro.class.php
 *	\ingroup	reappro
 *	\brief		Ensemble de fonctions d'action de base pour le module de réapprovisionnement
 */
require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT .'/core/class/commonobjectline.class.php';
/**
 *	\class	reappro
 *	\brief	Classe d'action des souffleuses et machines clients
 */
class reappro extends CommonObject
{
	public $element				= 'reappro';
	public $table_element		= 'reappro';
    public $table_element_line	= 'reappro_det';
    public $fk_element			= 'fk_reappro';
	public $picto				= 'reappro@reappro';
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
    }
	
	/**
	 *	Array de la liste commandes client validées
	 *
	 *	@param		int			$limit				Nombre de résultats par page
	 *	@param		int			$offset				Pagination
	 *	@param		string		$sortfield			Colonne à trier
	 *	@param		string		$sortorder			Ordre de tri

	 *	@param		string		$search_customer	Recherche nom de la societe
	 *	@param		date		$search_date		Recherche date de création
	 *	@param		int			$search_user		Recherche auteur
	 *	@param		bolean		$total				Retourner le total
	 *		 
	 *  @return		array or int
	 */		
	function liste_des_commandes_client($limit, $offset, $sortfield, $sortorder,  $search_customer, $search_date, $search_user, $total){
		
		$sql = "SELECT c.rowid FROM ".MAIN_DB_PREFIX."commande AS c";
		
		if ( $search_customer ){//recherche nom client
			$sql .= ", ".MAIN_DB_PREFIX."societe AS soc";
			
		}   
		
		$sql .= " WHERE c.fk_statut = 1";
		


		if ( $search_customer ){//recherche nom client
			$sql .= " AND c.fk_soc = soc.rowid";
			$sql .= natural_search('soc.nom', $search_customer);
		}   
			
		
		if ( $search_user != -1 && $search_user!=''){//recherche nom client
			$sql .= " AND c.fk_user_author =  ".$search_user;
		}   
		
		if( $search_date != -1 ) {//recherche date création
			$sql .= " AND c.date_creation LIKE '".$search_date."%'";

		}
		
$sql .= " AND c.entity = 1";
		
	// print $sql;
	
		$sql.= $this->db->order($sortfield,$sortorder);
		if(!$total){
			$sql .= " LIMIT ".($offset).",".$limit.""; 
		}
		
	
		$resql 	= $this->db->query($sql);
		$array = false;
		if($this->db->num_rows($resql)!=0 ){
			
			if($total){
				$array = $this->db->num_rows($resql);
			}else{
				$objp = $this->db->fetch_object($resql);	
				$array = array();
				do{
					array_push($array,$objp->rowid);
					
				}while($objp = $this->db->fetch_object($resql));
			}
			
				

		}
		// print $sql;
		return $array;	
	}

	/**
	 *	Trouver une commande fournisseur brouillon d'une societe d'un produit 
	 *
	 *	@param		int			$societe		Id societe
	 *	@param		int			$produit		Id produit
	 *	 
	 *  @return		int			Id de la commande fournisseur
	 */
	function id_commande_fournisseur($societe, $produit=0){
		
		global $user;
		$return = array();
		
		if( $produit != 0 )
		{//recherche pour mise a jour ligne cf
				
			//recherche de cf selon produit et auteur
			$sql  = "SELECT cf.rowid, cfd.rowid AS ligne, cfd.qty FROM ".MAIN_DB_PREFIX."commande_fournisseur AS cf, ".MAIN_DB_PREFIX."commande_fournisseurdet AS cfd";
			$sql .= " WHERE cf.fk_statut = 0";
			$sql .= " AND cf.rowid = cfd.fk_commande";
			
			$sql .= " AND cfd.fk_product =".$produit;//produit 
				
			$sql .= " AND cf.fk_soc = ".$societe;//societe 
			$sql .= " AND cf.fk_user_author = ".$user->id;//auteur 
			$sql .= " GROUP BY  cf.rowid";//il peux y avoir plusieurs lignes de ce produit dans une meme cf 
			
			$resql 	= $this->db->query($sql);
			
			if($this->db->num_rows($resql) != 0 )
			{//il existe des commandes fournisseurs avec ce produit de cet auteur
		
				$objp = $this->db->fetch_object($resql);

				$return[0] = $objp->rowid;
				$return[1] = $objp->ligne;
				$return[2] = $objp->qty;
			}
			else
			{//rechercher des cf selon produit uniquement
				
				$sql  = "SELECT cf.rowid, cfd.rowid AS ligne, cfd.qty FROM ".MAIN_DB_PREFIX."commande_fournisseur AS cf, ".MAIN_DB_PREFIX."commande_fournisseurdet AS cfd";
				$sql .= " WHERE cf.fk_statut = 0";
				$sql .= " AND cf.rowid = cfd.fk_commande";
				
				$sql .= " AND cfd.fk_product =".$produit;//produit 
					
				$sql .= " AND cf.fk_soc = ".$societe;//societe 

				$sql .= " GROUP BY cf.rowid";//il peux y avoir plusieurs lignes de ce produit dans une meme cf 
				// print $sql;
				$resql 	= $this->db->query($sql);
				
				if($this->db->num_rows($resql) != 0 )
				{//il existe des commandes fournisseurs avec ce produit
			
					$objp = $this->db->fetch_object($resql);
					
					$return[0] = $objp->rowid;
					$return[1] = $objp->ligne;
					$return[2] = $objp->qty;
				}	
					
			}
		}
		else
		{//recherche cf sans ligne produit
			$sql  = "SELECT cf.rowid FROM ".MAIN_DB_PREFIX."commande_fournisseur AS cf";
			$sql .= " WHERE cf.fk_statut = 0";
			$sql .= " AND cf.fk_soc = ".$societe;//societe 

			$resql 	= $this->db->query($sql);
				
			if($this->db->num_rows($resql) != 0 )
			{//il existe des commandes fournisseurs
			
				$objp = $this->db->fetch_object($resql);
					
				$return = array();
				$return[0] = $objp->rowid;
				
			}
		}
		
		return $return;
		
	}
	/**
	 *	Retourner la liste des cf liées a une commande client  
	 *
	 *	@param		int	id	Id de la commande client
	 *	 
	 *  @return		array	Liste des commandes
	 */	
	function commande_fournisseur_liees($id){
	
		$array=array();
		
		$sql  = "SELECT x.fk_object FROM ".MAIN_DB_PREFIX."commande_fournisseur_extrafields AS x";
		$sql .= " WHERE x.reappro  LIKE '".$id."' OR x.reappro LIKE '".$id.",%' OR x.reappro LIKE '%,".$id.",%' OR x.reappro LIKE '%,".$id."%' ";

		$resql 	= $this->db->query($sql);
		if($this->db->num_rows($resql)!=0 )
		{//il existe des commandes fournisseurs
	
			$objp = $this->db->fetch_object($resql);
			do{
				$array[] = $objp->fk_object;
			}while( $objp = $this->db->fetch_object($resql) );
			
			
		}
		return $array;
	}
	/**
	 *	Retourner le nombre d'articles en commande  
	 *
	 *	@param		int	id	Id du produit
	 *	 
	 *  @return		int	quantité en commande
	 */	
	function quantite_en_commande($id){
		
		$sql  = "SELECT  sum(cfd.qty) AS total FROM ".MAIN_DB_PREFIX."commande_fournisseur AS cf, ".MAIN_DB_PREFIX."commande_fournisseurdet AS cfd";
		$sql .= " WHERE cf.fk_statut IN (0,1,2,3)";
		$sql .= " AND cf.rowid = cfd.fk_commande";
		$sql .= " AND cfd.fk_product IS NOT NULL";//produit défini
		$sql .= " AND cfd.product_type=0";//pas de service etc
		$sql .= " AND cfd.fk_product=".$id."";//pas de service etc
		// $sql .= " GROUP BY cfd.fk_product";
	
		$resql 	= $this->db->query($sql);
		// return $sql;
		if($this->db->num_rows($resql)!=0 )
		{//il existe des commandes fournisseurs
	
			$objp = $this->db->fetch_object($resql);
			return $objp->total;
		}
	}
	
	
	/**
	 *	Classer la liste des produits(quantité) par référence 
	 *
	 *	@param		array			$produits	Liste des produits (rowid=>quantité)
	 *	 
	 *  @return		array			key = array(rowid, quantity)
	 */	
	function ordonner_tableau($produits){
		
		$array = array();
		$liste	=	array_keys( $produits );
		$liste	=	implode(",", $liste );
		
		$sql  = "SELECT p.rowid FROM ".MAIN_DB_PREFIX."product AS p";
		$sql .= " WHERE p.rowid IN (".$liste.")";//produits concernés uniquement
		$sql .= " AND p.tobuy = 1";
		$sql .= " ORDER BY p.ref";
		$resql 	= $this->db->query($sql);
		if($this->db->num_rows($resql)!=0 )
		{//il existe des commandes fournisseurs
	
			$objp = $this->db->fetch_object($resql);
			
			do{
				$array[] = array($objp->rowid, $produits[$objp->rowid]);			
			}while($objp = $this->db->fetch_object($resql));
		}
		return $array;
	}
	
	/**
	 *	Array de la liste commandes fournisseur  statut 0,1,2,3
	 *
	 *	@param		array			$produits		Liste des produits que l'on veut en réapro
	 *	 
	 *  @return		array or int
	 */	
	function liste_des_produits_commandes_fournisseur( $produits ){

		$array = false;
		$produits2 = array();//produits déjà traité dans des cf non receptionnées
		$produits3 = array();//produits non traité dans des cf non receptionnées
		//array des produits en string
		$liste	=	array_keys( $produits );
		$liste	=	implode(",", $liste );
		
		$sql  = "SELECT cfd.fk_product, sum(cfd.qty) AS total, p.stock,p.seuil_stock_alerte FROM ".MAIN_DB_PREFIX."commande_fournisseur AS cf, ".MAIN_DB_PREFIX."commande_fournisseurdet AS cfd, ".MAIN_DB_PREFIX."product AS p";
		$sql .= " WHERE cf.fk_statut IN (0,1,2,3)";
		$sql .= " AND cf.rowid = cfd.fk_commande";
		$sql .= " AND cfd.fk_product IS NOT NULL";//produit défini
		$sql .= " AND cfd.product_type=0";//pas de service etc
		$sql .= " AND cfd.fk_product IN (".$liste.")";//produits concernés uniquement
		$sql .= " AND cfd.fk_product = p.rowid";//produits concernés uniquement
		$sql .= " GROUP BY cfd.fk_product";
	
		$resql 	= $this->db->query($sql);
		if($this->db->num_rows($resql)!=0 )
		{//il existe des commandes fournisseurs
	
			$objp = $this->db->fetch_object($resql);
			
			do{
				
				if( $objp->seuil_stock_alerte == '')
				{//stock minimum
					$objp->seuil_stock_alerte = 0;
				}
				
				$s = $objp->stock + $objp->total;
				$s = $s - $produits[$objp->fk_product];
				
				if($s < $objp->seuil_stock_alerte)
				{
					$s = abs($s) + $objp->seuil_stock_alerte;
				}
				else{
					$s=0;
				}
				$produits2[$objp->fk_product] = $s;//quantité à commander en fonction des pieces déjà commandées
			
			}while($objp = $this->db->fetch_object($resql));
		}
		
		$produits3 =  array_diff_key($produits, $produits2);//produits pas traité dans des cf non receptionnées
		
		
		if(count($produits3) > 0)
		{//il reste encore des produits à traiter on vérifie si les niveaux d'alertes de stock
			
			//array des produits en string
			$liste	=	array_keys( $produits3 );
			$liste	=	implode(",", $liste );
			
			$sql  = "SELECT p.rowid,  p.stock, p.seuil_stock_alerte FROM ".MAIN_DB_PREFIX."product AS p";
			$sql .= " WHERE p.rowid IN (".$liste.")";//produits concernés uniquement
			
			
			$resql 	= $this->db->query($sql);
			if($this->db->num_rows($resql)!=0 )
			{//tout va bien
				$objp = $this->db->fetch_object($resql);
			
				do{
					
					if( $objp->seuil_stock_alerte == '')
					{//stock minimum
						$objp->seuil_stock_alerte = 0;
					}
					
					$s = $objp->stock;

					$s = $s - $produits[$objp->rowid];
					
					if($s < $objp->seuil_stock_alerte)
					{
						$s = abs($s) + $objp->seuil_stock_alerte;
					}
					else{
						$s=0;
					}
					$produits3[ $objp->rowid ] = $s;//quantité à commander
					
						
				}while($objp = $this->db->fetch_object($resql));
			}
			
		}
		
		$array = $produits2 + $produits3;//pieces commandées + pièces non commandées
		
		return $array;

	}
	
	
	
	
	
}	