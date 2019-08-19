<?php
 
namespace PhalconRest\Libs;

use \Phalcon\Logger;
use \Phalcon\Logger\Adapter\File as FileAdapter;
use \PhalconRest\Exceptions\HTTPException;
use \Phalcon\Http\Request;
use \Phalcon\Http\Client\Request as HttpClient;
use \Phalcon\Http\Client\Exception as HttpClientException;

class RestConnect {

    public static function curl($endpoint, $route = "/", $method = "GET", $get = array(), $post = array()) {
        $ch = self::curlGenerate($endpoint, $route, $method, $get, $post);
        $output = false;
        if( $ch !== false  ){
            // $output contains the output string 
            
            if(!$output = curl_exec($ch)){
                throw new HTTPException("Error on make Curl request",
                    404,
                    array(
                        'internalCode' => curl_errno($ch),
                        'more' => curl_error($ch)."::".$endpoint . $route
                        )
                );
            }
            // close curl resource to free up system resources 
            curl_close($ch);
        }
        return $output;
    }

    public static function curlGenerate($endpoint, $route = "/", $method = "GET", $get = array(), $post = array()) {
        $ch = false;
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
                // curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($post));
                curl_setopt($ch, CURLOPT_POSTFIELDS,($post));
            }else if( $method == "PUT"){
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($get));
            }else if( $method == "DELETE"){
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            }
            
            curl_setopt($ch, CURLOPT_URL, $url); 
            $header = array(
                'Accept: application/json',
            );

            $request = new Request();
            $headers = $request->getHeaders();
            if (isset($headers['Authorization']) && !empty($headers['Authorization'])) {
                $header[] = 'Authorization: Bearer '. str_replace('Bearer ','',$headers['Authorization']);
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        }
        return $ch;
    }

    /**
     * Method to check if endpoint is configured.
     * If yes, return it.
     */
    private static function getEndpointFromConfig($endpoint) {
        /**
         * Load endpoint configs.
         */
        $config = \Phalcon\DI::getDefault()->get('config');
        $host = $config[$endpoint]['host'];

        return $host ? $host : false;
    }

    /**
     * Make an Http Request to a known endpoint
     * which is defined in config.ini
     */
    public static function curlWithConfiguredEndpoint($endpoint, $route = "", $method = "GET", $get = array(), $post = array()) {
        if (!$endpoint) return "Endpoint not passed as argument";
        /**
         * Check if endpoint is configured.
         */
        $host = RestConnect::getEndpointFromConfig($endpoint);
        if ($host == false) return "Endpoint not configured in config.ini";

        /**
         * Handle $route left slash.
         */
        if ($route) $route = ltrim($route, "/");

        /**
         * Start building request.
         */
        $req = HttpClient::getProvider();
        $req->setBaseUri($host);
        $req->header->set('Accept', 'application/json');
        $req->header->set('Content-Type', 'application/json');

        /**
         * Auth middleware.
         * ---
         * If JWT is set, forward it.
         */
        $request = new Request();
        $headers = $request->getHeaders();
        if (isset($headers['Authorization']) && !empty($headers['Authorization'])) {
            $req->header->set("Authorization", 'Bearer '. str_replace('Bearer ', '', $headers['Authorization']));
        }

        /**
         * Set method.
         */
        $method = strtoupper($method);
        switch ($method) {
            /**
             * GET.
             */
            case "GET":
                // Unset ext. Private info.
                if (isset($get['ext'])) unset($get['ext']);
                $response = $req->get(
                    $route,
                    $get
                );
                break;
            
            /**
             * POST.
             */
            case "POST":
                $response = $req->post(
                    $route,
                    json_encode($post)
                );
                break;

            /**
             * DELETE.
             */
            case "DELETE":
                $response = $req->delete(
                    $route
                );
                break;
            /**
             * Default.
             */
            default:
                return "Method not implemented...";
        }

        /**
         * Response.
         */
        return $response->body;
    }

    /**
     * Get dump_safe_url from dumpServer.
     */
    public static function curlDumpServer($endpoint, $route, $data) {
        $logger = \Phalcon\DI::getDefault()->get('logger');
        /**
         * Start building request.
         */
        $req = HttpClient::getProvider();
        $req->setBaseUri($endpoint);
        /**
         * Set headers.
         */
        $req->header->set('Accept', 'application/json');
        $req->header->set('Content-Type', 'application/json');

        /**
         * Make the request.
         */
        $response = $req->post(
            $route, 
            json_encode($data)
        );

        /**
         * Return response.
         */
		//$logger->debug(">>> [core][RestConnect][curlDumpServer] - Response: " . print_r($response, true));
        return $response->body;
    }
}
