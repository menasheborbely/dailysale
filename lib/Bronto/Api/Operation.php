<?php
/**
 * This file was generated by the ConvertToLegacy class in bronto-legacy.
 * The purpose of the conversion was to maintain PSR-0 compliance while
 * the main development focuses on modern styles found in PSR-4.
 *
 * For the original:
 * @see src/Bronto/Api/Operation.php
 */


/**
 * Typed insteraction with APIv4 is achieved through an Bronto_Api_Operation.
 * In its generic form, the Bronto_Api_Operation can handle:
 *
 * - read
 * - add
 * - update
 * - delete
 *
 * @author Philip Cali <philip.cali@bronto.com>
 */
class Bronto_Api_Operation
{
    protected $_api;
    protected $_type;
    protected $_pluralized;
    protected $_methodsToPrefix = array();
    protected $_uniqueKey = array('name');
    protected $_writeLimit = 100;

    /**
     * Wraps the Bronto_Api and transfer type
     *
     * @param Bronto_Api $api
     * @param string $transferType
     * @param array $methods (Optional)
     */
    public function __construct(Bronto_Api $api, $transferType, array $methods = null)
    {
        $this->_api = $api;
        $this->_type = $transferType;
        $this->_pluralized = $this->_pluralize($transferType);
        $methods = is_null($methods) ? array('add', 'update', 'delete') : $methods;

        foreach ($methods as $key => $method) {
            $writeKey = "$method";
            if (is_string($key)) {
                $method = $key;
            }
            if ($this->_supportedPrefix($method)) {
                $this->_methodsToPrefix["{$method}{$this->_pluralized}"] = $writeKey;
            } else {
                $this->_methodsToPrefix[$method] = $writeKey;
            }
        }
    }

    /**
     * Returns the pluralized type of this transfer type
     *
     * @param string $type
     * @return string
     */
    protected function _pluralize($type)
    {
        if (preg_match('/y$/', $type)) {
            $type = preg_replace('/y$/', 'ies', $type);
        } else {
            $type .= 's';
        }
        return $type;
    }

    /**
     * A supported prefix will allow the shorted variations of the API commands
     *
     * @param string $method
     * @return boolean
     */
    protected function _supportedPrefix($method)
    {
        switch ($method) {
        case 'add':
        case 'update':
        case 'delete':
        case 'addOrUpdate':
            return true;
        default:
            return false;
        }
    }

    /**
     * Determines if the method name is supported
     *
     * @param string $name
     * @return boolean
     */
    protected function _supportedMethod($name)
    {
        return array_key_exists($name, $this->_methodsToPrefix);
    }

    /**
     * Returns the write key in update operations
     *
     * @param string $writeMethod
     * @return string
     */
    protected function _resolvedWriteKey($writeMethod)
    {
        $value = "{$this->_pluralized}";
        $value[0] = strtolower($value[0]);
        return $value;
    }

    /**
     * Allows for acceptable methods to be called on the Operation
     * Default read methods:
     *
     * - getById
     * - getByName
     * - read
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (preg_match('/^getBy(.*)/', $name, $match)) {
            switch ($match[1]) {
            case 'Id':
                return $this->createRead()->withId(array($arguments[0]))->first();
            default:
                return $this->createRead()->{"with{$match[1]}"}(array('operator' => 'EqualTo', 'value' => $arguments[0]))->first();
            }
        }
        switch ($name) {
        case 'read':
            $readData = array();
            if (count($arguments) > 0) {
                $readData = $arguments[0];
            }
            return $this->createRead($readData);
        case 'save':
            $thing = $arguments[0];
            if (count($arguments) > 1) {
                $upsert = (boolean) $arguments[1];
            }
            if (!$thing->hasId() && !$this->_supportedPrefix('add') && $this->_supportedPrefix('addOrUpdate')) {
                $writeOps = $this->addOrUpdate();
            } else if (!$thing->hasId()) {
                if ($upsert) {
                    $existingRead = $this->createRead()->withType('AND');
                    foreach ($this->_uniqueKey as $key) {
                        if ($thing->{$key}) {
                            $existingRead->where->{$key}->equalTo($thing->{$key});
                        }
                    }
                    $existingThing = $existingRead->first();
                    if ($existingThing) {
                        $thing = $this->createObject(array_merge($existingThing->toArray(), $thing->toArray()));
                        $writeOps = $this->update();
                    }
                }
                if (!$thing->hasId()) {
                    $writeOps = $this->add();
                }
            } else {
                $writeOps = $this->update();
            }
            foreach ($writeOps->push($thing) as $result) {
                $item = $result->getItem();
                if ($item->getIsError()) {
                    throw new Bronto_Api_Exception($item->getErrorString(), $item->getErrorCode());
                }
                $thing->withId($item->getId());
            }
            return $thing;
        default:
            if ($this->_supportedPrefix($name)) {
                $name = "{$name}{$this->_pluralized}";
            }
            $limit = null;
            if (count($arguments) > 0) {
                $limit = $arguments[0];
            }
            return $this->createWrite($name, $limit, $this->_methodsToPrefix[$name]);
        }
    }

    /**
     * Get the Bronto_Api client issuing the requests
     *
     * @return Bronto_Api
     */
    public function getApi()
    {
        return $this->_api;
    }

    /**
     * Gets the transfer type associated with this operation
     *
     * @return string
     */
    public function getTransferType()
    {
        return $this->_type;
    }

    /**
     * Gets the pluralized type associated with this operation
     *
     * @return string
     */
    public function getPluralizedType()
    {
        return $this->_pluralized;
    }

    /**
     * Creates an API object belonging to this transfer type
     *
     * @param array $data
     * @return Bronto_Api_Object
     */
    public function createObject(array $data = array())
    {
        $modelClass = "Bronto_Api_Model_{$this->getTransferType()}";
        // Note: This snippet was generated with legacy conversion
        if (is_string($modelClass) && !array_key_exists($modelClass, Bronto_ImportManager::$_fileCache)) {
            $dir = str_replace(str_replace("_", "/", "Bronto_Api"), '', dirname(__FILE__));
            $file = $dir . str_replace("_", "/", $modelClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
                Bronto_ImportManager::$_fileCache[$modelClass] = true;
            } else {
                Bronto_ImportManager::$_fileCache[$modelClass] = false;
            }
        }
        // End Conversion Snippet
        if (Bronto_ImportManager::$_fileCache[$modelClass]) {
            return new $modelClass($data);
        }
        return new Bronto_Api_Object($this->_type, $data);
    }

    /**
     * Creates a read pager for the read request
     *
     * @param Bronto_Object $request
     * @return Bronto_Api_Read_Pager
     */
    public function createReadPager($request)
    {
        return new Bronto_Api_Read_ByPage($this, $request);
    }

    /**
     * Creates a write pager for the varying write requests
     *
     * @param Bronto_Object $request
     * @return Bronto_Api_Write_Pager
     */
    public function createWritePager($request)
    {
        return new Bronto_Api_Write_Pager($this, $request);
    }

    /**
     * Creates a read request with default request parameters
     *
     * @param array $readData
     * @param array $requestFields
     * @return Bronto_Api_Read
     */
    public function createRead(array $readData = array(), array $requestFields = array())
    {
        $requestFields = array('filter' => true, 'pageNumber' => true) + $requestFields;
        return new Bronto_Api_Read($this, "read{$this->_pluralized}", $readData, $requestFields);
    }

    /**
     * Creates a write request with default request parameters
     *
     * @return Bronto_Api_Write
     */
    public function createWrite($method, $limit = null, $original = null)
    {
        if (!$this->_supportedMethod($method)) {
            throw new BadMethodCallException("Method $method is not supported.");
        }
        $limit = $limit ?: $this->_writeLimit;
        $original = $original ?: $method;
        return new Bronto_Api_Write($this, $method, $limit, $original, $this->_resolvedWriteKey($original));
    }
}
