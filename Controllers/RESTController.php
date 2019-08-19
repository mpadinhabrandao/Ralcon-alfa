<?php
/**
 * File with class RESTController
 */
 
namespace PhalconRest\Controllers;
use \PhalconRest\Exceptions\HTTPException;
use \Phalcon\Http\Request;


/**
 * RestController have the base functions to parse request and set response headers 
 */
class RESTController extends \Phalcon\DI\Injectable{
	
	/** @var int Limit of object list. */
	protected $limit = null;
	
	/** @var int */
	protected $offset = null;
	/**
	 * Fields to searched against
	 * @var array|null
	 */
	protected $searchFields = null;
	
	/**
	 * List fields requested to be returned
	 * @var array|null
	 */
	protected $partialFields = null;
	
	/**
	 * Ordering of object list
	 * @var array
	 */
	protected $orders = array();
	
	/**
	 * This is array with keys: 
	 *    search: List fields with search permissions
	 *    fields: List fields with exhibition permissions
	 *    orders: List fields with permissions for ordering object list
	 * @var array
	 */
	protected $allowedFields = array(
		'search' => array(),
		'partials' => array(),
		'orders' => array()
	);

	
	/**
	 * Construct
	 *
	 * @param bool $parseQueryString Default true
	 */
	public function __construct($parseQueryString = true){
		//parent::__construct();
		$di = \Phalcon\DI::getDefault();
		$this->setDI($di);
		if ($parseQueryString){
			$this->parseRequest($this->allowedFields);
		}
		return;
	}
	

	/**
	 * Parce param f to list fields
	 * ex.:
	 *	&f=fiel
	 *	&f=(fiel),(field2)
	 *
	 * @param string $unparsed 
	 */
	protected function parseFieldsParameters($unparsed){
		$unparsed = explode(',', trim($unparsed, '()'));
		return $unparsed;
	}
	

	/**
	 * Parce param q to search conditions
	 * ex.:
	 *	&q=fiel=10 			>> where field = 10 
	 *	&q=(fiel=10)			>> where field = 10
	 *	&q=(fiel=10),(field2=30)	>> where filed = 10 AND field2 = 30
	 *
	 * @param string $unparsed 
	 */
	protected function parseSearchParameters($unparsed){
		$unparsed = trim($unparsed, '()');
		$splitFields = explode('),(', $unparsed);
		$mapped = array();
		foreach ($splitFields as $field) {
			$splitField = explode('=', $field);
			$mapped[$splitField[0]] = $splitField[1];
		}
		return $mapped;
	}
	

	/**
	 * Parce param o to set ordering object list
	 * ex.:
	 *	&o=fiel=10 			>> ORDER BY field ASC
	 *	&o=+fiel=10			>> ORDER BY field ASC
	 *	&o=-fiel,+field2		>> ORDER BY field DESC, field2 ASC 
	 *
	 * @param string $unparsed 
	 */
	protected function parseOrdersParameters($unparsed){
		$unparsed = trim($unparsed, '()');
		$unparsed = explode(',', $unparsed);
		$arr = array();	
		foreach($unparsed as $cell){
            $cell = trim($cell);
			if ( strpos($cell,'-')===0 ){
				$cell = ltrim($cell,'-');
				$dir = 'DESC';
			} else {
				$cell = ltrim($cell,'+');
				$dir = 'ASC';
			}
			if ( in_array($cell,$this->allowedFields['orders']) ) {
				$arr[$cell] = "$cell $dir";
			}
		}
		return $arr;
	}
	
	/**
	 * Parce Request params: 
	 *	o => ordering
	 *	f => fileds 
	 *	q => search
	 * 
	 * @return bool Return value true;
	 */
	protected function parseRequest(){
		$request = $this->di->get('request');
		$searchParams = $request->get('q', null, null);
		$fields = $request->get('f', null, null);
		$orders = $request->get('o',null, null);

		$this->limit = ($request->get('limit', null, null)) ?: $this->limit;
		$this->offset = ($request->get('offset', null, null)) ?: $this->offset;

		if($searchParams){
			$this->searchFields = $this->parseSearchParameters($searchParams);

			if(array_diff(array_keys($this->searchFields), $this->allowedFields['search'])){
				throw new HTTPException(
					"The fields you specified cannot be searched.",
					401,
					array(
						'dev' => 'You requested to search fields that are not available to be searched.',
						'internalCode' => 'S1000',
						'more' => '' // Could have link to documentation here.
				));
			}
		}

		if($fields){
			$this->partialFields = $this->parseFieldsParameters($fields);

			if(array_diff($this->partialFields, $this->allowedFields['partials'])){
				throw new HTTPException(
					"The fields you asked for cannot be returned.",
					401,
					array(
						'dev' => 'You requested to return fields that are not available to be returned in partial responses.',
						'internalCode' => 'P1000',
						'more' => '' // Could have link to documentation here.
				));
			}
		}
		if($orders){
			$this->orders = $this->parseOrdersParameters($orders);
		}

		return true;
	}

	/**
	 * Set base headres for objec list
	 * 
	 * @return bool Return value true;
	 */
	public function optionsBase(){
		$response = $this->di->get('response');
		$response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, HEAD');
		$response->setHeader('Access-Control-Allow-Origin', $this->di->get('request')->header('Origin'));
		$response->setHeader('Access-Control-Allow-Credentials', 'true');
		$response->setHeader('Access-Control-Allow-Headers', "origin, x-requested-with, content-type");
		$response->setHeader('Access-Control-Max-Age', '86400');
		return true;
	}
	
	
	/**
	 * Set base headres for object
	 * 
	 * @return bool Return value true;
	 */
	public function optionsOne(){
		$response = $this->di->get('response');
		$response->setHeader('Access-Control-Allow-Methods', 'GET, PUT, PATCH, DELETE, OPTIONS, HEAD');
		$response->setHeader('Access-Control-Allow-Origin', $this->di->get('request')->header('Origin'));
		$response->setHeader('Access-Control-Allow-Credentials', 'true');
		$response->setHeader('Access-Control-Allow-Headers', "origin, x-requested-with, content-type");
		$response->setHeader('Access-Control-Max-Age', '86400');
		return true;
	}

	/**
	 * Check if response is array
	 * 
	 * @param array $recordsArray
	 * 
	 * @return array;
	 */
	protected function respond($recordsArray){

		if(!is_array($recordsArray)){
			throw new HTTPException(
				"An error occured while retrieving records.",
				500,
				array(
					'dev' => 'The records returned were malformed.',
					'internalCode' => 'RESP1000',
					'more' => ''
				)
			);
		}

		if(count($recordsArray) < 1){
			return array();
		}

		return array($recordsArray);

	}
	public function save($model, $data = null){
		
		$request = new Request();
		if( is_null($data) ) $data = $request->getJsonRawBody();
		try {
			if($model->save($data)){
				return $model->toArray();
			} else {
				$err = array();
				foreach ($model->getMessages() as $message) {
					throw new HTTPException($message->getMessage(),404,array('internalCode' => $message->getCode()));
				}
				return $err;
			}
		} catch ( \Exception $e ) {
			throw new HTTPException($e->getMessage(),404,array('internalCode' => $e->getCode()));
		}
	}
	public function respondParams($model, $array = array(), $prefix = ''){
		
		if( !empty($prefix) ) $prefix = trim($prefix, '.').'.';
		$attributes = $model->getModelsMetaData()->getAttributes($model);
		
		if( !empty($this->partialFields) ){
			$array['columns'] = $prefix.implode(
						', '.$prefix, 
						array_intersect($attributes, $this->partialFields)
						);
		}
		if( isset($this->limit) && $this->limit >0 )
			$array['limit'] = $this->limit;
			
		if( isset($this->offset) && $this->offset >0 )
			$array['offset'] = $this->offset;
		if( count($this->searchFields) ) {
			foreach ($this->searchFields as $field => $value) {
				$array['conditions'] =	( empty($array['conditions']) ) ? 
							"$prefix$field = :$field:" : 
							"{$array['conditions']} AND $prefix$field = :$field:";
				$array['bind'][$field] = $value;
			}
		}
		if( !empty($this->orders) ){
			$array['order'] = implode($this->orders);
		}
		return $array;		
	}

}
