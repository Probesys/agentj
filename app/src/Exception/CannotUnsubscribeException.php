<?php

namespace App\Exception;

/**
 * Thrown when a user tries to unsubscribe from the list of a message, while
 * they are not allowed to or while the message cannot be unsubscribed from.
 */
class CannotUnsubscribeException extends \RuntimeException
{
}
