<?php

class sfMongoDBLogger extends sfLogger
{
  /**
   * Mongo server handler.
   *
   * @var Mongo
   */
  protected $handler = null;

  /**
   * Reference of MongoDB
   *
   * @var MongoCollection
   */
  protected $collection;

  /**
   * Returns the list of all options and their default value.
   *
   * @return array
   */
  public function getDefaultOptions()
  {
    return array(
      // system defaults
      'host' => Mongo::DEFAULT_HOST,
      'port' => Mongo::DEFAULT_PORT,

      // without auth by default
      'username' => false,
      'password' => false,

      // meta information added to the document
      'document' => array(),

      // MongoDB specific options
      'save' => array(
        'safe' => false,
        'fsync' => false,
        'timeout' => MongoCursor::$timeout,
      ),

      'create' => array(),
    );
  }

  /**
   * Returns the list of all required options for this logger.
   *
   * The list contains all options that MUST be set by the user. It does not include options, that are set by the default options.
   *
   * @return array
   */
  public function getRequiredOptions()
  {
    return array(
      'database',
      'collection',
    );
  }

  /**
   * Initializes this logger.
   *
   * @throws sfInitializationException
   *
   * @param  sfEventDispatcher $dispatcher  A sfEventDispatcher instance
   * @param  array             $options     An array of options.
   *
   * @return bool
   */
  public function initialize(sfEventDispatcher $dispatcher, $options = array())
  {
    if (!class_exists('Mongo'))
    {
      throw new sfInitializationException('The MongoDB extension is not installed or enabled.');
    }

    foreach ($this->getRequiredOptions() as $eachOption)
    {
      if (!isset($options[$eachOption]))
      {
        throw new sfInitializationException(sprintf('The required option "%s" is missing.', $eachOption));
      }
    }

    $this->options = array_merge($this->getDefaultOptions(), $options);

    if ($this->options['username'] and $this->options['password'])
    {
      $this->handler = new Mongo(sprintf('mongodb://%s:%s@%s:%d/%s', $this->options['username'], $this->options['password'], $this->options['host'], $this->options['port'], $this->options['database']));
    }
    else
    {
      $this->handler = new Mongo(sprintf('mongodb://%s:%d', $this->options['host'], $this->options['port']));
    }

    if (!empty($this->options['create']))
    {
      $this->handler->selectDB($this->options['database'])->createCollection($this->options['collection'], $this->options['create']['capped'], $this->options['create']['size'], $this->options['create']['max']);
    }

    $this->collection = $this->handler->selectCollection($this->options['database'], $this->options['collection']);

    return parent::initialize($dispatcher, $this->options);
  }

  /**
   * Logs a message.
   *
   * @param string $message   Message
   * @param string $priority  Message priority
   */
  protected function doLog($message, $priority)
  {
    $log = array(
      'message'  => $message,
      'time'     => new DateTime(),
      'priority' => $this->getPriority($priority)
    );

    $this->collection->insert(array_merge($this->options['document'], $log), $this->options['save']);
  }

  /**
   * Returns the priority string to use in log messages.
   *
   * @param  string $priority The priority constant
   *
   * @return string The priority to use in log messages
   */
  protected function getPriority($priority)
  {
    return sfLogger::getPriorityName($priority);
  }

  /**
   * Executes the shutdown method.
   */
  public function shutdown()
  {
    $this->handler->close();
  }
}
