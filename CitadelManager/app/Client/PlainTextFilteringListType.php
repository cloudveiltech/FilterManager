<?php

namespace App\Client;

class PlainTextFilteringListType
{

    /**
     * A plain text file where each line contains a domain or URL that should be blacklisted.
     */
    const Blacklist = 'Blacklist';

    /**
     * A plain text file where each line contains a domain or URL that should be whitelisted.
     */
    const Whitelist = 'Whitelist';

    /**
     * A plain text file where each line contains a domain or URL that should be blacklisted,
     * but also should also be capable of being transformed on-demand into a whitelist.
     */
    const BypassList = 'BypassList';

    /**
     * A plain text file where each line contains arbitrary text that, if detected within a HTML
     * text payload, should trigger a block action.
     */
    const TextTrigger = 'TextTrigger';

}