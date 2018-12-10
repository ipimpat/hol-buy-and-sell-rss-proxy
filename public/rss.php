<?php

require __DIR__ . '/../vendor/autoload.php';

// Fetch the "Buy & Sell" DOM (links)
$links = Sunra\PhpSimple\HtmlDomParser::str_get_html(file_get_contents('https://www.hardwareonline.dk/koebsalgoversigt.aspx', false, stream_context_create(['http' => ['user_agent' => 'HOL Buy & sell RSS Proxy']])))
    ->find('#ContentPlaceHolder_ContentPlaceHolder_data .ks-oversigt');

// RSS feed
$feed = new Bhaktaraz\RSSGenerator\Feed();

// Selling channel
$channels['selling'] = new Bhaktaraz\RSSGenerator\Channel();
$channels['selling']->title('HardwareOnline - Selling')
    ->description('Selling advertisements')
    ->url('https://www.hardwareonline.dk/koebsalgoversigt.aspx')->appendTo($feed);

// Buying channel
$channels['buying'] = new Bhaktaraz\RSSGenerator\Channel();
$channels['buying']->title('HardwareOnline - Buying')
    ->description('Buying advertisements')
    ->url('https://www.hardwareonline.dk/koebsalgoversigt.aspx')->appendTo($feed);

// Exchange channel
$channels['exchange'] = new Bhaktaraz\RSSGenerator\Channel();
$channels['exchange']->title('HardwareOnline - Exchange')
    ->description('Exchange advertisements')
    ->url('https://www.hardwareonline.dk/koebsalgoversigt.aspx')->appendTo($feed);

// Loop through the sales ads
foreach ($links as $link)
{
    // Make sure we have something that looks like a HOL sale
    if (preg_match('/^(.+):\s(.+)\(([\d]+)\)/', $link->find('div a', 0)->plaintext, $advertisement))
    {

        // Create a new RSS item for the feed
        $item = new Bhaktaraz\RSSGenerator\Item();

        // Set the RSS item properties
        $item->title(html_entity_decode(trim($advertisement[2])))
            ->description(sprintf('User: %s ZIP: %s Comments: %d', trim($link->find('div a', 1)->plaintext), trim($link->find('div a', 2)->plaintext), intval($advertisement[3])))
            ->url('https://www.hardwareonline.dk/' . $link->find('div:nth-child(1) a', 0)->href);

        // Add the RSS item to the right channel
        switch (strtolower(substr($advertisement[1], 0, 1)))
        {
            // Add to buy channel
            case 'k':
                $item->appendTo($channels['buying']);
                break;
            case 's':
                $item->appendTo($channels['selling']);
                break;
            case 'b':
                $item->appendTo($channels['exchange']);
                break;
        }
    }
}

// Send the RSS feed to the output buffer
echo $feed;
