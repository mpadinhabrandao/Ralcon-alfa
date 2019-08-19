<?php
namespace PhalconRest\Responses;

class CSVResponse extends Response{

	protected $headers = true;

	public function __construct(){
		parent::__construct();
	}

	public function send($records){

		$response = $this->di->get('response');
		$response->setHeader('Content-type', 'application/csv');
header('Content-Disposition: attachment; filename="filename.csv";');
		$response->setHeader('Content-Disposition', 'attachment; filename="'.time().'.csv"');
		$response->setHeader('Pragma', 'no-cache');
		$response->setHeader('Expires', '0');
		
		$handle = fopen('php://output', 'w');

		if($this->headers){
			fputcsv($handle, array_keys($records[0]),";");
		}

		foreach($records as $line){
			fputcsv($handle, $line,";");
		}

		fclose($handle);

		return $this;
	}

	public function useHeaderRow($headers){
		$this->headers = (bool) $headers;
		return $this;
	}

}
