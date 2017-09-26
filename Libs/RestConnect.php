<?php
 
namespace PhalconRest\Libs;
use \PhalconRest\Exceptions\HTTPException;

class RestConnect{
    public static function curl( $endpoint, $route = "/", $method = "GET", $get = array(), $post = array() ){
        $config = \Phalcon\DI::getDefault()->get('config');
        if( isset($config[$endpoint]) ){
            $url = rtrim($config[$endpoint]['host'], "/") ."/". ltrim($route, "/");
            $ch = curl_init(); 
            // set url 
	    if( $method == "GET"){
		    if( !empty($get)){
			if(isset($get['ext'])) unset($get['ext']);
		    	$get = http_build_query($get);
		    	$url = "$url?$get";
		    }
	    }else if( $method == "POST"){
		    curl_setopt($ch, CURLOPT_POST, 1);
		    curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($post));
	    }else if( $method == "PUT"){
		    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($get));
	    }else if( $method == "DELETE"){
		    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
	    } 
            curl_setopt($ch, CURLOPT_URL, $url); 

            //return the transfer as a string 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
            // $output contains the output string 
            if(!$output = curl_exec($ch)){
		throw new HTTPException("Error on make Curl request",
			404,
			array(
				'internalCode' => curl_errno($ch),
				'more' => curl_error($ch)."::".$url
			)
		);
            }
            // close curl resource to free up system resources 
            curl_close($ch);    
	    return $output;
        }
    }
}
