<?php
/** 
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	\file       reappro/list.php
 *	\ingroup    reappro	
 *	\brief      Afficher les commandes clients validées et généerer des cf brouillon 
 *
 */

// Load Dolibarr environment
require '../../main.inc.php'; // From "custom" directory
// require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once 'lib/reappro.lib.php';
require_once 'class/reappro.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
// Translations
$langs->load("reappro@reappro");
$langs->load("admin");
$langs->load("errors");
$langs->load('other');

/// \cond IGNORER
//Contrôle d'accès et statut du module
require_once 'core/modules/modreappro.class.php';	
$mod_status = new modreappro($db);
$const_name = 'MAIN_MODULE_'.strtoupper($mod_status->name);
if ( ( !$user->rights->fournisseur->commande->commander && !$user->admin ) || empty($conf->global->$const_name) ){
	accessforbidden();
}


 /**
  *	@var	int		$limit		Nombre de résultats par page
  */ 
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;//$conf->liste_limit
 /**
  *	@var	int		$page		Pagination
  */
$page = (GETPOST("page",'int')?GETPOST("page", 'int'):0);
if (empty($page) || $page == -1) { $page = 0; } 
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

 /**
  *	@var	string		$sortfield		Colonne à trier
  */ 
$sortfield = GETPOST("sortfield",'alpha');

 /**
  *	@var	string		$sortorder		Type de tri
  */ 
$sortorder = GETPOST("sortorder",'alpha');

 /**
  *	@var	int		$step		Type de panier
  */ 
$action = GETPOST("action",'alpha');
 /**
  *	@var	string		$param		Paramètres à transmettre
  */ 
$param = '';

//tri par défaut
if (! $sortfield) $sortfield="c.date_creation";
if (! $sortorder) $sortorder="DESC";

 /**
  *	@var	string		$search_customer		Recherche nom societé
  */ 
$search_customer		= GETPOST('search_customer','alpha');
 /**
  *	@var	int		$search_user		Recherche nom client
  */ 
$search_user		= GETPOST('search_user','int');
 /**
  *	@var	int		$search_paiement		Recherche type de paiement
  */ 
$search_paiement		= GETPOST("search_paiement","int");
 /**
  *	@var	int		$search_dateday		Recherche jour date panier
  */ 
$search_dateday			= GETPOST("search_dateday","int");
 /**
  *	@var	int		$search_datemonth		Recherche moi date panier
  */ 
$search_datemonth		= GETPOST("search_datemonth","int");
 /**
  *	@var	int		$search_dateyear		Recherche année date panier
  */ 
$search_dateyear		= GETPOST("search_dateyear","int");
 /**
  *	@var	string		$search_date		Recherche date panier
  */ 
$search_date			= -1;

if($search_dateday && $search_datemonth && $search_dateyear){
	
	
	if( $search_datemonth < 10 ){
		$search_datemonth = '0'.$search_datemonth;
	}
	if( $search_dateday < 10 ){
		$search_dateday = '0'.$search_dateday;
	}
	
	$search_date = $search_dateyear.'-'.$search_datemonth.'-'.$search_dateday;
	
	$param	.= "&amp;search_dateyear=".$search_dateyear;
	$param	.= "&amp;search_datemonth=".$search_datemonth;
	$param	.= "&amp;search_dateday=".$search_dateday;
	
}
// Do we click on purge search criteria ?
if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
{
    $search_customer='';
	$search_dateday='';
	$search_datemonth='';
	$search_dateyear='';
	$search_date=-1;
	$search_paiement =-1;
	$search_user='';
	
}


if($step != ''){
	$param.="&amp;step=".$step;
}
if($search_customer){
	$param.="&amp;search_customer=".urlencode($search_customer);
}
if($search_user){
	$param.="&amp;search_user=".urlencode($search_user);
}
if($search_paiement > 0){
	$param.="&amp;search_paiement=".$search_paiement;
}


/*
 * Actions
 */


	
/*
 * View
 */


llxHeader('', 'Réappro');


$head = reapproPrepareHead();


$obj_b = new reappro($db);
if($action == 'reappro' || $action == 'valider' )
{//réappro en cours

	
	if($action == 'valider' )
	{//créer des commandes fournisseurs
		$liste = array();//tableau pour génerer les commandes fournisseur [idfournisseur] =array(array[idproduit]=qte);
		$xtra = array();//tableau pour alimenter les extrafields [idfournisseur] = idcommande,idcommande;

		foreach($_POST as $k => $v)
		{//recupere le formulaire
			
			if(explode("_", $k)[0] == "product")
			{//produit
				$id_product		= explode("_", $k)[1];
				$qty_product	= abs($v);
				
				$id_supplier	= GETPOST('supplier_'.$id_product,"int");
				$id_price		= GETPOST('price_'.$id_product,"int");
				
				// print 'id produit:'.$id_product.'<br>';
				// print 'qte produit:'.$qty_product.'<br>';
				// print 'fournisseur:'.$id_supplier.'<br>';
				// print 'ligne prix recommandée:'.$id_price.'<hr>';
				$orderlist_id = GETPOST('order_'.$id_product,"alpha");//liste des cf en lien
				
				
				if($id_supplier >0 && $qty_product>0 && $id_product > 0)
				{//variables définies (on ne vérifie que le produit puis le reste)
					// print '$orderlist_id:'.$orderlist_id.'<br>';
					if(!isset($liste[$id_supplier]))
					{//liste des produits de ce fournisseur, liste des commandes pour ce fournisseur
						$liste[$id_supplier] = array();
						$xtra[$id_supplier] = '';
					}
					$liste[$id_supplier][$id_product] = $qty_product;
					
					$xtra[$id_supplier] = $xtra[$id_supplier].$orderlist_id;
					
				}
				
			}
		}


		if(count($liste) > 0)
		{//des fournisseur définis avec leur produits
			
			foreach($xtra as $f=>$l)
			{//dedoublonner si necessaire la liste de commandes clients par fournisseur
				if(substr($l,-1) == ',')
				{
					$l = substr($l,0,strlen($l)-1);
				}
				$l = explode(',', $l);
				$l = array_unique($l);
				$l = implode(',', $l);
				$xtra[$f] = $l;
			}
			
	

			$liste_cf = array();//pour recaptiuler la liste des cf crées ou modifié pour cette réappro
			foreach($liste as $id_soc => $courses)
			{//lister les founisseurs séléctionés
			

				// print 'fournisseur :'.$id_soc.'<br>';
				foreach($courses as $id_product => $qty_product)
				{//lister les produits désirés chez ce fournisseur
					
					
					// print 'produit à traiter:'.$id_product.' qté:'.$qty_product.'<br>';
					
					//recherche de cf
					$id_cf = $obj_b->id_commande_fournisseur($id_soc, $id_product);//une cf brouillon avec ce produit (brouillon dont je suis l'auteur puis brouillon simplement)
					if(!isset($id_cf[0]) && !isset($id_cf[1]) )
					{//trouver une cf brouillon simplement
						$id_cf = $obj_b->id_commande_fournisseur($id_soc);//une cf brouillon de ce fournisseur
					}
					
					$cfstatic = new CommandeFournisseur($db);
					

					if( !isset($id_cf[0]) )
					{//créer nouvelle cf car non trouvé
					
						$cfstatic->ref_supplier  		= 'Reappro';
						$cfstatic->socid         		= $id_soc;
						$cfstatic->cond_reglement_id 	= '';
						$cfstatic->mode_reglement_id 	= '';
						$cfstatic->fk_account        	= '';
						$cfstatic->note_private			= '';
						$cfstatic->note_public   		= '';
						$cfstatic->date_livraison		= '';
						$cfstatic->fk_incoterms 		= '';
						$cfstatic->location_incoterms 	= '';
						$cfstatic->multicurrency_code 	= '';
						$cfstatic->multicurrency_tx 	= '';
						$cfstatic->fk_project       	= '';
						
						$id = $cfstatic->create($user);
				
						if ($id < 0)
						{
							$error++;
							//creation de la cf erreur
							// print 'céer une cf erreur:'.$id.'<br>';
						}
						else
						{
							$id_cf[0] = $id;//id de la cf crée
							// print 'céer une cf ok:'.$cfstatic->ref.'<br>';

							
						}

					}		
					
					//A ce stade la cf existe il faut donc soit inserer la ligne soit mettre à jour
					if( $cfstatic->fetch($id_cf[0]) > 0 )
					{
						
	
						$cfstatic->fetch_thirdparty();
						
						// print 'la cf existe<br>';
						if( isset($id_cf[1]) )
						{//mettre à jour une ligne (delete + insert et pas updater)
					
							$qty_product += $id_cf[2];//quantité + quantité dans la cf brouillon
							// print 'quantité en plus de la ligne cf (il faudra supprimer cette ligne)<br>';
								
						}
					
						//recherche de prix pour cette quantité
						$sqlOpt = " SELECT rowid FROM `".MAIN_DB_PREFIX."product_fournisseur_price` WHERE `fk_product` = ".$id_product." AND ".$qty_product." >= quantity AND fk_soc = ".$id_soc." ORDER BY quantity DESC LIMIT 0,1";
						$resql 	= $db->query($sqlOpt);
					
						if( $db->num_rows($resql) == 1 )
						{//le prix pour cette quantité existe et la cf est bien chargée on continue
							
							
							if(!isset($liste_cf[$cfstatic->id]))
							{//nouvelle cf pour cette réappro
								$liste_cf[$cfstatic->id] = $cfstatic->ref;
							}
							
							
							if(isset($id_cf[1]))
							{//mettre à jour une ligne (delete + insert et pas updater)
							
								if($cfstatic->deleteline($id_cf[1]))
								{//suppression de la ligne
									// print 'ligne supprimée:'.$id_cf[1].'<br>';
								}

							}
							
							$objp = $db->fetch_object($resql);
							$idprodfournprice = $objp->rowid;//prix fournisseur
									
							$productsupplier = new ProductFournisseur($db);
									
							$label = $productsupplier->label;
							$desc = $productsupplier->description;
							if (trim($product_desc) != trim($desc)) $desc = dol_concatdesc($desc, $product_desc);
							$type = $productsupplier->type;
							$tva_tx	= get_default_tva($cfstatic->thirdparty, $mysoc, $productsupplier->id, $idprodfournprice);
							$tva_npr = get_default_npr($cfstatic->thirdparty, $mysoc, $productsupplier->id, $idprodfournprice);
							if (empty($tva_tx)) $tva_npr=0;
							$localtax1_tx= get_localtax($tva_tx, 1, $mysoc, $cfstatic->thirdparty, $tva_npr);
							$localtax2_tx= get_localtax($tva_tx, 2, $mysoc, $cfstatic->thirdparty, $tva_npr);
									
							$cfstatic->fetch($id_cf[0]);//chargement de la cf
								
							//lien extrafield sont au format string (ex: id,id,id)
							if(isset($xtra[$id_soc]))
							{//devrait toujours etre vrai
								if( empty($cfstatic->array_options["options_reappro"]) )
								{//si vide définir
									$x = $xtra[$id_soc];
								
								}
								else
								{//si est déjà défini mettre à jour
									$x = $xtra[$id_soc].','.$cfstatic->array_options["options_reappro"];
									$x = explode(',', $x);
									$x = array_unique($x);
									$x = implode(',', $x);
									
								}
								
								if($cfstatic->array_options["options_reappro"] != $x)
								{//Mettre à jour les xtrafield seulement si changement
									
									//extrafied qui stocke la liste des commandes clients liées à cette réappro cf
									$sql  = "SELECT fk_object FROM ".MAIN_DB_PREFIX.$cfstatic->table_element."_extrafields";
									$sql .= " WHERE fk_object = '".$cfstatic->id."'";
									// print $sql.'<br>';
									$resql = $db->query($sql);
									
									if( $db->num_rows($resql) == 0  )
									{//creation d'extrafield
								
										$sql  = "INSERT INTO ".MAIN_DB_PREFIX.$cfstatic->table_element."_extrafields (`rowid`, `tms`, `fk_object`, `import_key`, `relance`, `reappro`)";
										$sql .= " VALUES (NULL, CURRENT_TIMESTAMP, '".$cfstatic->id."', NULL, NULL, '".$db->escape($x)."')";
										$resql = $db->query($sql);
										// print $sql.'<br>';
										
									}
									else
									{//mise a jour d'extrafield
										
										$sql = "UPDATE ".MAIN_DB_PREFIX.$cfstatic->table_element."_extrafields SET reappro='".$db->escape($x)."'";
										$sql .= " WHERE fk_object = ".$cfstatic->id;
										// print $sql.'<br>';
										$resql = $db->query($sql);
										
									}
									
								}
								

							}else{
								print '/!\ce message ne devrait jamais s\'afficher : $xtra['.$id_soc.']<br>';
							}
						

								
							$result=$cfstatic->addline(
											$desc,
											$productsupplier->fourn_pu,
											$qty_product,
											$tva_tx,
											$localtax1_tx,
											$localtax2_tx,
											$id_product,
											$idprodfournprice,
											$productsupplier->fourn_ref,
											$remise_percent='',
											'HT',
											$pu_ttc,
											$type,
											$tva_npr,
											'',
											$date_start=null,
											$date_end=null,
											$array_options=0,
											$productsupplier->fk_unit
							);
								
							if( $result <= 0) 
							{
								$error++;
								//insertion quantité produit errreur
								// print 'ligne non insére dans '.$cfstatic->ref.'<br>';
							}
							else
							{
								// print 'ligne insére dans '.$cfstatic->ref.'<br>';
							}

						}
						else
						{
						// print 'pas de prix pour cette quantité';	
						}
					
					}
						
				}
				

			}
		

			$total = count($liste_cf);
				
			
			$s = '';
		
			if($total>1){
				$s = 's';
				}
		
			print_barre_liste($titre='Réapprovisionnement effectué sur '.$total.' commande'.$s.'.',$page='', $file='', $param='', $sortfield='', $sortorder, $center='', $num='', $t='', $picto='img/object_reappro_bw.png',$pictoisfullpath=1);
		
			if($total > 0)
			{//il y a des cf
				print '<table class="noborder" width="100%">'."\n";
				print '<thead><tr class="liste_titre"><td style="width:110px">Ref.</td><td style="width:110px">Tiers</td><td style="width:110px">Montant HT</td><td style="width:110px">Montant TTC</td></tr></thead>';
		
				foreach($liste_cf as $key=>$value)
				{//lister les cf
					
					$cfstatic = new CommandeFournisseur($db);
					$cfstatic->fetch($key);
			
					$thirdpartystatic = new Fournisseur($db);
					$thirdpartystatic->fetch($cfstatic->socid);
					
					print '<tr class="oddeven"><td>'.$cfstatic->getNomUrl(1).'</td><td>'.$thirdpartystatic->getNomUrl(1,'supplier').'</td><td>'.price($cfstatic->total_ht).'</td><td>'.price($cfstatic->total_ttc).'</td></tr>';
					
				}
				print '</table>';
			}
				
		}

		
	}
	elseif($action == 'reappro')
	{//page recap des valeurs à commander et selectionner les fournisseurs

		$produitlistecommandes = array();//liste des commandes clients qui vont vraiement (produit vont passer en commande fournisseur après vérif) être alimentées par le reappro

		
		$total = count($_POST['reappro']);
	
		$reappro_liste = array();//liste de reappro[] pour recalculer
		if($total > 0)
		{//au moins une commande
			$s = '';
			
			if($total>1)
			{
				$s = 's';//un peu de français
			}
			print_barre_liste($titre='Réapprovisionnement de '.$total.' commande'.$s.'.',$page='', $file='', $param='', $sortfield='', $sortorder, $center='', $num='', $total='', $picto='img/object_reappro_bw.png',$pictoisfullpath=1);

			$produits = array();//liste des produits quantité necessaires id du produit=>quantité
			
			$titreReappro = '';
			
			foreach($_POST['reappro'] as $idCommande)
			{//liste des commandes clients séléctionnées
				
				$reappro_liste[] = $idCommande;
				$commandestatic=new Commande($db);
				$commandestatic->fetch($idCommande);
				if($commandestatic->statut == 1)
				{//commande clients validé uniquement
					
					// $titreReappro .=' '.$commandestatic->ref;
					foreach($commandestatic->lines as $key=>$value)
					{//lignes details d'une commande
						
						if($value->product_type == 0 && $value->fk_product != '')
						{//produit uniquement (pas de services)
						
							$productstatic = new product($db);
			
							$productstatic->fetch($value->fk_product);
							if($productstatic->type == 0)
							{//vérif de nouveau si produit non service car parfois dans commande det pas/mal renseigné
								
								$produits[$value->fk_product]+=$value->qty;//incrémenter la quantité totale nécessaire de ce produit
										
								$produitlistecommandes[$value->fk_product][$commandestatic->id] = $commandestatic->getNomUrl(1);

								
							}
							
						}
						
					}
					
				}
				
				// lister les produits
			}
			

				
	
			$produisDesire = $produits;//différencier la demande du traitement
			
			$produits = $obj_b->liste_des_produits_commandes_fournisseur($produits);//soustraire les produits déjà en commande fournisseur
			
			$produits = $obj_b->ordonner_tableau($produits);//reclasser le tableau par références et supprimer les produits hors achat
			
			
			print '<div id="message" style="display:none"></div>'."\n";

			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?action=valider" id="formulaire">'."\n";
			$thead = '<thead>
			<tr class="liste_titre">
				<td style="width:110px;">Ref.</td>
				<td>Qté. désirée</td>
				<td>Qté. en stock</td>
				<td>'.$form->textwithpicto('Qté. en commande', 'Sur les commandes fournisseurs : brouillon, validé, approuvé, commandé.').'</td>
				<td>Limite</td>
				<td>'.$form->textwithpicto('Qté. à commander', 'Lors de la génération des commandes fournisseur le meilleur prix (pour le fournisseur choisi) sera selectionné.<br>Pour ne rien commander saisir comme valeur 0.').'</td>
				<td>Prix par fournisseur</td>
				<td>'.$form->textwithpicto('Commande', 'Commande en consommation directe.').'</td>
				</tr>
			</thead>'."\n";
			print '<table class="noborder" width="100%">'."\n";
			print $thead;
				
			$t=0;//pour répeter le header
			$ready = 0;//si ready = $i alors plus rien a commander		
			$montant_total = 0;//calcul du montant ht
			
			for($i=0;$i<count($produits);$i++)
			{//lister les produits
						
				$id  = $produits[$i][0];
				$qty = $produits[$i][1];
					
				if($qty == 0 )
				{//si rien a commander pour ce produit (vérifier avant de changer la valeur de qty)
					$ready++ ;
				}
					
				//si qté définie par recalculer
				if( isset($_POST['product_'.$id]) )
				{
					$qty = GETPOST('product_'.$id,'int');
				}
					
				$t++;
				if($t == 10)
				{//repeter le header toutes les 10 lignes
					print $thead;
					$t=0;
				}
					
				$productstatic = new product($db);
				$productstatic->fetch($id);
				
				print '<tr class="oddeven">';
					
				$liste = $productstatic->list_suppliers();//liste des fournisseurs pour ce produit
					
				print '<td style="vertical-align:top;">'.$productstatic->getNomUrl(1).'</td>
				<td style="vertical-align:top;">'.$produisDesire[$id].'</td>
				<td style="vertical-align:top;">'.$productstatic->stock_reel.'</td>		
				<td style="vertical-align:top;">'.$obj_b->quantite_en_commande($id).'</td>		
				<td style="vertical-align:top;">'.$productstatic->seuil_stock_alerte.'</td>
				<td style="vertical-align:top;"><input type="number" min="0" pattern="^(\d+)$" value="'.$qty.'" name="product_'.$id.'" id="product_'.$id.'" value="'.$id.'" style="text-align:right;width:60px"></td>
				';
					
				if( count($liste) > 0)
				{//au moins un fournisseur
			
			
					print '<td style="vertical-align:top;">
					<table class="noborder">';
						
					print '<tr class="liste_titre"><td style="width:20px"></td><td style="width:170px" class="right">Fournisseur</td><td style="width:180px" class="right">Prix HT</td><td class="right">Prix Unit. HT</td><td class="right">Prix total HT</td></tr>';
						
					$productsupplierstatic = new ProductFournisseur($db);
									
					
					$opt = '';//moins disant
					if( $productsupplierstatic->find_min_price_product_fournisseur($id, $qty) )
					{
						$opt = $productsupplierstatic->product_fourn_price_id;
					}
					
						
					foreach($liste as $key => $value)
					{//lister les fournisseurs
							
							
						$societestatic = new Societe($db);
						if($societestatic->fetch($value))
						{//cette societé existe
								
							//recherche prix pour ce fournisseur/ quantite
							$sqlOpt = " SELECT rowid, price, quantity FROM `".MAIN_DB_PREFIX."product_fournisseur_price` WHERE `fk_product` = ".$id." AND ".$qty." >= quantity AND fk_soc = ".$value." ORDER BY quantity DESC LIMIT 0,1";
								
							$resql 	= $db->query($sqlOpt);
								
						
							print '<tr>';
												
							$c='';//checked radio
							
							if($db->num_rows($resql) == 1 )
							{//le prix pour cette quantité existe
								$objp = $db->fetch_object($resql);
									
									
								if(isset($_POST['supplier_'.$id]))
								{//check si defini dans recalculer 
									
									if( GETPOST('supplier_'.$id,'int') == $societestatic->id)
									{
										$c = 'checked="checked"';
										$montant_total += ($qty*($objp->price/$objp->quantity));
											
									}
						
								}
									elseif($opt == $objp->rowid )
									{
										$c = 'checked="checked"';
										$montant_total += ($qty*($objp->price/$objp->quantity));
										
									}
									
								print '<td><input type="radio" name="supplier_'.$id.'" value="'.$societestatic->id.'" '.$c.' ></td>';
								print '<td>'.$societestatic->getNomUrl(1).'</td>';
								print '<td class="right"><input type="hidden" name="price_'.$id.'" id="price_'.$id.'" value="'.$objp->rowid.'" >'.price($objp->price).'</td>';
								print '<td class="right">'.price($objp->price/$objp->quantity).'</td>';
								print '<td class="right">'.price($qty*($objp->price/$objp->quantity)).'</td>';
							}
							else
							{//le prix n'existe pas (soit quantité désirée à 0 soit le prix n'est pas défini chez ce fournisseur)
							
								$m = '';//quantité minimum
								// $d = 'disabled="disabled"';//inutile car l'utilisateur autorisé à forcer une autre quantitée
								$p = '';//prix a l'unité
								$pt = '';//prix total
								$sqlOpt = " SELECT  price, quantity FROM `".MAIN_DB_PREFIX."product_fournisseur_price` WHERE `fk_product` = ".$id." AND fk_soc = ".$value." ORDER BY quantity ASC LIMIT 0,1";
								$resql 	= $db->query($sqlOpt);
								
								if($db->num_rows($resql) == 1 )
								{
									$objp = $db->fetch_object($resql);
									$m = ' ('.$objp->quantity.' minimum)';
									// $d = '';
									$p = $objp->price/$objp->quantity;
									$pt = $qty*($objp->price/$objp->quantity);
								}
							
							
								print '<td><input type="radio" name="supplier_'.$id.'" value="'.$societestatic->id.'" ></td>';
								print '<td>'.$societestatic->getNomUrl(1).'</td>';
								print '<td class="right"><div>'.img_warning('Attention','style="vertical-align:middle;"').'<span style="vertical-align:middle"> aucun prix pour cette quantité'.$m.'</span></div></td>';
								print '<td class="right">'.$p.'</td>';
								print '<td class="right">'.$pt.'</td>';
							}
							print '</tr>';
						}
							
					}
					print '</table>
					</td>';
						
				}
				else
				{//aucun fournisseur pour ce produit
					print '<td class="center">
					<div>'.img_warning('Attention','style="vertical-align:middle;"').'<span style="vertical-align:middle"> aucun fournisseur pour ce produit</span></div>
					</td>';
				}
		
					
					
				print '';
					
				print '<td style="vertical-align:top">';
					
				$order_list='';
				if(isset($produitlistecommandes[$productstatic->id]))
				{//lister les commandes liées 
					foreach($produitlistecommandes[$productstatic->id] as $idcom => $lien)
					{
						print  $lien.'<br>';
						$order_list .= $idcom.',';//alimenter le input hidden pour le recalcul
					}
						
				}
					
				print '<input type="hidden" name="order_'.$id.'" id="order_'.$id.'" value="'.$order_list.'">';//lister les id commandes clients en lien
				print'</td>';
				print '</tr>';
			}
				
				
			foreach($reappro_liste as $rk=> $rv)
			{//liste des commandes selectionnées cachées pour un recalcul
				print '<input type="checkbox" value="'.$rv.'" name="reappro[]" checked="checked" style="display:none">';
			}
				
				
			print '</table>'."\n";
				
			//bouton action
			print '<div class="tabsAction">
				<div class="inline-block divButAction" ><a class="butAction"  id="recalculer" onclick="$(\'#formulaire\').attr(\'action\', \''.$_SERVER["PHP_SELF"].'?action=reappro\');$(\'#formulaire\').submit();">Recalculer</a></div>
				<div class="inline-block divButAction" ><a class="butAction"  id="valider" onclick="$(\'#formulaire\').submit();">Passer les commandes pour '.price($montant_total).'€ HT</a></div>
			</div>';
			


			print '</form>'."\n";
			if($i == $ready)
			{//message stock suffisant pour ces commandes clients
				print '<script>'."\n";
				print '$(document).ready(function(){'."\n";

				print '	$( "#message").css("display","block");'."\n";
				print '	$( "#message").html(\''.info_admin("Stock en quantité suffisante.", 0, 0, "1").'<br>\');'."\n";

				print '});'."\n";
				print '</script>'."\n";
					
			}
				
		
		}
	}

}
else
{//affichage liste par défaut
	$liste = $obj_b->liste_des_commandes_client($limit, $offset, $sortfield, $sortorder,  $search_customer, $search_date, $search_user, false);//commandes clients validées (statut 1)

	if( $liste )
	{//au moins une commande client validée

		//formulaire de filtres de recherche
		print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" id="filtres">';
		$total = $obj_b->liste_des_commandes_client($limit, $offset, $sortfield, $sortorder, $search_customer, $search_date, $search_user, true);
	
		print_barre_liste($titre='Liste des commandes à réapprovisionner.', $page, $file=$_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $center='', $num=(count($liste)+1), $total, $picto='img/object_reappro_bw.png', $pictoisfullpath=1, $morehtml='', $morecss='', $limit--);
	
		print '<table class="tagtable liste" width="100%">'."\n";
		
		
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
		print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
		print '<input type="hidden" name="page" value="'.$page.'">';
	
		// Lignes des champs de filtre
		print '<tr class="liste_titre_filter">';

		print '<td class="liste_titre"  >';
		print '</td>';
		
		print '<td class="liste_titre" >';
		print $form->select_dolusers($search_user, 'search_user', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth200');
		print '</td>';
		
		print '<td class="liste_titre">';
		print '<input class="flat" type="text" name="search_customer" size="20" value="'.dol_escape_htmltag($search_customer).'">';
		print '</td>';
		
		print '<td class="liste_titre" align="left">';
		$form->select_date($search_date, 'search_date');
		print '</td>';
		
		print '<td class="liste_titre" align="left">';
		print '</td>';
		
		print '<td class="liste_titre"></td>';

		$searchpicto=$form->showFilterButtons();
		print '<td class="liste_titre" align="middle">';
		print $searchpicto;
		print '</td>';

		print '</tr>';

		

		print '	<tr class="liste_titre">'."\n";
	
	
		if( $sortfield == 'c.ref' && $sortorder == 'DESC'){
			print '		<th class="liste_titre" style="text-align:center;width:130px"><a title="classer par référence" href="' . $_SERVER["PHP_SELF"] . '?sortfield=c.ref&sortorder=ASC" >Réf</a></th>'."\n";
		}else{
			print '		<th class="liste_titre" style="text-align:center;width:130px"><a title="classer par date" href="' . $_SERVER["PHP_SELF"] . '?sortfield=c.ref&sortorder=DESC" >Réf</a></th>'."\n";
		}
		print '		<th class="liste_titre" style="text-align:center;">Auteur</th>'."\n";
		print '		<th class="liste_titre" style="text-align:center;">Client</th>'."\n";
		print '		<th class="liste_titre" style="text-align:center;">Date de commande</th>'."\n";
		print '		<th class="liste_titre" style="text-align:right;width:100px">Montant HT</th>'."\n";
		print '		<th class="liste_titre" style="text-align:center;">Commandes fournisseurs liées</th>'."\n";
		print '		<th class="liste_titre" style="text-align:center"><input type="checkbox" id="checkallactions" name="checkallactions" ></th>'."\n";
		print '	</tr><tbody>'."\n";
		print '</form>'."\n";
		
		//formulaire de selction de commandes clients
		print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" id="formulaire">';
		print '<input type="hidden" name="action" value="reappro">';
		
		$liste_reappro = array();//liste des cfstatic->getnomurl(1) afin de ne pas recharger les meme données
		
		foreach( $liste as $key => $value)
		{//liste des commandes brouillon
		
			$commandestatic=new Commande($db);
			$commandestatic->fetch($value);
				
			print '<tr class="oddeven" >';
			
			//label
			print '	<td>' . $commandestatic->getNomUrl(1) . '</td>';
			
			
			//Auteur
			$userstatic = new User($db);
			$userstatic->fetch($commandestatic->user_author_id);
			print '	<td>' .  $userstatic->getNomUrl(1) . '</td>';
			

			//Client
			$companystatic = new Societe($db);
			$companystatic->fetch($commandestatic->socid);
			print '	<td>' . $companystatic->getNomUrl(1,'',100) . '</td>';
			
			//Date commmande		
			print '	<td style="text-align:center">' . dol_print_date($commandestatic->date,'day') . '</td>';
		
			//Montant HT
			print '	<td style="text-align:right">' . price($commandestatic->total_ht, 0, $langs, 1, -1, -1, $conf->currency) . '</td>';
			
			
			//Cf liées en réppro
			print '	<td style="text-align:right">';
			$cf = $obj_b->commande_fournisseur_liees($commandestatic->id);
			
			if(count($cf)>0)
			{//il existe des cf liées à cette commande client
				
				foreach($cf as $kcf => $vcf)
				{
					
					if(!isset($liste_reappro[$vcf]))
					{//charger le tableau pour les prochaines lignes
						$cfstatic = new CommandeFournisseur($db);
						$cfstatic->fetch($vcf);
						$liste_reappro[$vcf] = $cfstatic->getNomUrl(1);
					}
					print $liste_reappro[$vcf].' ';
				}
			}
			print '</td>'."\n";
			print '	<td style="text-align:center"><input type="checkbox" name="reappro[]" value="'.$commandestatic->id.'" ></td>';
			
			print '</tr>';
			
			
		}
			

		print '</tbody>'."\n";
		print '</table>'."\n";
		
		//bouton action
		print '<div class="tabsAction"><div class="inline-block divButAction" ><a class="butActionRefused"  id="valider">Préparer la réappro.</a></div></div>';
		
		print '</form>'."\n";

		
		//un peu de javascript	
		print'<script type="text/javascript">'."\n";
		print '/**'."\n";
		print ' * Afficher masquer le lien bouton réappro'."\n";
		print ' *'."\n";
		print '*/'."\n";
		print 'function toggle_reappro(){'."\n";
			
		print '	if($("[name=\'reappro[]\']:checked").length < 1){//cacher le bouton submit '."\n";
		print '		$(\'#valider\').attr(\'onclick\',\'\');'."\n";
		print '		$( "#valider" ).removeClass( "butAction" );'."\n";
		print '		$( "#valider" ).addClass( "butActionRefused");'."\n";
		print '	}else{//afficher le bouton submit '."\n";
		print '		$(\'#valider\').attr(\'onclick\',\'$("#formulaire").submit();\');'."\n";	
		print '		$( "#valider" ).removeClass( "butActionRefused" );'."\n";
		print '		$( "#valider" ).addClass( "butAction");'."\n";	   
		print '	}'."\n";
			
			
		print '}'."\n";
		print '<!-- Cliquer en masse -->'."\n";
		print '$(document).ready(function() {'."\n";
		
		print '	$("#checkallactions").click(function() {'."\n";
		print '		if($(this).is(\':checked\')){'."\n";  
		print '			$("[name=\'reappro[]\']").prop(\'checked\', true);'."\n";	
		print '		}'."\n";
		print '		else'."\n";
		print '		{'."\n";          
		print '			$("[name=\'reappro[]\']").prop(\'checked\', false);'."\n";
		print '		}'."\n";		
		print '		toggle_reappro();	'."\n";
		print '	});'."\n";
		print '});'."\n";
	 

		print '<!-- Afficher le bouton submit -->'."\n";
		print '$("[name=\'reappro[]\']").change(function() {'."\n";
		print '	toggle_reappro()'."\n";
		print '});'."\n";

		print '</script>'."\n";

	}
	else
	{//aucune commande 
		print load_fiche_titre('Liste des commandes à réapprovisionner.','',"img/object_reappro_bw.png",1);

		print '<p>Aucune commande client validée.</p>';
		
	}


}




dol_fiche_end();



llxFooter();

$db->close();
/// \endcond	
?>