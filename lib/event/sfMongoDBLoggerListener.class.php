<?php

class sfMongoDBLoggerListener
{
  /**
   * Adds the current timestamp to the log entry.
   *
   * @param sfEvent $event
   * @param array $logEntry
   *
   * @return array
   */
  public static function addTimestamp(sfEvent $event, array $logEntry)
  {
    $logEntry['time'] = new DateTime();
    $logEntry['timestamp'] = new MongoDate($logEntry['time']->format('U'));

    return $logEntry;
  }

  /**
   * Converts the log priority into its string representation.
   *
   * @param sfEvent $event
   * @param array $logEntry
   *
   * @return array
   */
  public static function convertLogPriority(sfEvent $event, array $logEntry)
  {
    $logEntry['priority'] = sfLogger::getPriorityName($logEntry['priority']);

    return $logEntry;
  }
}