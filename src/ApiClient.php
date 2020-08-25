<?php declare(strict_types=1);
namespace NAVIT\AzureAd;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\BadResponseException;
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
     * @param string $tenant Tenant name
     * @param HttpClient $authClient Pre-configured HTTP client for auth
     * @param HttpClient $httpClient Pre-configured HTTP client for the API calls
     */
    public function __construct(string $clientId, string $clientSecret, string $tenant, HttpClient $authClient = null, HttpClient $httpClient = null) {
        $response = ($authClient ?: new HttpClient())->post(sprintf('https://login.microsoftonline.com/%s/oauth2/v2.0/token', $tenant), [
            'form_params' => [
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'scope'         => 'https://graph.microsoft.com/.default',
                'grant_type'    => 'client_credentials',
            ],
        ]);

        /** @var array{access_token: string} */
        $response = json_decode((string) $response->getBody(), true);

        $this->httpClient = $httpClient ?: new HttpClient([
            'base_uri' => $this->baseUri,
            'headers'  => [
                'Accept'        => 'application/json',
                'Authorization' => sprintf('Bearer %s', (string) $response['access_token']),
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
        } catch (BadResponseException $e) {
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
        } catch (BadResponseException $e) {
            return null;
        }

        /** @var array{value: array<int, array>} */
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
        } catch (BadResponseException $e) {
            return null;
        }

        /** @var array{value: array<int, array>} */
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
     * @throws RuntimeException
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
        } catch (BadResponseException $e) {
            throw new RuntimeException('Unable to create group', (int) $e->getCode(), $e);
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
        } catch (BadResponseException $e) {
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
     * @throws RuntimeException
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
        } catch(BadResponseException $e) {
            throw new RuntimeException('Unable to add group to enterprise application', (int) $e->getCode(), $e);
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
            return $this->getGroupById((string) $group['principalId']);
        }, array_filter($this->getPaginatedData($url, ['principalId', 'principalType']), function(array $group) : bool {
            return 'group' === strtolower((string) $group['principalType']);
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
                /** @var Models\GroupMember */
                return Models\GroupMember::fromArray($member);
            } catch (InvalidArgumentException $e) {
                return null;
            }
        }, $this->getPaginatedData(sprintf('groups/%s/members', $groupId), ['id', 'displayName', 'mail', 'accountEnabled'])));
    }

    /**
     * Get all groups that a user is a member of
     *
     * @param string $userId ID of the user
     * @return Models\Group[]
     */
    public function getUserGroups(string $userId) : array {
        return array_filter(array_map(function(array $group) : ?Models\Group {
            try {
                return Models\Group::fromArray($group);
            } catch (InvalidArgumentException $e) {
                return null;
            }
        }, $this->getPaginatedData(sprintf('users/%s/memberOf/microsoft.graph.group', $userId), ['id', 'displayName', 'description', 'mailNickname'])));
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
                /** @var Models\GroupOwner */
                return Models\GroupOwner::fromArray($member);
            } catch (InvalidArgumentException $e) {
                return null;
            }
        }, $this->getPaginatedData(sprintf('groups/%s/owners', $groupId), ['id', 'displayName', 'mail', 'accountEnabled'])));
    }

    /**
     * Get a user by ID
     *
     * @param string $userId
     * @return ?Models\User
     */
    public function getUserById(string $userId) : ?Models\User {
        try {
            $response = $this->httpClient->get(sprintf('users/%s', $userId), ['query' => [
                '$select' => join(',', [
                    'id', 'displayName', 'mail', 'accountEnabled'
                ])
            ]]);
        } catch (BadResponseException $e) {
            return null;
        }

        /** @var Models\User */
        return Models\User::fromApiResponse($response);
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

        while (null !== $url) {
            try {
                $response = $this->httpClient->get($url, array_filter(['query' => $query]));
            } catch (BadResponseException $e) {
                throw new RuntimeException('Unable to fetch paginated data', (int) $e->getCode(), $e);
            }

            /** @var array{value: array, "@odata.nextLink": ?string} */
            $body = json_decode($response->getBody()->getContents(), true);
            $entries = array_merge($entries, $body['value']);
            $url = $body['@odata.nextLink'] ?? null;
            $query = []; // Only need this for the first request
        }

        return $entries;
    }

    /**
     * Add a specific user to a group
     *
     * @param string $userId
     * @param string $groupId
     * @return void
     */
    public function addUserToGroup(string $userId, string $groupId) : void {
        try {
            $this->httpClient->post(sprintf('groups/%s/members/$ref', $groupId), ['json' => [
                '@odata.id' => sprintf('%s/users/%s', rtrim($this->baseUri, '/'), $userId),
            ]]);
        } catch(BadResponseException $e) {
            throw new RuntimeException('Unable to add user to group', (int) $e->getCode(), $e);
        }
    }

    /**
     * Remove a specific user from a group
     *
     * @param string $userId
     * @param string $groupId
     * @return void
     */
    public function removeUserFromGroup(string $userId, string $groupId) : void {
        try {
            $this->httpClient->delete(sprintf('groups/%s/members/%s/$ref', $groupId, $userId));
        } catch(BadResponseException $e) {
            throw new RuntimeException('Unable to remove user from group', (int) $e->getCode(), $e);
        }
    }
}