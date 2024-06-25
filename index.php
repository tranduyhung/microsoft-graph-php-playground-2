<?php
require __DIR__ . '/vendor/autoload.php';

use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use Microsoft\Kiota\Abstractions\ApiException;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Graph\Generated\Users\Item\AppRoleAssignments\AppRoleAssignmentsRequestBuilderGetRequestConfiguration;
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
            break;
        case 4:
            getUserRoles();
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

function getGraphServiceClient() {
    $tokenRequestContext = new ClientCredentialContext(
        $_ENV['TENANT_ID'],
        $_ENV['CLIENT_ID'],
        $_ENV['CLIENT_SECRET']
    );

    return new GraphServiceClient($tokenRequestContext);
}