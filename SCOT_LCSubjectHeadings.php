<?php

require_once __DIR__ . '/vendor/autoload.php';
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
		
		if (!empty($params['q']))
		{
			$this->term = $LABEL_FRAGMENT = $params['q'];
		}
		else
		{
			$f3->status(400);
			die('Expected URL parameter: q');
		}
		
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
				$this->scot_data = array('sourceTitle' => 'SCOT') + $scot->data;  // populate SCOT result
			}
			
			$request->socketSelect();
		}
    }

    function format_lc_data($lc_data)
    {
		$data = array('sourceTitle' => 'LC Subjects');
		
		$i = 0;
		while (isset($lc_data[1][$i]))
		{
			$datum = array(
				'label' => $lc_data[1][$i],
				'value' => $lc_data[3][$i],
			);
			
			$data[] = $datum;
			
			++$i;
		}
		
		return $data;
    }
    
    function render()
    {
		header('Content-Type: application/json');
		echo json_encode(array($this->term, $this->scot_data, $this->format_lc_data($this->lc_subj_data)));	// lcsuggest pulls the second element - multi types take second and any additional elements as result sources
    }
}

