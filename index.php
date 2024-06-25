<?php
require __DIR__ . '/vendor/autoload.php';

use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use Microsoft\Kiota\Abstractions\ApiException;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Graph\Generated\Users\Item\UserItemRequestBuilderGetRequestConfiguration;

use Microsoft\Graph\Core\Tasks\PageIterator;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$dotenv->required(['CLIENT_ID', 'CLIENT_SECRET', 'TENANT_ID']);

$graphServiceClient = getGraphServiceClient();

$choice = -1;
$userPrincipalName = $_ENV['DEFAULT_PRINCIPAL_NAME'];

while ($choice != 0) {
    echo 'Current user\'s principal name is ' . $userPrincipalName . PHP_EOL;
    echo PHP_EOL;
    echo 'Please choose one of the following options:' . PHP_EOL;
    echo '0. Exit' . PHP_EOL;
    echo '1. Change current user by princial name' . PHP_EOL;
    echo '2. Get current name\'s display name' . PHP_EOL;
    echo '3. Change current name\'s display name' . PHP_EOL;
    echo '4. Get current name\'s roles' . PHP_EOL;
    echo '5. Get current name\'s contact info' . PHP_EOL;
    echo '6. Change current name\'s street address' . PHP_EOL;
    echo PHP_EOL;

    $choice = (int) readline('');

    echo PHP_EOL;

    switch ($choice) {
        case 1:
            setUser();
            break;
        case 2:
            getUserDisplayName();
            break;
        case 3:
            setUserDisplayName();
            break;
        case 4:
            getUserRoles();
            break;
        case 5:
            getUserContactInfo();
            break;
        case 6:
            setUserStreetAddress();
            break;
        default:
            print('Goodbye...' . PHP_EOL);
    }

    echo PHP_EOL;
}

function setUser() {
    global $userPrincipalName;

    echo 'Please enter a princial name:' . PHP_EOL;

    $userPrincipalName = readline('');

    echo "Current user's princial name is $userPrincipalName" . PHP_EOL;
}

function getUserDisplayName() {
    global $graphServiceClient;
    global $userPrincipalName;

    echo 'Please wait...' . PHP_EOL;

    try {
        $user = $graphServiceClient->users()->byUserId($userPrincipalName)->get()->wait();
        echo 'Current user\'s display name is ' . $user->getDisplayName() . PHP_EOL;

    } catch (ApiException $e) {
        echo 'Error: ' . $e->getError()->getMessage();
        exit(0);
    }
}

function setUserDisplayName() {
    global $graphServiceClient;
    global $userPrincipalName;

    echo "Please enter a new display name for $userPrincipalName" . PHP_EOL;

    $newDisplayName = readline('');

    echo 'Please wait...' . PHP_EOL;

    try {
        $user = $graphServiceClient->users()->byUserId($userPrincipalName)->get()->wait();
        $user->setDisplayName($newDisplayName);
        $graphServiceClient->users()->byUserId($userPrincipalName)->patch($user)->wait();
    } catch (ApiException $e) {
        echo 'Error: ' . $e->getError()->getMessage();
        exit(0);
    }

    echo 'Current user\'s display name is now ' . $user->getDisplayName() . PHP_EOL;
}

function getUserRoles() {
    global $graphServiceClient;
    global $userPrincipalName;

    echo 'Please wait...' . PHP_EOL;

    try {
        $user = $graphServiceClient->users()->byUserId($userPrincipalName)->get()->wait();

        echo 'Directory roles of ' . $user->getDisplayName() . ': ' . PHP_EOL;

        $roles = $graphServiceClient->users()->byUserId($userPrincipalName)->memberOf()->graphDirectoryRole()->get()->wait();

        $pageIterator = new PageIterator($roles, $graphServiceClient->getRequestAdapter());

        $callback = function ($role) {
            echo $role->getDisplayName() . PHP_EOL;
        };

        while ($pageIterator->hasNext()) {
            $pageIterator->iterate($callback);
        }
    } catch (ApiException $e) {
        echo 'Error: ' . $e->getError()->getMessage();
        exit(0);
    }
}

function getUserContactInfo() {
    global $graphServiceClient;
    global $userPrincipalName;

    echo 'Please wait...' . PHP_EOL;

    try {
        $requestConfiguration = new UserItemRequestBuilderGetRequestConfiguration();
        $queryParameters = UserItemRequestBuilderGetRequestConfiguration::createQueryParameters();
        $queryParameters->select = ['streetAddress', 'city', 'state', 'postalCode', 'country', 'businessPhones', 'mobilePhone', 'mail', 'faxNumber', 'imAddresses', 'mailNickname'];
        $requestConfiguration->queryParameters = $queryParameters;

        /** @var Microsoft\Graph\Generated\Models\User $user */
        $user = $graphServiceClient->users()->byUserId($userPrincipalName)->get($requestConfiguration)->wait();
        echo 'Street address: ' . $user->getStreetAddress() . PHP_EOL;
        echo 'City: ' . $user->getCity() . PHP_EOL;
        echo 'State or province: ' . $user->getState() . PHP_EOL;
        echo 'ZIP or postal code: ' . $user->getPostalCode() . PHP_EOL;
        echo 'Country or region: ' . $user->getCountry() . PHP_EOL;
        echo 'Business phone: ' . PHP_EOL;
        print_r($user->getBusinessPhones()) . PHP_EOL;
        echo 'Mobile phone: ' . $user->getMobilePhone() . PHP_EOL;
        echo 'Email: ' . $user->getMail() . PHP_EOL;
        echo 'Fax number: ' . $user->getFaxNumber() . PHP_EOL;
        echo 'IM addresses: ' . PHP_EOL;
        print_r($user->getImAddresses());
        echo 'Mail nickname: ' . $user->getMailNickname() . PHP_EOL;

    } catch (ApiException $e) {
        echo 'Error: ' . $e->getError()->getMessage();
        exit(0);
    }
}

function setUserStreetAddress() {
    global $graphServiceClient;
    global $userPrincipalName;

    echo "Please enter a new street address for $userPrincipalName" . PHP_EOL;

    $newStreetAddress = readline('');

    echo 'Please wait...' . PHP_EOL;

    try {
        $user = $graphServiceClient->users()->byUserId($userPrincipalName)->get()->wait();
        $user->setStreetAddress($newStreetAddress);
        $graphServiceClient->users()->byUserId($userPrincipalName)->patch($user)->wait();
    } catch (ApiException $e) {
        echo 'Error: ' . $e->getError()->getMessage();
        exit(0);
    }

    echo 'Current user\'s street address is now ' . $user->getDisplayName() . PHP_EOL;
}

function getGraphServiceClient() {
    $tokenRequestContext = new ClientCredentialContext(
        $_ENV['TENANT_ID'],
        $_ENV['CLIENT_ID'],
        $_ENV['CLIENT_SECRET']
    );

    return new GraphServiceClient($tokenRequestContext);
}