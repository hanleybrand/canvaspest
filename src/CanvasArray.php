<?php

/** smtech\CanvasPest\CanvasArray */

namespace smtech\CanvasPest;

/**
 * An object to represent a list of Canvas Objects returned as a response from
 * the Canvas API.
 *
 * @author Seth Battis <SethBattis@stmarksschool.org>
 **/
class CanvasArray implements \Iterator, \ArrayAccess, \Serializable
{

    /** The maximum supported number of responses per page */
    const MAXIMUM_PER_PAGE = 100;

    /** @var CanvasPest $api Canvas API (for paging through the array) */
    protected $api;

    /**
     * @var string $endpoint API endpoint whose response is represented by this
     *      object
     **/
    private $endpoint = null;

    /**
     * @var CanvasPageLink[] $pagination The canonical (first, last, next,
     *      prev, current) pages relative to the current page of responses
     **/
    private $pagination = [];

    /**
     * @var array Cached pagination per each page response
     */
    private $paginationPerPage = [];

    /** @var CanvasObject[] $data Backing store */
    private $data = [];

    /** @var int $page Page number corresponding to current $key */
    private $page = null;

    /** @var int $key Current key-value of iterator */
    private $key = null;



    /**
     * Construct a CanvasArray
     *
     * @param string $jsonResponse A JSON-encoded response array from the
     *                             Canvas API
     * @param CanvasPest $canvasPest An API object for making pagination calls
     **/
    public function __construct($jsonResponse, $canvasPest)
    {
        $this->api = $canvasPest;

        $this->pagination = $this->parsePageLinks();

        /* locate ourselves */
        if (isset($this->pagination[CanvasPageLink::CURRENT])) {
            $this->page = $this->pagination[CanvasPageLink::CURRENT]->getPageNumber();
            $this->key = $this->pageNumberToKey($this->page);
            $this->paginationPerPage[$this->page] = $this->pagination;
        }

        /* parse the JSON response string */
        $key = $this->key;
        foreach (json_decode($jsonResponse, true) as $item) {
            $this->data[$key++] = new CanvasObject($item);
        }
    }

    /**
     * Parse the API response link headers into pagination information
     *
     * @param boolean|string[] $headers (Optional, defaults to `$this->api->lastHeader('link')`)
     * @return CanvasPageLink[]
     */
    protected function parsePageLinks($headers = false)
    {
        $pagination = [];
        if (!$headers) {
            $headers = $this->api->lastHeader('link');
        }

        /* parse Canvas page links */
        if (preg_match_all('%<([^>]*)>\s*;\s*rel="([^"]+)"%', $headers, $links, PREG_SET_ORDER)) {
            foreach ($links as $link) {
                $pagination[$link[2]] = new CanvasPageLink($link[1], $link[2]);
            }
        }

        return $pagination;
    }

    /**
     * Convert a page number to an array key
     *
     * @param int $pageNumber 1-indexed page number
     *
     * @return int|false
     **/
    protected function pageNumberToKey($pageNumber)
    {
        if (isset($this->pagination[CanvasPageLink::CURRENT]) and is_numeric($pageNumber)) {
            return ($pageNumber - 1) * $this->pagination[CanvasPageLink::CURRENT]->getPerPage();
        }
        return false;
    }

    /**
     * Request a page of responses from the API
     *
     * A page of responses will be requested if it appears that that page has
     * not yet been loaded (tested by checking if the initial element of the
     * page has been initialized in the $data array).
     *
     * @param int $pageNumber Page number to request
     * @param bool $forceRefresh (Optional) Force a refresh of backing data,
     *                           even if cached (defaults to `FALSE`)
     *
     * @return bool `TRUE` if the page is requested, `FALSE` if it is already
     *                     cached (and therefore not requested)
     **/
    protected function requestPageNumber($pageNumber, $forceRefresh = false)
    {
        if (!isset($this->data[$this->pageNumberToKey($pageNumber)]) || ($forceRefresh && isset($this->api))) {
            // assume one page if no pagination (and already loaded)
            if (isset($this->pagination[CanvasPageLink::CURRENT])) {
                $params = $this->pagination[CanvasPageLink::CURRENT]->getParams();
                $params[CanvasPageLink::PARAM_PAGE_NUMBER] = $pageNumber;
                $page = $this->api->get($this->pagination[CanvasPageLink::CURRENT]->getEndpoint(), $params);
                $this->data = array_replace($this->data, $page->data);
                $pagination = $this->parsePageLinks();
                $this->paginationPerPage[$pagination[CanvasPageLink::CURRENT]->getPageNumber()] = $pagination;
                return true;
            }
        }
        return false;
    }

    /**
     * Request all pages from API
     *
     * This stores the entire API response locally, in preparation for, most
     * likely, serializing this object.
     *
     * @param bool $forceRefresh (Optional) Force a refresh of backing data,
     *                           even if cached (defaults to `FALSE`)
     *
     * @return void
     */
    protected function requestAllPages($forceRefresh = false)
    {
        $_page = $this->page;
        $_key = $this->key;

        $nextPageNumber = false;
        if (isset($this->pagination[CanvasPageLink::NEXT])) {
            $nextPageNumber = $this->pagination[CanvasPageLink::NEXT]->getPageNumber();
        }

        /* welp, here goes... let's hope we have a next page! */
        while ($nextPageNumber !== false) {
            $this->requestPageNumber($nextPageNumber, $forceRefresh);
            if (isset($this->paginationPerPage[$nextPageNumber][CanvasPageLink::NEXT])) {
                $nextPageNumber = $this->paginationPerPage[$nextPageNumber][CanvasPageLink::NEXT]->getPageNumber();
            } else {
                $nextPageNumber = false;
            }
        }

        $this->page = $_page;
        $this->key = $_key;
    }

    /***************************************************************************
     * ArrayObject methods
     */

    /**
     * Get the number of CanvasObjects in the Canvas response
     *
     * @return int
     *
     * @see http://php.net/manual/en/arrayobject.count.php ArrayObject::count
     **/
    public function count()
    {
        $this->requestAllPages();
        return count($this->data);
    }

    /**
     * Creates a copy of the CanvasArray
     *
     * @return CanvasObject[]
     *
     * @see http://php.net/manual/en/arrayobject.getarraycopy.php
     *      ArrayObject::getArrayCopy
     **/
    public function getArrayCopy(): array
    {
        $this->requestAllPages();
        return $this->data;
    }

    /***************************************************************************
     * ArrayAccess methods
     */

    /**
     * Whether an offset exists
     *
     * @param int|string $offset
     *
     * @return bool
     *
     * @see http://php.net/manual/en/arrayaccess.offsetexists.php
     *      ArrayAccess::offsetExists
     **/
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        if (!isset($this->data[$offset])) {
            $this->requestAllPages();
        }
        return isset($this->data[$offset]);
    }

    /**
     * Offset to retrieve
     *
     * @param int|string $offset
     *
     * @return CanvasObject|null
     *
     * @see http://php.net/manual/en/arrayaccess.offsetexists.php
     *      ArrayAccess::offsetGet
     **/
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        if (!isset($this->data[$offset])) {
            $this->requestAllPages();
        }
        return $this->data[$offset];
    }

    /**
     * Assign a value to the specified offset
     *
     * @param int|string $offset
     * @param CanvasObject $value
     *
     * @return void
     *
     * @throws CanvasArray_Exception IMMUTABLE All calls to this method will cause an exception
     *
     * @deprecated CanvasObject and CanvasArray responses are immutable
     *
     * @see http://php.net/manual/en/arrayaccess.offsetset.php
     *      ArrayAccess::offsetSet
     **/
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        throw new CanvasArray_Exception(
            'Canvas responses are immutable',
            CanvasArray_Exception::IMMUTABLE
        );
    }

    /**
     * Unset an offset
     *
     * @param int|string $offset
     *
     * @return void
     *
     * @throws CanvasArray_Exception IMMUTABLE All calls to this method will
     *         cause an exception
     *
     * @deprecated CanvasObject and CanvasArray responses are immutable
     *
     * @see http://php.net/manual/en/arrayaccess.offsetunset.php
     *      ArrayAccess::offsetUnset
     **/
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new CanvasArray_Exception(
            'Canvas responses are immutable',
            CanvasArray_Exception::IMMUTABLE
        );
    }

    /**************************************************************************/

    /**************************************************************************
     * Iterator methods
     */

    /**
     * Return the current element
     *
     * @return CanvasObject
     *
     * @see http://php.net/manual/en/iterator.current.php Iterator::current
     **/
    #[\ReturnTypeWillChange]
    public function current()

    {
        return $this->data[$this->key];
    }

    /**
     * Return the key of the current element
     *
     * @return int
     *
     * @see http://php.net/manual/en/iterator.key.php Iterator::key
     **/
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->key;
    }


    /**
     * Move forward to next element
     *
     * @return void
     *
     * @see http://php.net/manual/en/iterator.next.php Iterator::next
     **/
    #[\ReturnTypeWillChange]
    public function next()
    {
        $this->key++;
    }

    /**
     * Rewind the iterator to the first element
     *
     * @return void
     *
     * @see http://php.net/manual/en/iterator.rewind.php Iterator::rewind
     **/
    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->key = 0;
    }

    /**
     * Checks if current position is valid
     *
     * @return bool
     *
     * @see http://php.net/manual/en/iterator.valid.php Iterator::valid
     **/
    #[\ReturnTypeWillChange]
    public function valid()
    {
        return ($this->offsetExists($this->key));
    }

    /**************************************************************************/

    /***************************************************************************
     * Serializable methods
     */

    /**
     * String representation of CanvasArray
     *
     * @return string
     *
     * @see http://php.net/manual/en/serializable.serialize.php
     *      Serializable::serialize()
     **/
    public function __serialize(): array
    {
        $this->requestAllPages();
        return serialize(
            array(
                'page' => $this->page,
                'key' => $this->key,
                'data' => $this->data
            )
        );
    }

    public function serialize(): array
    {
        return $this.__serialize();
    }

    /**
     * Construct a CanvasArray from its string representation
     *
     * The data in the unserialized CanvasArray is static and cannot be
     * refreshed, as the CanvasPest API connection is _not_ serialized to
     * preserve the security of API access tokens.
     *
     * @param string $data
     *
     * @return string
     *
     * @see http://php.net/manual/en/serializable.unserialize.php
     *      Serializable::unserialize()
     **/
    public function __unserialize($data)
    {
        $_data = unserialize($data);
        $this->page = $_data['page'];
        $this->key = $_data['key'];
        $this->data = $_data['data'];
        $this->api = null;
        $this->endpoint = null;
        $this->pagination = array();
    }
    public function unserialize($data)
    {
        $this.__unserialize($data);
    }

//    public function serialize(): array {}
//    public function unserialize(string $data): void {}

}
