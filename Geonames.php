<?php

// include ARC2 libraries
require_once __DIR__ . '/vendor/autoload.php';

class Geonames {

    public $term;
    public $data;

	public $states = array(
			'New South Wales' 				=> 'NSW',
			'Queensland'					=> 'Qld',
			'Victoria'						=> 'Vic',
			'Western Australia'				=> 'WA',
			'South Australia'				=> 'SA',
			'Northern Territory'			=> 'NT',
			'Tasmania'						=> 'Tas',
			'Australian Capital Territory'	=> 'ACT',
	);
	
    function __construct()
	{
	
    }

    function handle_request($f3)
    {
		$params = $f3->get('REQUEST');
		
		if (!empty($params['q']))
		{
			$this->term = $params['q'];
			
			if (!empty($params['featureClass']))
			{
				$fcs = $params['featureClass'];
			}
			else
			{
				// default
				$fcs = 'HLPSTU';
			}
			
			$fc_str = implode('&featureClass=', str_split($fcs));
			$username = 'utseresearch';	//	'hs74b6G2p2898pa'
			$country = 'AU';
			$lang = 'en';
			$maxRows = 25;
			$orderBy = 'relevance';
			
			$query_string = "q={$this->term}&featureClass={$fc_str}&country={$country}&lang={$lang}&maxRows={$maxRows}&orderBy={$orderBy}&username={$username}";
			
//			die($query_string);
			
			$ch = curl_init();
			$options = array(
				CURLOPT_URL => 'http://api.geonames.org/searchJSON?'. $query_string,
				CURLOPT_RETURNTRANSFER => 1,
			);
			curl_setopt_array($ch, $options);
			$response = curl_exec($ch);
			curl_close($ch);
			
			$data = json_decode($response);

			$output = array();
			foreach ($data->geonames as $key => $node)
			{
				$outnode = array();
				$outnode['value'] = "http://www.geonames.org/{$node->geonameId}";
				$outnode['label'] = $node->name;
				if ($node->toponymName != $node->name)
				{
					$outnode['label'] .= " ({$node->toponymName})";
				}
				if ($this->_abbreviateState($node->adminName1))
				{
					$outnode['label'] .= ', '. $this->_abbreviateState($node->adminName1);
				}
				$outnode['label'] .= " [{$node->fcodeName}]";
				$output[] = $outnode;
			}
			
			$this->data = $output;
		}
		else
		{
			$f3->status(400);
			die('Expected URL parameter: q');
		}
    }
    
	private function _abbreviateState($statename)
	{
		if (isset($this->states[$statename]))
		{
			return $this->states[$statename];
		}
		else
		{
			return $statename;
		}
	}
	
    public function render()
    {
		header('Content-Type: application/json');
		echo json_encode(array($this->term, $this->data), JSON_PRETTY_PRINT);
    }
}

