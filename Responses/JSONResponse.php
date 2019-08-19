<?php
namespace PhalconRest\Responses;

class JSONResponse extends Response{

	protected $snake = true;
	protected $envelope = true;

	public function __construct(){
		parent::__construct();
	}

	public function send($records, $error=false){
		$response = $this->di->get('response');
		$success = ($error) ? 'ERROR' : 'SUCCESS';

		$request = $this->di->get('request');
		if($request->get('envelope', null, null) === 'false'){
			$this->envelope = false;
		}

		if($this->snake){
			$records = $this->arrayKeysToSnake($records);
		}

		$etag = md5(serialize($records));

		if($this->envelope){
			$message = array();
			$message['_meta'] = array(
				'status' => $success,
				'count' => ($error) ? 1 : count($records)
			);

			if($message['_meta']['count'] === 0){
				$message['records'] = new \stdClass();
			} else {
				$message['records'] = $records;
			}

		} else {
			$response->setHeader('X-Record-Count', count($records));
			$response->setHeader('X-Status', $success);
			$message = $records;
		}

		$response->setContentType('application/json');
		$response->setHeader('E-Tag', $etag);

		if(!$this->head){
			$response->setJsonContent($message);
		}

		$response->send();

		return $this;
	}

	public function convertSnakeCase($snake){
		$this->snake = (bool) $snake;
		return $this;
	}

	public function useEnvelope($envelope){
		$this->envelope = (bool) $envelope;
		return $this;
	}

}
