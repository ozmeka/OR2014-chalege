<?php

require 'vendor/autoload.php';
require_once 'SCOT.php';

class SCOT_LCSubjectHeadings {

    public $term;
    public $scot_data;
    public $lc_subj_data;
    public $data;

    function __construct() {
    }
    
    function handle_request($f3)
    {
	// we're calling two web services here.  snappiness is important so we want to make each request non-blocking.
	// rather than hack up the (blocking) ARC code which calls SCOT, i've wrapped its blocking call in a non-blocking
	// call to Library of Congress
	
	$params = $f3->get('REQUEST');
	$this->term = $params['q'];
	
	// prepare the library of congress subject query
	$request = new \cURL\Request('http://id.loc.gov/authorities/subjects/suggest?q='. $this->term);
	$request->getOptions()
	    ->set(CURLOPT_TIMEOUT, 5)
	    ->set(CURLOPT_RETURNTRANSFER, true);
	    
	$o = $this;
	$request->addListener('complete', function (\cURL\Event $event) use (&$o) {
	    $response = $event->response;
	    $feed = json_decode($response->getContent(), true);
	    $o->lc_subj_data = $feed;	// populate LC result
	});
	
	// while waiting for LC response, handle SCOT lookup
	while ($request->socketPerform())
	{
	    if (empty($scot))
	    {
		$scot = new SCOT();
		$scot->handle_request($f3);
		$this->scot_data = $scot->data;  // populate SCOT result
	    }
	    
	    $request->socketSelect();
	}
    }

    function format_lc_data($lc_data, $count = 5)
    {
	$data = array();
	for ($i = 0; $i < $count; $i++)
	{
	    $datum = array(
		'label' => $lc_data[1][$i],
		'value' => $lc_data[3][$i],
	    );
	    
	    $data[] = $datum;
	}
	
	return $data;
    }
    
    function render()
    {
	$blended_data = array();
	
	$i = 0;
	foreach ($this->scot_data as $datum)
	{
	    if ($i >= 5)
	    {
		break;
	    }
	    
	    $blended_data[] = $datum;
	    ++$i;
	}
	
	$lc_cleaned = $this->format_lc_data($this->lc_subj_data);
	foreach ($lc_cleaned as $datum)
	{
	    if ($i >= 10)
	    {
		break;
	    }
	    
	    $blended_data[] = $datum;
	    ++$i;
	}

//	print_r($blended_data);
	
	header('Content-Type: application/json');
	echo json_encode(array($this->term, $blended_data));	// lcsuggest pulls the second element - leaving here for simplicity
	
    }
}

