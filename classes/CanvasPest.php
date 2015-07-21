<?php

/**
 * An object to handle interactions with the Canvas API
 *
 * @author Seth Battis <SethBattis@stmarksschool.org>
 **/
class CanvasPest extends Pest {
	protected $headers;
	
	public function __construct($apiInstanceUrl, $apiAuthorizationToken) {
		parent::__construct($apiInstanceUrl);
		$this->setupToken($apiAuthorizationToken);
	}
	
	public function setupToken($token) {
		if (is_string($token) && strlen($token) > 0) {
			$this->headers['Authorization'] = "Bearer $token";
		} else if ($this->throw_exceptions) {
			throw new CanvasPest_Exception('API authorization token must be a non-zero-length string');
		}
	}
	
	/**
	 * Force maximum response page size, if not already defined
	 **/
	private function preprocessData($data) {
		if (is_array($data) && !array_key_exists('per_page', $data)) {
			$data['per_page'] = CanvasArray::MAXIMUM_PER_PAGE;
		}
		return $data;
	}
	
	/**
     * {@inheritDoc} extended by CanvasPest to format the HTTP query parameters with non-indexed array elements (so http://example.com/api/v1/foo?bar[]=1&bar[]=2, rather than http://example.com/api/v1/foo?bar[0]=1&bar[1]=2).
     */
    public function prepData($data)
    {
        if (is_array($data)) {
            $multipart = false;

            foreach ($data as $item) {
                if (is_string($item) && strncmp($item, "@", 1) == 0 && is_file(substr($item, 1))) {
                    $multipart = true;
                    break;
                }
            }

            return ($multipart) ? $data : preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', http_build_query($data));
        } else {
            return $data;
        }
    }

	/**
	 * {@inheritDoc} extended by CanvasPest to include the API access token in the Authorization header.
	 **/
	protected function prepHeaders($headers) {
		return parent::prepHeaders(array_merge($this->headers, $headers));
	}
	
	/**
	 * @return CanvasObject | CanvasArray
	 **/
	protected function postprocessResponse($path, $response) {
		if(preg_match('%^.*/\d+/?$%', $path)) {
			return new CanvasObject($response, $this);
		} else {
			return new CanvasArray($response, $this);
		}
	}
	
	/**
	 * @return CanvasObject | CanvasArray
	 **/
	public function get($path, $data = array(), $headers = array()) {
		return $this->postprocessResponse(
			$path,
			parent::get($path, $this->preprocessData($data), $headers)
		);
	}
	
	/**
	 * @return CanvasObject | CanvasArray
	 **/
	public function post($path, $data = array(), $headers = array()) {
		return $this->postprocessResponse(
			$path,
			parent::post($path, $this->preprocessData($data), $headers)
		);
	}
	
	/**
	 * @return CanvasObject | CanvasArray
	 **/
	public function put($path, $data = array(), $headers = array()) {
		return $this->postprocessResponse(
			$path,
			parent::put($path, $this->preprocessData($data), $headers)
		);
	}
		
	/**
	 * @return CanvasObject | CanvasArray
	 **/
	public function delete($path, $headers = array()) {
		return $this->postprocessResponse(
			$path,
			parent::delete($path, $headers)
		);
	}

	/**
	 * @deprecated The Canvas API does not currently support PATCH calls
	 * @throws CanvasPest_Exception UNSUPPORTED_METHOD All calls to this method will cause an exception
	 **/	
	public function patch($path, $data = array(), $headers = array()) {
		throw new CanvasPest_Exception(
			'The Canvas API does not support the PATCH method',
			CanvasPest_Exception::UNSUPPORTED_METHOD
		);
	}
}

/**
 * All exceptions thrown by the CanvasPest object
 *
 * @author Seth Battis <SethBattis@stmarksschool.org>
 **/	
class CanvasPest_Exception extends Exception {
	/**
	 * @const UNSUPPORTED_METHOD The API access method is not supported by the Canvas API
	 **/
	const UNSUPPORTED_METHOD = 1;
}

?>