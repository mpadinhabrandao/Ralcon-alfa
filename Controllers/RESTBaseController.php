<?php
namespace PhalconRest\Controllers;
use \PhalconRest\Exceptions\HTTPException;

class RESTBaseController extends \PhalconRest\Controllers\RESTController{

	protected $primaryKey = 'id';
	
	public function get(){
		$list = array();
		
		$params = $this->respondParams($this->model);
		$tmp = $this->model->find($params);
		if( $tmp->count() ){
			$list = $tmp->toArray();	
		}
		return $list;
	}

	public function getOne($id){

	        $array = array();	
		$array['conditions'] = "{$this->primaryKey} = :{$this->primaryKey}:";
		$array['bind'][$this->primaryKey] = $id;

		$array = $this->respondParams($this->model, $array);
		
		$itm = $this->model->findFirst($array);
		if( $itm ){
			return $itm->toArray();
		}else{
			throw new HTTPException("Item not found",404,array('internalCode' => "100002"));
		}
	}
	


	public function post(){
		return $this->save($this->model);
	}

	public function put($id){
		
		$array['conditions'] = "{$this->primaryKey} = :{$this->primaryKey}:";
		$array['bind'][$this->primaryKey] = $id;
		
		$itm = $this->model->findFirst($array);
		if( $itm ){
			return $this->save($itm);
		}else{
			throw new HTTPException("Item not found",404,array('internalCode' => "100002"));
		}
		
	}

	public function delete($id){
		
		$array['conditions'] = "{$this->primaryKey} = :{$this->primaryKey}:";
		$array['bind'][$this->primaryKey] = $id;
		
		$itm = $this->model->findFirst($array);
		if( $itm ){
			if( $itm->delete() ){
				return array(true);
			} else {
				$err = array();
				foreach ($itm->getMessages() as $message) {
					throw new HTTPException($message->getMessage(),404,array('internalCode' => $message->getCode()));
				}
				return $err;
			}
		}else{
			throw new HTTPException("Item not found",404,array('internalCode' => "100002"));
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
