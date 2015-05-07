<?php

// include ARC2 libraries
require_once __DIR__ . '/vendor/autoload.php';

class SCOT {

    public $term;
    public $data;

    function __construct() {
    }

    function handle_request($f3)
    {

		// configure the remote store
		$configuration = array('remote_store_endpoint'  => 'http://vocabulary.curriculum.edu.au/PoolParty/sparql/scot');
		$store = ARC2::getRemoteStore($configuration);
		
		$params = $f3->get('REQUEST');
		
		if (!empty($params['q']))
		{
			$this->term = $LABEL_FRAGMENT = $params['q'];
		}
		else
		{
			$f3->status(400);
			die('Expected URL parameter: q');
		}
		
		$LANGUAGE = 'en';
		
		// the sparql query
		$query = <<<EOB
PREFIX skos:<http://www.w3.org/2004/02/skos/core#>
SELECT ?concept ?searchLabel
WHERE {
  { ?concept skos:prefLabel ?searchLabel. } UNION
  { ?concept skos:altLabel ?searchLabel. }
  FILTER (regex(str(?searchLabel), "$LABEL_FRAGMENT", "i"))
  FILTER (lang(?searchLabel) = "$LANGUAGE")
}
EOB;
	
		// get the response from the sparql endoint
		$rows = $store->query($query, 'rows');
	
		$data = array();
		foreach ($rows as $row)
		{
			$datum = array(
			'label' => $row['searchLabel'],
			'value' => $row['concept'],
			);
			
			$data[] = $datum;
		}
		
		$this->data = $data;
    }
    
    public function render()
    {
//	header('Content-Type: application/json');
	echo json_encode(array($this->term, $this->data), JSON_PRETTY_PRINT);	// lcsuggest pulls the second element - leaving here for simplicity
    }
}

