<?php

namespace PhalconRest\Models;

use \PhalconRest\Exceptions\HTTPException;

class RESTModel extends Model{

    public function save($data = NULL, $whiteList = NULL){
		try{	
			if(parent::save($data)){
				return $this;
			} else {
				foreach ($this->getMessages() as $message) {
					$err = array(
						'type' => $message->getType(),
						'message' => $message->getMessage(),
						'filed' => $message->getField(),
						'model' => $message->getModel(),
						'code' => $message->getCode(),
					);
					throw new HTTPException($err['message'],404,array('more' => $err, 'internalCode' => $err['code']));
				}
			}               
		
		} catch ( \PDOException $e ) {
			throw new HTTPException($e->getMessage(),404,array('internalCode' => $e->getCode()));
		}
    }

}
