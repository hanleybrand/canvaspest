<?php

/** smtech\CanvasPest\CanvasObject */

namespace smtech\CanvasPest;

/**
 * An object that represents a single Canvas object, providing both object-
 * style access (obj->key) and array-style access (array[key]).
 * CanvasObject objects are immutable, so attempts to change their
 * underlying data will result in exceptions.
 *
 * A CanvasObject is returned from any API request for which the endpoint ends
 * with a specific ID number (e.g. http://example.com/api/v1/accounts/1/users/1).
 *
 * @author Seth Battis <SethBattis@stmarksschool.org>
 **/
class CanvasObject implements \ArrayAccess, \Serializable
{

    /** @var array $data Backing store */
    private $data;

    /**
     * Construct a CanvasObject
     *
     * @param string|string[] $response JSON-encoded response from the Canvas
     *                                  API or the resulting JSON-decoded
     *                                  associative array
     **/
    public function __construct($response)
    {
        if (is_array($response)) {
            $this->data = $response;
        } else {
            $this->data = json_decode($response, true);
        }
    }

    /***************************************************************************
     * Object methods
     */

    /**
     * Whether a property exists
     *
     * @param string $key
     *
     * @return bool
     *
     * @see http://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members
     *      Property overloading
     **/
    public function __isset($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Property to retrieve
     *
     * @param string $key
     *
     * @return mixed
     *
     * @see http://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members
     *      Property overloading
     **/
    public function __get($key)
    {
        return $this->data[$key];
    }

    /**
     * Whether a property exists
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     *
     * @throws CanvasObject_Exception IMMUTABLE All calls to this method will cause an exception
     *
     * @deprecated Canvas objects are immutable
     *
     * @see http://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members
     *      Property overloading
     **/
    public function __set($key, $value)
    {
        throw new CanvasObject_Exception(
            'Canvas objects are immutable',
            CanvasObject_Exception::IMMUTABLE
        );
    }

    /**
     * Unset a property
     *
     * @param string $key
     *
     * @return void
     *
     * @throws CanvasObject_Exception IMMUTABLE All calls to this method will cause an exception
     *
     * @deprecated Canvas objects are immutable
     *
     * @see http://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members
     *      Property overloading
     **/
    public function __unset($key)
    {
        throw new CanvasObject_Exception(
            'Canvas objects are immutable',
            CanvasObject_Exception::IMMUTABLE
        );
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
     * @see http://php.net/manual/en/arrayaccess.offsetexists.php ArrayAccess::offsetExists()
     **/
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * Offset to retrieve
     *
     * @param int|string $offset
     *
     * @return mixed|null
     *
     * @see http://php.net/manual/en/arrayaccess.offsetexists.php ArrayAccess::offsetGet()
     **/
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    /**
     * Assign a value to the specified offset
     *
     * @param int|string $offset
     * @param mixed $value
     *
     * @return void
     *
     * @throws CanvasObject_Exception IMMUTABLE All calls to this method will cause an exception
     *
     * @deprecated Canvas objects are immutable
     *
     * @see http://php.net/manual/en/arrayaccess.offsetset.php ArrayAccess::offsetSet()
     **/
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        throw new CanvasObject_Exception(
            'Canvas objects are immutable',
            CanvasObject_Exception::IMMUTABLE
        );
    }

    /**
     * Unset an offset
     *
     * @param int|string $offset
     *
     * @return void
     *
     * @throws CanvasObject_Exception IMMUTABLE All calls to this method will cause an exception
     *
     * @deprecated Canvas objects are immutable
     *
     * @see http://php.net/manual/en/arrayaccess.offsetunset.php ArrayAccess::offsetUnset()
     **/
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new CanvasObject_Exception(
            'Canvas objects are immutable',
            CanvasObject_Exception::IMMUTABLE
        );
    }

    /***************************************************************************
     * Serializable methods
     */

    /**
     * String representation of CanvasObject
     *
     * @return string
     *
     * @see http://php.net/manual/en/serializable.serialize.php Serializable::serialize()
     **/
    public function __serialize()
    {
        return serialize($this->data);
    }

    public function serialize()
    {
        return $this . __serialize();
    }

    /**
     * Construct a CanvasObject from its string representation
     *
     * @param string $data
     *
     * @return void
     *
     * @see http://php.net/manual/en/serializable.unserialize.php Serializable::unsserialize()
     **/
    public function __unserialize($data)
    {
        $this->data = unserialize($data);
    }

    public function unserialize($data)
    {
        $this . __unserialize($data);
    }

    /**************************************************************************/
    /**
     * An array representation of the CanvasObject
     *
     * @return array
     **/
    #[\ReturnTypeWillChange]
    public function getArrayCopy(): array
    {
        return $this->data;
    }
}
