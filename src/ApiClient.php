<?php declare(strict_types=1);
namespace NAVIT\AzureAd;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use InvalidArgumentException;
use RuntimeException;

class ApiClient {
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var string
     */
    private $baseUri = 'https://graph.microsoft.com/beta/';

    /**
     * Class constructor
     *
     * @param string $clientId Client ID
     * @param string $clientSecret Client secret
     * @param HttpClient $authClient Pre-configured HTTP client for auth
     * @param HttpClient $httpClient Pre-configured HTTP client for the API calls
     */
    public function __construct(string $clientId, string $clientSecret, HttpClient $authClient = null, HttpClient $httpClient = null) {
        $response = ($authClient ?: new HttpClient())->post('https://login.microsoftonline.com/nav.no/oauth2/v2.0/token', [
            'form_params' => [
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'scope'         => 'https://graph.microsoft.com/.default',
                'grant_type'    => 'client_credentials',
            ],
        ]);

        $response = json_decode((string) $response->getBody(), true);

        $this->httpClient = $httpClient ?: new HttpClient([
            'base_uri' => $this->baseUri,
            'headers'  => [
                'Accept'        => 'application/json',
                'Authorization' => sprintf('Bearer %s', $response['access_token']),
            ],
        ]);
    }

    /**
     * Get a group by ID
     *
     * @param string $groupId
     * @return Models\Group|null
     */
    public function getGroupById(string $groupId) : ?Models\Group {
        try {
            $response = $this->httpClient->get(sprintf('groups/%s', $groupId));
        } catch (ClientException $e) {
            return null;
        }

        /** @var Models\Group */
        return Models\Group::fromApiResponse($response);
    }

    /**
     * Get a group by display name
     *
     * @param string $displayName
     * @return Models\Group|null
     */
    public function getGroupByDisplayName(string $displayName) : ?Models\Group {
        try {
            $response = $this->httpClient->get('groups', [
                'query' => [
                    '$filter' => sprintf('displayName eq \'%s\'', $displayName),
                ],
            ]);
        } catch (ClientException $e) {
            return null;
        }

        $groups = json_decode($response->getBody()->getContents(), true);

        return !empty($groups['value'])
            ? Models\Group::fromArray($groups['value'][0])
            : null;
    }

    /**
     * Get a group by mailNickname
     *
     * @param string $mailNickname
     * @return Models\Group|null
     */
    public function getGroupByMailNickname(string $mailNickname) : ?Models\Group {
        try {
            $response = $this->httpClient->get('groups', [
                'query' => [
                    '$filter' => sprintf('mailNickname eq \'%s\'', $mailNickname),
                ],
            ]);
        } catch (ClientException $e) {
            return null;
        }

        $groups = json_decode($response->getBody()->getContents(), true);

        return !empty($groups['value'])
            ? Models\Group::fromArray($groups['value'][0])
            : null;
    }

    /**
     * Create a group
     *
     * @param string $displayName The name of the group
     * @param string $description The description of the group
     * @param string[] $owners List of users to be added as owners
     * @param string[] $members List of users to be added as members
     * @throws InvalidArgumentException
     * @return Models\Group
     */
    public function createGroup(string $displayName, string $description, array $owners = [], array $members = []) : Models\Group {
        $prefixer = function(string $user) : string {
            return sprintf('%s/users/%s', rtrim($this->baseUri, '/'), $user);
        };

        try {
            $response = $this->httpClient->post('groups', [
                'json' => array_filter([
                    'displayName'        => $displayName,
                    'description'        => $description,
                    'securityEnabled'    => true,
                    'mailEnabled'        => true,
                    'mailNickname'       => $displayName,
                    'groupTypes'         => ['unified'],
                    'visibility'         => 'Private',
                    'owners@odata.bind'  => array_map($prefixer, $owners),
                    'members@odata.bind' => array_map($prefixer, $members),
                ]),
            ]);
        } catch (ClientException $e) {
            throw new InvalidArgumentException('Unable to create group', $e->getCode(), $e);
        }

        /** @var Models\Group */
        return Models\Group::fromApiResponse($response);
    }

    /**
     * Set group description
     *
     * @param string $groupId The ID of the group
     * @param string $description The new description
     * @return bool Returns true on success or false otherwise
     */
    public function setGroupDescription(string $groupId, string $description) : bool {
        try {
            $this->httpClient->patch(sprintf('groups/%s', $groupId), [
                'json' => [
                    'description' => $description,
                ],
            ]);
        } catch (ClientException $e) {
            return false;
        }

        return true;
    }

    /**
     * Add Azure AD group to an enterprise application
     *
     * @param string $groupId The ID of the group to add
     * @param string $applicationObjectId The object ID of the application to add the group to
     * @param string $applicationRoleId The role ID the group will receive
     * @throws InvalidArgumentException
     * @return void
     */
    public function addGroupToEnterpriseApp(string $groupId, string $applicationObjectId, string $applicationRoleId) : void {
        try {
            $this->httpClient->post(sprintf('servicePrincipals/%s/appRoleAssignments', $applicationObjectId), [
                'json' => [
                    'principalId' => $groupId,
                    'appRoleId'   => $applicationRoleId,
                    'resourceId'  => $applicationObjectId,
                ],
            ]);
        } catch(ClientException $e) {
            throw new InvalidArgumentException('Unable to add group to enterprise application', $e->getCode(), $e);
        }
    }

    /**
     * Get all groups connected to a specific enterprise application in Azure AD
     *
     * @param string $applicationObjectId The object ID of the application
     * @return Models\Group[]
     */
    public function getEnterpriseAppGroups(string $applicationObjectId) : array {
        $url = sprintf('servicePrincipals/%s/appRoleAssignedTo', $applicationObjectId);

        return array_filter(array_map(function(array $group) : ?Models\Group {
            return $this->getGroupById($group['principalId']);
        }, array_filter($this->getPaginatedData($url, ['principalId', 'principalType']), function(array $group) : bool {
            return 'group' === strtolower($group['principalType']);
        })));
    }

    /**
     * Get all members in a group
     *
     * @param string $groupId The group ID
     * @return Models\GroupMember[] Returns an array of users
     */
    public function getGroupMembers(string $groupId) : array {
        return array_filter(array_map(function(array $member) : ?Models\GroupMember {
            try {
                /** @var ?Models\GroupMember */
                return Models\GroupMember::fromArray($member);
            } catch (InvalidArgumentException $e) {
                return null;
            }
        }, $this->getPaginatedData(sprintf('groups/%s/members', $groupId), ['id', 'displayName', 'mail'])));
    }

    /**
     * Get all owners of a group
     *
     * @param string $groupId The group ID
     * @return Models\GroupOwner[] Returns an array of users
     */
    public function getGroupOwners(string $groupId) : array {
        return array_filter(array_map(function(array $member) : ?Models\GroupOwner {
            try {
                /** @var ?Models\GroupOwner */
                return Models\GroupOwner::fromArray($member);
            } catch (InvalidArgumentException $e) {
                return null;
            }
        }, $this->getPaginatedData(sprintf('groups/%s/owners', $groupId), ['id', 'displayName', 'mail'])));
    }

    /**
     * Get paginated data from the API
     *
     * @param string $url The URL to fetch
     * @param array $fields Fields to fetch
     * @throws RuntimeException
     * @return array
     */
    private function getPaginatedData(string $url, array $fields = []) : array {
        $entries = [];

        $query = array_filter([
            '$select' => join(',', $fields),
            '$top'    => 100
        ]);

        while ($url) {
            try {
                $response = $this->httpClient->get($url, array_filter(['query' => $query]));
            } catch (ClientException $e) {
                throw new RuntimeException('Unable to fetch paginated data', $e->getCode(), $e);
            }

            $body = json_decode($response->getBody()->getContents(), true);
            $entries = array_merge($entries, $body['value']);
            $url = $body['@odata.nextLink'] ?? null;
            $query = []; // Only need this for the first request
        }

        return $entries;
    }

    /**
     * Get a user by ID
     *
     * @param string $userId
     * @return ?Models\User
     */
    public function getUserById(string $userId) : ?Models\User {
        try {
            $response = $this->httpClient->get(sprintf('users/%s', $userId));
        } catch (ClientException $e) {
            return null;
        }

        /** @var Models\User */
        return Models\User::fromApiResponse($response);
    }
}