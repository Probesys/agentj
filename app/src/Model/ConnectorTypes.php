<?php

namespace App\Model;

use App\Util\Enum;

// TODO: singulariser
class ConnectorTypes extends Enum
{
    public const OFFICE365 = 'Office365';
    public const LDAP = 'LDAP';
    public const IMAP = 'IMAP';
}
