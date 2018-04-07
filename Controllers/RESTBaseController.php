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

}
