<?php

/**
 * An object to represent a list of Canvas Objects returned as a response from
 * the Canvas API.
 *
 * @author Seth Battis <SethBattis@stmarksschool.org>
 **/
class CanvasArray implements Iterator, ArrayAccess {
	
	/**
	 * @const MAXIMUM_PER_PAGE The maximum supported number of responses per page
	 **/
	const MAXIMUM_PER_PAGE = 100;
	
	protected $api;
	private $endpoint = null;
	private $pagination = array();
	private $data = array();
	private $page = null;
	private $perPage = null;
	private $key = null;
	
	public function __construct($jsonResponse, $canvasPest) {
		$this->api = $canvasPest;

		/* parse Canvas page links */
		if (preg_match_all('%<([^>]*)>\s*;\s*rel="([^"]+)"%', $this->api->lastHeader('link'), $links, PREG_SET_ORDER)) {
			foreach ($links as $link)
			{
				$this->pagination[$link[2]] = new CanvasPageLink($link[1], $link[2]);
			}
		} else {
			$this->pagination = array(); // might only be one page of results
		}
		
		/* locate ourselves */
		$this->page = $this->pagination[CanvasPageLink::CURRENT]->getPageNumber();
		$this->key = $this->pageNumberToKey($this->page);

		/* parse the JSON response string */
		$key = $this->key;
		foreach (json_decode($jsonResponse, true) as $item) {
			$this->data[$key++] = new CanvasObject($item, $this->api);
		}
	}
	
	private function pageNumberToKey($pageNumber) {
		return ($pageNumber - 1) * $this->pagination[CanvasPageLink::CURRENT]->getPerPage();
	}
	
	private function keyToPageNumber($key) {
		return ((int) ($key / $this->pagination[CanvasPageLink::CURRENT]->getPerPage())) + 1;
	}
	
	private function requestPageNumber($pageNumber, $forceRefresh = false) {
		if (!isset($this->data[$this->pageNumberToKey($pageNumber)]) || $forceRefresh) {
			$page = $this->api->get(
				$this->pagination[CanvasPageLink::CURRENT]->getEndpoint(),
				array(
					CanvasPageLink::PARAM_PAGE_NUMBER => $pageNumber,
					CanvasPageLink::PARAM_PER_PAGE => $this->pagination[CanvasPageLink::CURRENT]->getPerPage()
				)
			);
			$this->data = array_replace($this->data, $page->data);
		}
	}
	
	private function rewindToPageNumber($pageNumber, $forceRefresh = false) {
		$page = null;
		$key = $this->pageNumberToKey($pageNumber);
		if ($forceRefresh || !isset($this->data[$key])) {
			$page = $this->requestPageNumber($pageNumber, $forceRefresh);
		}
		
		$this->key = $key;
		$this->page = $pageNumber;
		$this->pagination[CanvasPageLink::PREV] = new CanvasPageLink(
			$pageNumber,
			$this->pagination[CanvasPageLink::FIRST],
			CanvasPageLink::PREV
		);
		$this->pagination[CanvasPageLink::NEXT] = new CanvasPageLink(
			$pageNumber,
			$this->pagination[CanvasPageLink::FIRST],
			CanvasPageLink::NEXT
		);
	}
		
	/****************************************************************************
	 ArrayAccess methods */
	
	public function offsetExists($offset) {
		$lastPageNumber = $this->pagination[CanvasPageLink::LAST]->getPageNumber();
		if ($this->keyToPageNumber($offset) == $lastPageNumber && !isset($this->data[$this->pageNumberToKey($lastPageNumber)])) {
			$this->requestPageNumber($lastPageNumber);
		}
		return isset($this->data[$offset]) || ($offset >= 0 && $offset < $this->pageNumberToKey($lastPageNumber));
	}
	
	public function offsetGet($offset) {
		if ($this->offsetExists($offset) && !isset($this->data[$offset])) {
			$this->requestPageNumber($this->keyToPageNumber($offset));
		}
		return $this->data[$offset];
	}
	
	public function offsetSet($offset, $value) {
		throw new CanvasArray_Exception(
			'Canvas responses are immutable',
			CanvasArray_Exception::IMMUTABLE
		);
	}
	
	public function offsetUnset($offset) {
		throw new CanvasArray_Exception(
			'Canvas responses are immutable',
			CanvasArray_Exception::IMMUTABLE
		);
	}
	
	/****************************************************************************/
	
	/****************************************************************************
	 Iterator methods */
	
	public function current() {
		if (!isset($this->data[$this->key])) {
			$this->requestPageNumber($this->keyToPageNumber($this->key));
		}
		return $this->data[$this->key];
	}
	
	public function key() {
		return $this->key;
	}
	
	public function next() {
		$this->key++;
	}
	
	public function rewind() {
		$this->key = 0;
	}
	
	public function valid() {
		return ($this->offsetExists($this->key));
	}
	
	/****************************************************************************/
}

/**
 * All exceptions thrown by CanvasArray
 *
 * @author Seth Battis <SethBattis@stmarksschool.org>
 **/	
class CanvasArray_Exception extends CanvasObject_Exception {
	// index starts at 200;
}
	
?>