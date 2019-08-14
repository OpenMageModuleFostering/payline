 <?php
/**
 * Class used as a datasource to display Payline product categories
 */
class Monext_Payline_Model_Datasource_Paylineproductcategories
{
	private $data = array(
        	1 => 'Informatique (matériel et logiciel)',
        	2 => 'Electronique',
        	3 => 'Téléphone',
        	4 => 'Electroménager',
        	5 => 'Habitat et jardin',
        	6 => 'Mode',
        	7 => 'Produit de beauté',
        	8 => 'Bijouterie',
        	9 => 'Sport',
        	10 => 'Loisirs',
        	11 => 'Automobiles / motos',
        	12 => 'Ammeublement',
        	13 => 'Enfants',
        	14 => 'Jeux video',
        	15 => 'Jouets',
        	16 => 'Animaux',
        	17 => 'Alimentation',
        	18 => 'Cadeaux',
        	19 => 'Spectacles',
        	20 => 'Voyages',
        	21 => 'Enchères',
        	22 => 'Services aux particuliers',
        	23 => 'Services aux professionnels'
        );
	
	public function toOptionArray()
    {
    	$ret = array();
    	$n=1;
    	for($n=1;$n<sizeof($this->data)+1;$n++){
    		$ret[$n] = array('value' => $n, 'label' => $this->data[$n]);
    	}
    	return $ret;
    }
    
    public function getLabelbyId($id){
    	return $this->data[$id];
    }
}
