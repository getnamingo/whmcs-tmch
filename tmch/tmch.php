<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function tmch_config()
{
    return [
        'name' => 'TMCH Claims Notice Support',
        'description' => 'Supports managing Trademark Clearinghouse (TMCH) claims notices',
        'version' => '1.0',
        'author' => 'Namingo',
        'fields' => [
            'username' => [
                'FriendlyName' => 'Username',
                'Type' => 'text',
                'Size' => '25',
                'Default' => '',
                'Description' => 'Enter your TMCH username',
            ],
            'password' => [
                'FriendlyName' => 'Password',
                'Type' => 'password',
                'Size' => '25',
                'Default' => '',
                'Description' => 'Enter your TMCH password',
            ],
        ],
    ];
}

function tmch_activate()
{
    return [
        'status' => 'success',
        'description' => 'TMCH Claims Notice Support activated successfully.',
    ];
}

function tmch_deactivate()
{
    return [
        'status' => 'success',
        'description' => 'TMCH Claims Notice Support deactivated successfully.',
    ];
}

function tmch_clientarea($vars)
{
    $username = $vars['username'];
    $password = $vars['password'];

    // Access GET parameters and sanitize the lookupKey
    $lookupKey = filter_input(INPUT_GET, 'lookupKey', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if (!$lookupKey) {
        // Display the form to input 'lookupKey'
        return [
            'pagetitle' => 'TMCH Claims Notice',
            'breadcrumb' => ['index.php?m=tmch' => 'TMCH Claims Notice'],
            'templatefile' => 'clientarea',
            'requirelogin' => false,
            'vars' => [
                'error' => 'Please provide a lookupKey.',
            ],
        ];
    }

    $url = "https://test.tmcnis.org/cnis/" . $lookupKey . ".xml";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $xml = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
    }
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($xml) {
        $xml_object = simplexml_load_string($xml);
        $xml_object->registerXPathNamespace("tmNotice", "urn:ietf:params:xml:ns:tmNotice-1.0");
        $claims = $xml_object->xpath('//tmNotice:claim');

        $note = "This message is a notification that you have applied for a domain name that matches a trademark record submitted to the Trademark Clearinghouse. Your eligibility to register this domain name will depend on your intended use and if it is similar or relates to the trademarks listed below." . PHP_EOL;

        $note .= "Please be aware that your rights to register this domain name may not be protected as a noncommercial use or 'fair use' in accordance with the laws of your country. It is crucial that you read and understand the trademark information provided, including the trademarks, jurisdictions, and goods and services for which the trademarks are registered." . PHP_EOL;

        $note .= "It's also important to note that not all jurisdictions review trademark applications closely, so some of the trademark information may exist in a national or regional registry that does not conduct a thorough review of trademark rights prior to registration. If you have any questions, it's recommended that you consult with a legal expert or attorney on trademarks and intellectual property for guidance." . PHP_EOL;

        $note .= "By continuing with this registration, you're representing that you have received this notice and understand it and, to the best of your knowledge, your registration and use of the requested domain name will not infringe on the trademark rights listed below." . PHP_EOL;

        $note .= "The following " . count($claims) . " marks are listed in the Trademark Clearinghouse:" . PHP_EOL;

        $note .= PHP_EOL;

        foreach ($claims as $claim) {
            $elements = $claim->xpath('.//*');
            $firstHolder = true;
            $firstContact = true;
            foreach ($elements as $element) {
                $elementName = trim($element->getName());
                $elementText = trim((string) $element);
                if (!empty($elementName) && !empty($elementText)) {
                    if ($element->xpath('..')[0]->getName() == "holder" && $firstHolder) {
                        $note .= "Trademark Registrant: " . PHP_EOL;
                        $firstHolder = false;
                    }
                    if ($element->xpath('..')[0]->getName() == "contact" && $firstContact) {
                        $note .= "Trademark Contact: " . PHP_EOL;
                        $firstContact = false;
                    }
                    $note .= $elementName . ": " . $elementText . PHP_EOL;
                }
            }
            $note .= PHP_EOL;
        }

        // Return data to the template
        return [
            'pagetitle' => 'TMCH Claims Notice',
            'breadcrumb' => ['index.php?m=tmch' => 'TMCH Claims Notice'],
            'templatefile' => 'clientarea',
            'requirelogin' => false,
            'vars' => [
                'note' => nl2br(htmlspecialchars($note)),
            ],
        ];
    } else {
        $error = 'No claims notice loaded';
        return [
            'pagetitle' => 'TMCH Claims Notice',
            'breadcrumb' => ['index.php?m=tmch' => 'TMCH Claims Notice'],
            'templatefile' => 'clientarea',
            'requirelogin' => false,
            'vars' => [
                'error' => htmlspecialchars($error),
            ],
        ];
    }
}
