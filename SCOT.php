<?php

class SCOT {

    function __construct() {
    }

    function handle_request($f3)
    {
	// include ARC2 libraries
	require_once('../libraries/arc2/ARC2.php');
	
	// configure the remote store
	$configuration = array('remote_store_endpoint'  => 'http://vocabulary.curriculum.edu.au/PoolParty/sparql/scot');
	$store = ARC2::getRemoteStore($configuration);
	
	$params = $f3->get('REQUEST');
	
	$LABEL_FRAGMENT = $params['q'];
	$LANGUAGE = 'en';
	
	// the sparql query
	$query = <<<EOB
PREFIX skos:<http://www.w3.org/2004/02/skos/core#>
SELECT DISTINCT ?concept ?searchLabel
WHERE {
  { ?concept skos:prefLabel ?searchLabel. } UNION
  { ?concept skos:altLabel ?searchLabel. }
  FILTER (regex(str(?searchLabel), "$LABEL_FRAGMENT", "i"))
  FILTER (lang(?searchLabel) = "$LANGUAGE")
} LIMIT 10
EOB;
	
	// get the response from the sparql endoint
	$rows = $store->query($query, 'rows');
	
	// sort by ID - can't do this in sparql apparently
	$sorted_rows = array();
	foreach ($rows as $row)
	{
	    $bits = explode('/', $row['concept']);
	    $id = $bits[count($bits) - 1];
	    $sorted_rows[$id] = $row;
	}
	ksort($sorted_rows);
	///
	
	$data = array();
	foreach ($sorted_rows as $row)
	{
	    $datum = array(
		'label' => $row['searchLabel'],
		'value' => $row['concept'],
	    );
	    
	    $data[] = $datum;
	}
	
	header('Content-Type: application/json');
	echo json_encode(array(null, $data));	// lcsuggest pulls the second element - leaving here for simplicity
    }
}

